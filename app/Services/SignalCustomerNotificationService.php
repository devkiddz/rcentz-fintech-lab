<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\SignalEvent;
use App\Models\SignalRevision;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class SignalCustomerNotificationService
{
    public const MATERIAL_EVENT_TYPES = [
        'activated',
        'adjusted',
        'target_hit',
        'stopped',
        'expired',
        'invalidated',
        'cancelled',
        'closed',
    ];

    /**
     * Fan a material Signal event out to customers who already own the Signal
     * through SignalDelivery. SignalDelivery remains the access authority;
     * notifications are alerts only.
     */
    public function notifyForEvent(SignalEvent $event, bool $dryRun = false): array
    {
        $stats = [
            'event_id' => $event->id,
            'event_type' => $event->type,
            'deliveries' => 0,
            'created' => 0,
            'would_create' => 0,
            'existing' => 0,
            'failed' => 0,
        ];

        if (! in_array($event->type, self::MATERIAL_EVENT_TYPES, true)) {
            return $stats;
        }

        $event->loadMissing(['signal.stock', 'signal.marketInstrument.stock', 'signal.marketInstrument.forexPair', 'signal.targets', 'target']);
        $signal = $event->signal;

        if (! $signal) {
            return $stats;
        }

        $deliveries = $signal->deliveries()
            ->with('user')
            ->orderBy('id')
            ->get();

        $stats['deliveries'] = $deliveries->count();

        if ($deliveries->isEmpty()) {
            return $stats;
        }

        $revision = $this->revisionForEvent($event);
        [$title, $message] = $this->content($event, $revision);

        foreach ($deliveries as $delivery) {
            $user = $delivery->user;
            if (! $user) {
                $stats['failed']++;
                continue;
            }

            try {
                if ($this->alreadyNotified((int) $user->id, (int) $event->id)) {
                    $stats['existing']++;
                    continue;
                }

                if ($dryRun) {
                    $stats['would_create']++;
                    continue;
                }

                $user->notifications()->create([
                    'type' => 'signal',
                    'title' => $title,
                    'message' => $message,
                    'data' => $this->data($event, $revision),
                ]);

                $stats['created']++;
            } catch (\Throwable $e) {
                $stats['failed']++;
                Log::warning('Signal customer lifecycle notification failed.', [
                    'signal_id' => $signal->id,
                    'signal_event_id' => $event->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    public function materialEventTypes(): array
    {
        return self::MATERIAL_EVENT_TYPES;
    }

    private function alreadyNotified(int $userId, int $eventId): bool
    {
        try {
            return Notification::query()
                ->where('user_id', $userId)
                ->where('type', 'signal')
                ->where('data->signal_event_id', $eventId)
                ->exists();
        } catch (\Throwable) {
            // MariaDB/MySQL JSON grammar varies across local environments. The
            // fallback keeps recovery idempotent without requiring a migration.
            return Notification::query()
                ->where('user_id', $userId)
                ->where('type', 'signal')
                ->latest('id')
                ->limit(250)
                ->get(['id', 'data'])
                ->contains(fn (Notification $notification) =>
                    (int) data_get($notification->data, 'signal_event_id', 0) === $eventId
                );
        }
    }

    private function revisionForEvent(SignalEvent $event): ?SignalRevision
    {
        if ($event->type !== 'adjusted') {
            return null;
        }

        $revisionNumber = (int) data_get($event->payload, 'revision_number', 0);
        if ($revisionNumber <= 0) {
            return null;
        }

        return SignalRevision::query()
            ->where('signal_id', $event->signal_id)
            ->where('revision_number', $revisionNumber)
            ->first();
    }

    private function content(SignalEvent $event, ?SignalRevision $revision): array
    {
        $signal = $event->signal;
        $symbol = strtoupper((string) ($signal?->instrument_symbol ?? 'Signal'));
        $direction = strtoupper((string) ($signal?->direction ?? ''));
        $price = data_get($event->payload, 'market_price');
        $targetSequence = (int) (data_get($event->payload, 'sequence') ?? $event->target?->sequence ?? 0);
        $targetPrice = data_get($event->payload, 'target_price') ?? $event->target?->price;
        $reason = trim((string) data_get($event->payload, 'reason', ''));

        return match ($event->type) {
            'activated' => [
                "{$symbol} Signal is now active",
                $price !== null
                    ? "The {$direction} entry condition was reached near ".number_format((float) $price, 2).'. Monitor the live Signal terms.'
                    : "The {$direction} entry condition was reached. The Signal is now active.",
            ],
            'adjusted' => [
                "{$symbol} Signal terms updated",
                $this->revisionMessage($revision, $reason),
            ],
            'target_hit' => [
                "{$symbol} Target {$targetSequence} reached",
                $targetPrice !== null
                    ? 'TP'.$targetSequence.' at '.number_format((float) $targetPrice, 2).' was reached. Review the remaining live targets.'
                    : 'A configured profit target was reached. Review the remaining live targets.',
            ],
            'stopped' => [
                "{$symbol} Signal stopped",
                'The stop-loss condition was reached'.($price !== null ? ' near '.number_format((float) $price, 2) : '').'. The Signal is now stopped.',
            ],
            'expired' => [
                "{$symbol} Signal expired",
                'The Signal reached its expiry window before completing its remaining lifecycle.',
            ],
            'invalidated' => [
                "{$symbol} Signal invalidated",
                'Fresh market analysis no longer supports the original '.$direction.' setup. The Signal has been invalidated.',
            ],
            'cancelled' => [
                "{$symbol} Signal cancelled",
                $reason !== '' ? $reason : 'The Signal was cancelled by an administrator.',
            ],
            'closed' => [
                "{$symbol} Signal closed",
                $reason === 'all_targets_hit'
                    ? 'All configured profit targets were reached and the Signal is now closed.'
                    : ($reason !== '' ? $reason : 'The Signal lifecycle has been closed.'),
            ],
            default => ["{$symbol} Signal update", 'The Signal lifecycle was updated.'],
        };
    }

    private function revisionMessage(?SignalRevision $revision, string $fallbackReason): string
    {
        if (! $revision) {
            return $fallbackReason !== ''
                ? $fallbackReason
                : 'Market re-analysis materially updated the Signal terms. Review the latest levels and strength.';
        }

        $before = (array) $revision->previous_values;
        $after = (array) $revision->new_values;
        $parts = [];

        if (($before['strength'] ?? null) !== ($after['strength'] ?? null)) {
            $parts[] = 'strength '.strtoupper(str_replace('_', ' ', (string) ($before['strength'] ?? '—')))
                .' → '.strtoupper(str_replace('_', ' ', (string) ($after['strength'] ?? '—')));
        }

        if (($before['confluence_score'] ?? null) !== ($after['confluence_score'] ?? null)) {
            $parts[] = 'confluence '.number_format((float) ($before['confluence_score'] ?? 0), 2).'% → '
                .number_format((float) ($after['confluence_score'] ?? 0), 2).'%';
        }

        if (($before['stop_loss'] ?? null) !== ($after['stop_loss'] ?? null)) {
            $parts[] = 'SL '.number_format((float) ($before['stop_loss'] ?? 0), 2).' → '
                .number_format((float) ($after['stop_loss'] ?? 0), 2);
        }

        $beforeTargets = (array) ($before['targets'] ?? []);
        $afterTargets = (array) ($after['targets'] ?? []);
        $changedTargets = collect($afterTargets)
            ->filter(fn ($price, $sequence) =>
                ! array_key_exists($sequence, $beforeTargets)
                || abs((float) $beforeTargets[$sequence] - (float) $price) > 0.00000001
            )
            ->count();

        if ($changedTargets > 0) {
            $parts[] = $changedTargets.' target'.($changedTargets === 1 ? '' : 's').' revised';
        }

        if ($parts) {
            return 'Market re-analysis updated the Signal: '.implode(', ', $parts).'. Review the latest contract.';
        }

        return $revision->reason ?: ($fallbackReason !== '' ? $fallbackReason : 'The Signal contract was materially updated.');
    }

    private function data(SignalEvent $event, ?SignalRevision $revision): array
    {
        $signal = $event->signal;
        $symbol = strtoupper((string) ($signal?->instrument_symbol ?? 'Signal'));

        return [
            'signal_id' => $signal?->id,
            'signal_event_id' => $event->id,
            'event_type' => $event->type,
            'revision_number' => $revision?->revision_number,
            'change_source' => $revision?->change_source,
            'symbol' => $symbol,
            'direction' => $signal?->direction,
            'status' => $signal?->status,
            'timeframe' => $signal?->timeframe,
            'target_sequence' => data_get($event->payload, 'sequence') ?? $event->target?->sequence,
            'target_price' => data_get($event->payload, 'target_price') ?? $event->target?->price,
            'market_price' => data_get($event->payload, 'market_price'),
            'occurred_at' => $event->occurred_at?->toIso8601String(),
            'action_url' => Route::has('signals.show') && $signal
                ? route('signals.show', $signal->id, false)
                : null,
        ];
    }
}
