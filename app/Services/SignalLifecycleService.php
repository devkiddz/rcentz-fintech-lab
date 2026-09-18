<?php

namespace App\Services;

use App\Models\Signal;
use App\Models\SignalEvent;
use App\Models\SignalTarget;
use Illuminate\Support\Facades\DB;

class SignalLifecycleService
{
    public function __construct(
        private readonly SignalMarketContextService $marketContext
    ) {}

    public function process(?int $signalId = null): array
    {
        $query = Signal::query()
            ->with(['stock', 'targets'])
            ->whereIn('status', Signal::OPEN_STATUSES)
            ->orderBy('id');

        if ($signalId !== null) {
            $query->whereKey($signalId);
        }

        $stats = [
            'checked' => 0,
            'activated' => 0,
            'target_hits' => 0,
            'closed' => 0,
            'stopped' => 0,
            'expired' => 0,
            'unchanged' => 0,
            'failed' => 0,
        ];
        $items = [];

        $query->chunkById(100, function ($signals) use (&$stats, &$items) {
            foreach ($signals as $signal) {
                $stats['checked']++;

                try {
                    $result = $this->processOne($signal);
                    foreach ($result['counters'] as $key => $value) {
                        if (isset($stats[$key])) {
                            $stats[$key] += $value;
                        }
                    }
                    $items[] = $result;
                } catch (\Throwable $e) {
                    $stats['failed']++;
                    $items[] = [
                        'signal_id' => $signal->id,
                        'symbol' => $signal->stock?->symbol,
                        'status' => 'failed',
                        'price' => null,
                        'message' => $e->getMessage(),
                        'counters' => [],
                    ];
                }
            }
        });

        return ['stats' => $stats, 'items' => $items];
    }

    public function processOne(Signal $signal): array
    {
        $signal->loadMissing(['stock', 'targets']);
        $context = $this->marketContext->forStock($signal->stock, $signal->marketplace);
        $price = (float) $context['current_price'];

        return DB::transaction(function () use ($signal, $price) {
            $locked = Signal::query()->with('targets')->lockForUpdate()->findOrFail($signal->id);
            $counters = [
                'activated' => 0,
                'target_hits' => 0,
                'closed' => 0,
                'stopped' => 0,
                'expired' => 0,
                'unchanged' => 0,
            ];

            if (! in_array($locked->status, Signal::OPEN_STATUSES, true)) {
                $counters['unchanged']++;
                return $this->result($locked, $price, 'Signal is already terminal.', $counters);
            }

            if ($locked->expires_at && $locked->expires_at->lte(now())) {
                $locked->update([
                    'status' => 'expired',
                    'closed_at' => now(),
                ]);
                $this->event($locked, 'expired', ['market_price' => $price]);
                $counters['expired']++;
                return $this->result($locked->fresh(), $price, 'Signal expired.', $counters);
            }

            if ($locked->status === 'ready') {
                $counters['unchanged']++;
                return $this->result($locked, $price, 'Ready Signal awaits publication; market execution lifecycle is not active yet.', $counters);
            }

            if ($locked->status === 'published' && $this->entryReached($locked, $price)) {
                $locked->update([
                    'status' => 'active',
                    'activated_at' => now(),
                ]);
                $this->event($locked, 'activated', ['market_price' => $price]);
                $counters['activated']++;
                $locked->refresh();
            }

            if ($locked->status !== 'active') {
                $counters['unchanged']++;
                return $this->result($locked, $price, 'Published Signal has not reached its entry zone.', $counters);
            }

            if ($this->stopReached($locked, $price)) {
                $locked->update([
                    'status' => 'stopped',
                    'closed_at' => now(),
                ]);
                $this->event($locked, 'stopped', [
                    'market_price' => $price,
                    'stop_loss' => (float) $locked->stop_loss,
                ]);
                $counters['stopped']++;
                return $this->result($locked->fresh(), $price, 'Stop loss reached.', $counters);
            }

            foreach ($locked->targets()->where('status', 'pending')->orderBy('sequence')->lockForUpdate()->get() as $target) {
                if (! $this->targetReached($locked, $target, $price)) {
                    continue;
                }

                $target->update([
                    'status' => 'hit',
                    'hit_at' => now(),
                ]);
                $this->event($locked, 'target_hit', [
                    'sequence' => $target->sequence,
                    'target_price' => (float) $target->price,
                    'market_price' => $price,
                ], $target);
                $counters['target_hits']++;
            }

            if (! $locked->targets()->where('status', 'pending')->exists()) {
                $locked->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                ]);
                $this->event($locked, 'closed', ['market_price' => $price, 'reason' => 'all_targets_hit']);
                $counters['closed']++;
                return $this->result($locked->fresh(), $price, 'All Signal targets were reached.', $counters);
            }

            if (array_sum($counters) === 0) {
                $counters['unchanged']++;
            }

            return $this->result($locked->fresh(), $price, 'Active Signal checked.', $counters);
        }, 3);
    }

    private function entryReached(Signal $signal, float $price): bool
    {
        return $price >= (float) $signal->entry_min && $price <= (float) $signal->entry_max;
    }

    private function stopReached(Signal $signal, float $price): bool
    {
        if ($signal->direction === 'buy') {
            return $price <= (float) $signal->stop_loss;
        }

        if ($signal->direction === 'sell') {
            return $price >= (float) $signal->stop_loss;
        }

        return false;
    }

    private function targetReached(Signal $signal, SignalTarget $target, float $price): bool
    {
        if ($signal->direction === 'buy') {
            return $price >= (float) $target->price;
        }

        if ($signal->direction === 'sell') {
            return $price <= (float) $target->price;
        }

        return false;
    }

    private function event(Signal $signal, string $type, array $payload, ?SignalTarget $target = null): void
    {
        SignalEvent::create([
            'signal_id' => $signal->id,
            'signal_target_id' => $target?->id,
            'type' => $type,
            'payload' => $payload,
            'occurred_at' => now(),
        ]);
    }

    private function result(Signal $signal, float $price, string $message, array $counters): array
    {
        return [
            'signal_id' => $signal->id,
            'symbol' => $signal->stock?->symbol,
            'status' => $signal->status,
            'price' => $price,
            'message' => $message,
            'counters' => $counters,
        ];
    }
}
