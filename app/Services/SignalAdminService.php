<?php

namespace App\Services;

use App\Models\MarketInstrument;
use App\Models\Signal;
use App\Models\SignalEvent;
use App\Models\SignalRevision;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SignalAdminService
{
    public function __construct(
        private readonly SignalRevisionService $revisions
    ) {}

    public function createManual(array $data, User $actor): Signal
    {
        $this->assertManualContract($data);

        return DB::transaction(function () use ($data, $actor) {
            $targets = $this->targetsFromData($data);
            $riskReward = $this->deriveRiskReward($data, $targets);
            $stockId = (int) $data['stock_id'];
            $instrument = MarketInstrument::query()->where('stock_id', $stockId)->first();

            if (! $instrument) {
                throw new RuntimeException('Selected stock is missing from the MarketInstrument registry.');
            }

            $signal = Signal::create([
                'stock_id' => $stockId,
                'market_instrument_id' => $instrument->id,
                'marketplace' => strtolower((string) $data['marketplace']),
                'source' => 'admin_manual',
                'direction' => strtolower((string) $data['direction']),
                'timeframe' => strtolower((string) $data['timeframe']),
                'status' => 'ready',
                'strength' => strtolower((string) ($data['strength'] ?? 'moderate')),
                'confluence_score' => isset($data['confluence_score']) && $data['confluence_score'] !== ''
                    ? (float) $data['confluence_score']
                    : null,
                'entry_min' => (float) $data['entry_min'],
                'entry_max' => (float) $data['entry_max'],
                'stop_loss' => (float) $data['stop_loss'],
                'risk_reward' => $riskReward,
                'rationale' => filled($data['rationale'] ?? null) ? trim((string) $data['rationale']) : null,
                'analysis_snapshot' => [
                    'manual' => true,
                    'created_by' => $actor->id,
                    'created_at' => now()->toIso8601String(),
                ],
                'generated_at' => now(),
                'expires_at' => CarbonImmutable::parse($data['expires_at']),
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            foreach ($targets as $sequence => $price) {
                $signal->targets()->create([
                    'sequence' => $sequence,
                    'price' => $price,
                    'status' => 'pending',
                ]);
            }

            $this->event($signal, $actor, 'manual_created', [
                'source' => 'admin_manual',
                'direction' => $signal->direction,
                'timeframe' => $signal->timeframe,
                'targets' => count($targets),
            ]);

            return $signal->fresh(['stock', 'marketInstrument', 'targets']);
        }, 3);
    }

    public function updateTerms(Signal $signal, array $data, User $actor): ?SignalRevision
    {
        $signal->loadMissing('targets');

        if (! in_array($signal->status, ['ready', 'published'], true)) {
            throw new RuntimeException('Only ready or published Signals may be manually edited. Active and terminal Signals are locked.');
        }

        $this->assertManualContract($data, $signal);

        if ($signal->status === 'published') {
            if (strtolower((string) $data['direction']) !== strtolower((string) $signal->direction)) {
                throw new RuntimeException('A published Signal direction cannot be changed. Cancel it and create a new Signal instead.');
            }
            if (strtolower((string) $data['timeframe']) !== strtolower((string) $signal->timeframe)) {
                throw new RuntimeException('A published Signal timeframe cannot be changed. Cancel it and create a new Signal instead.');
            }
        }

        $targets = $this->targetsFromData($data);
        $proposal = [
            'direction' => strtolower((string) $data['direction']),
            'timeframe' => strtolower((string) $data['timeframe']),
            'strength' => strtolower((string) ($data['strength'] ?? $signal->strength ?? 'moderate')),
            'confluence_score' => isset($data['confluence_score']) && $data['confluence_score'] !== ''
                ? (float) $data['confluence_score']
                : null,
            'entry_min' => (float) $data['entry_min'],
            'entry_max' => (float) $data['entry_max'],
            'stop_loss' => (float) $data['stop_loss'],
            'risk_reward' => $this->deriveRiskReward($data, $targets),
            'rationale' => filled($data['rationale'] ?? null) ? trim((string) $data['rationale']) : null,
            'expires_at' => CarbonImmutable::parse($data['expires_at']),
            'targets' => $targets,
        ];

        return $this->revisions->apply(
            $signal,
            $proposal,
            trim((string) ($data['revision_reason'] ?? 'Administrator updated Signal terms.')),
            null,
            'admin_manual',
            $actor->id
        );
    }

    public function publish(Signal $signal, User $actor): Signal
    {
        return DB::transaction(function () use ($signal, $actor) {
            $locked = Signal::query()->with(['targets', 'marketInstrument'])->lockForUpdate()->findOrFail($signal->id);

            if ($locked->status !== 'ready') {
                throw new RuntimeException('Only a ready Signal can be published.');
            }
            if ($locked->marketInstrument?->isForex()) {
                throw new RuntimeException('FX2 Forex Signals remain in READY review state until live spot-price lifecycle authority is installed.');
            }
            if ($locked->expires_at && $locked->expires_at->lte(now())) {
                throw new RuntimeException('This Signal has already expired and cannot be published.');
            }
            if ($locked->targets->isEmpty()) {
                throw new RuntimeException('A Signal must have at least one target before publication.');
            }

            $locked->update([
                'status' => 'published',
                'published_at' => now(),
                'updated_by_user_id' => $actor->id,
            ]);

            $this->event($locked, $actor, 'published', [
                'direction' => $locked->direction,
                'timeframe' => $locked->timeframe,
                'entry_min' => (float) $locked->entry_min,
                'entry_max' => (float) $locked->entry_max,
            ]);

            return $locked->fresh(['stock', 'marketInstrument', 'targets']);
        }, 3);
    }

    public function cancel(Signal $signal, User $actor, ?string $reason = null): Signal
    {
        return DB::transaction(function () use ($signal, $actor, $reason) {
            $locked = Signal::query()->lockForUpdate()->findOrFail($signal->id);

            if (! in_array($locked->status, Signal::OPEN_STATUSES, true)) {
                throw new RuntimeException('Terminal Signals cannot be cancelled again.');
            }

            $locked->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'closed_at' => now(),
                'updated_by_user_id' => $actor->id,
            ]);

            $this->event($locked, $actor, 'cancelled', [
                'reason' => filled($reason) ? trim((string) $reason) : 'Cancelled by administrator.',
            ]);

            return $locked->fresh(['stock', 'marketInstrument', 'targets']);
        }, 3);
    }

    public function close(Signal $signal, User $actor, ?string $reason = null): Signal
    {
        return DB::transaction(function () use ($signal, $actor, $reason) {
            $locked = Signal::query()->lockForUpdate()->findOrFail($signal->id);

            if (! in_array($locked->status, ['published', 'active'], true)) {
                throw new RuntimeException('Only published or active Signals may be manually closed. Use Cancel for a ready Signal.');
            }

            $locked->update([
                'status' => 'closed',
                'closed_at' => now(),
                'updated_by_user_id' => $actor->id,
            ]);

            $this->event($locked, $actor, 'closed', [
                'reason' => filled($reason) ? trim((string) $reason) : 'Closed by administrator.',
                'manual' => true,
            ]);

            return $locked->fresh(['stock', 'marketInstrument', 'targets']);
        }, 3);
    }

    private function assertManualContract(array $data, ?Signal $signal = null): void
    {
        $entryMin = (float) ($data['entry_min'] ?? 0);
        $entryMax = (float) ($data['entry_max'] ?? 0);
        $stop = (float) ($data['stop_loss'] ?? 0);
        $direction = strtolower((string) ($data['direction'] ?? $signal?->direction ?? ''));
        $targets = $this->targetsFromData($data);

        if ($entryMin <= 0 || $entryMax <= 0 || $stop <= 0) {
            throw new RuntimeException('Entry and stop-loss values must be greater than zero.');
        }
        if ($entryMin > $entryMax) {
            throw new RuntimeException('Entry minimum cannot be above entry maximum.');
        }
        if (! in_array($direction, ['buy', 'sell'], true)) {
            throw new RuntimeException('Signal direction must be BUY or SELL.');
        }
        if (! $targets) {
            throw new RuntimeException('At least one take-profit target is required.');
        }

        $last = null;
        foreach ($targets as $price) {
            if ($price <= 0) {
                throw new RuntimeException('Take-profit targets must be greater than zero.');
            }

            if ($direction === 'buy') {
                if ($stop >= $entryMin) {
                    throw new RuntimeException('BUY stop loss must sit below the entry zone.');
                }
                if ($price <= $entryMax) {
                    throw new RuntimeException('BUY targets must sit above the entry zone.');
                }
                if ($last !== null && $price <= $last) {
                    throw new RuntimeException('BUY targets must increase from TP1 onward.');
                }
            } else {
                if ($stop <= $entryMax) {
                    throw new RuntimeException('SELL stop loss must sit above the entry zone.');
                }
                if ($price >= $entryMin) {
                    throw new RuntimeException('SELL targets must sit below the entry zone.');
                }
                if ($last !== null && $price >= $last) {
                    throw new RuntimeException('SELL targets must decrease from TP1 onward.');
                }
            }

            $last = $price;
        }

        $expiresAt = CarbonImmutable::parse($data['expires_at']);
        if ($expiresAt->lte(now())) {
            throw new RuntimeException('Signal expiry must be in the future.');
        }
    }

    private function targetsFromData(array $data): array
    {
        $targets = [];
        foreach (['tp1', 'tp2', 'tp3'] as $index => $key) {
            if (! isset($data[$key]) || $data[$key] === '' || $data[$key] === null) {
                continue;
            }
            $targets[$index + 1] = (float) $data[$key];
        }

        return $targets;
    }

    private function deriveRiskReward(array $data, array $targets): float
    {
        $entryAnchor = ((float) $data['entry_min'] + (float) $data['entry_max']) / 2;
        $risk = abs($entryAnchor - (float) $data['stop_loss']);
        $primary = $targets[2] ?? $targets[1] ?? reset($targets);

        if ($risk <= 0 || ! $primary) {
            return 0;
        }

        return round(abs((float) $primary - $entryAnchor) / $risk, 4);
    }

    private function event(Signal $signal, User $actor, string $type, array $payload = []): void
    {
        SignalEvent::create([
            'signal_id' => $signal->id,
            'actor_user_id' => $actor->id,
            'type' => $type,
            'payload' => $payload,
            'occurred_at' => now(),
        ]);
    }
}
