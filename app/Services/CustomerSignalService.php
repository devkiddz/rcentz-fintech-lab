<?php

namespace App\Services;

use App\Models\Signal;
use App\Models\SignalDelivery;
use App\Models\SignalEvent;
use App\Models\SignalRevision;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CustomerSignalService
{
    public function current(User $user, int $perPage = 12): LengthAwarePaginator
    {
        return $this->baseQuery($user)
            ->whereHas('signal', fn ($query) => $query->whereIn('status', ['published', 'active']))
            ->orderByDesc('delivered_at')
            ->paginate($perPage);
    }

    public function history(User $user, int $perPage = 12): LengthAwarePaginator
    {
        return $this->baseQuery($user)
            ->whereHas('signal', fn ($query) => $query->whereIn('status', Signal::TERMINAL_STATUSES))
            ->orderByDesc('delivered_at')
            ->paginate($perPage);
    }

    public function dashboard(User $user, int $limit = 3): Collection
    {
        return $this->baseQuery($user)
            ->whereHas('signal', fn ($query) => $query->whereIn('status', ['published', 'active']))
            ->orderByDesc('delivered_at')
            ->limit(max(1, min(6, $limit)))
            ->get();
    }

    public function summary(User $user): array
    {
        $query = SignalDelivery::query()->where('user_id', $user->id);

        return [
            'current' => (clone $query)
                ->whereHas('signal', fn ($signal) => $signal->whereIn('status', ['published', 'active']))
                ->count(),
            'active' => (clone $query)
                ->whereHas('signal', fn ($signal) => $signal->where('status', 'active'))
                ->count(),
            'waiting' => (clone $query)
                ->whereHas('signal', fn ($signal) => $signal->where('status', 'published'))
                ->count(),
            'history' => (clone $query)
                ->whereHas('signal', fn ($signal) => $signal->whereIn('status', Signal::TERMINAL_STATUSES))
                ->count(),
            'unread_deliveries' => (clone $query)->whereNull('read_at')->count(),
        ];
    }

    public function deliveryFor(User $user, Signal $signal): SignalDelivery
    {
        return $this->baseQuery($user)
            ->where('signal_id', $signal->id)
            ->firstOrFail();
    }

    /**
     * Customer-safe Signal history. SignalEvent/SignalRevision remain the
     * persistence authority; this method only turns them into readable timeline
     * entries and intentionally omits internal analysis snapshots/actor ids.
     */
    public function timeline(Signal $signal): Collection
    {
        $signal->loadMissing([
            'events.target',
            'revisions',
        ]);

        $revisions = $signal->revisions->keyBy('revision_number');

        return $signal->events
            ->sortByDesc(fn (SignalEvent $event) => $event->occurred_at?->getTimestamp() ?? 0)
            ->values()
            ->map(function (SignalEvent $event) use ($revisions) {
                $revisionNumber = (int) data_get($event->payload, 'revision_number', 0);
                $revision = $revisionNumber > 0 ? $revisions->get($revisionNumber) : null;

                return [
                    'id' => $event->id,
                    'type' => $event->type,
                    'title' => $this->timelineTitle($event),
                    'summary' => $this->timelineSummary($event, $revision),
                    'icon' => $this->timelineIcon($event->type),
                    'tone' => $this->timelineTone($event->type),
                    'badge' => $revision ? 'Revision #'.$revision->revision_number : null,
                    'occurred_at' => $event->occurred_at,
                    'changes' => $revision ? $this->revisionChanges($revision) : [],
                ];
            });
    }

    private function timelineTitle(SignalEvent $event): string
    {
        return match ($event->type) {
            'generated' => 'Signal generated',
            'manual_created' => 'Signal created',
            'published' => 'Signal published',
            'activated' => 'Entry condition reached',
            'adjusted' => 'Signal terms updated',
            'target_hit' => 'Target '.((int) (data_get($event->payload, 'sequence') ?? $event->target?->sequence ?? 0)).' reached',
            'stopped' => 'Stop loss reached',
            'expired' => 'Signal expired',
            'invalidated' => 'Signal invalidated',
            'cancelled' => 'Signal cancelled',
            'closed' => 'Signal closed',
            'strengthened' => 'Analysis strengthened',
            'weakened' => 'Analysis weakened',
            default => ucwords(str_replace('_', ' ', (string) $event->type)),
        };
    }

    private function timelineSummary(SignalEvent $event, ?SignalRevision $revision): string
    {
        $payload = (array) ($event->payload ?? []);
        $marketPrice = data_get($payload, 'market_price');
        $targetPrice = data_get($payload, 'target_price') ?? $event->target?->price;
        $reason = trim((string) data_get($payload, 'reason', ''));

        return match ($event->type) {
            'generated' => 'The Signal Intelligence Engine qualified this setup and created the Signal contract.',
            'manual_created' => 'An administrator created this Signal contract for review.',
            'published' => 'The Signal was approved for customer distribution.',
            'activated' => $marketPrice !== null
                ? 'The market reached the entry condition near '.number_format((float) $marketPrice, 2).' and the Signal became active.'
                : 'The market reached the entry condition and the Signal became active.',
            'adjusted' => $revision?->reason
                ?: ($reason !== '' ? $reason : 'Fresh market analysis materially updated the live Signal contract.'),
            'target_hit' => $targetPrice !== null
                ? 'The market reached this target near '.number_format((float) $targetPrice, 2).'.'
                : 'A configured profit target was reached.',
            'stopped' => $marketPrice !== null
                ? 'The stop-loss condition was reached near '.number_format((float) $marketPrice, 2).'.'
                : 'The stop-loss condition was reached.',
            'expired' => 'The Signal reached its expiry window before completing its remaining lifecycle.',
            'invalidated' => 'Fresh analysis no longer supported the original market direction, so the Signal was invalidated.',
            'cancelled' => $reason !== '' ? $reason : 'The Signal was cancelled by an administrator.',
            'closed' => $reason === 'all_targets_hit'
                ? 'All configured profit targets were reached.'
                : ($reason !== '' ? $reason : 'The Signal lifecycle was closed.'),
            'strengthened' => 'Fresh analysis increased the evidence supporting the existing Signal direction.',
            'weakened' => 'Fresh analysis reduced the evidence supporting the existing Signal direction.',
            default => 'The Signal lifecycle recorded a new event.',
        };
    }

    private function timelineIcon(string $type): string
    {
        return match ($type) {
            'generated', 'manual_created' => 'sparkles',
            'published' => 'send',
            'activated' => 'play',
            'adjusted' => 'sliders-horizontal',
            'target_hit' => 'target',
            'stopped' => 'shield-x',
            'expired' => 'clock-alert',
            'invalidated' => 'ban',
            'cancelled' => 'circle-x',
            'closed' => 'circle-check-big',
            'strengthened' => 'trending-up',
            'weakened' => 'trending-down',
            default => 'activity',
        };
    }

    private function timelineTone(string $type): string
    {
        return match ($type) {
            'target_hit', 'closed', 'strengthened' => 'positive',
            'stopped', 'invalidated', 'cancelled' => 'negative',
            'adjusted', 'weakened', 'expired' => 'warning',
            default => 'neutral',
        };
    }

    private function revisionChanges(SignalRevision $revision): array
    {
        $before = (array) $revision->previous_values;
        $after = (array) $revision->new_values;
        $changes = [];

        $simple = [
            'direction' => 'Direction',
            'timeframe' => 'Timeframe',
            'strength' => 'Strength',
            'confluence_score' => 'Confluence',
            'entry_min' => 'Entry minimum',
            'entry_max' => 'Entry maximum',
            'stop_loss' => 'Stop loss',
            'risk_reward' => 'Risk : Reward',
            'expires_at' => 'Expiry',
        ];

        foreach ($simple as $key => $label) {
            if (($before[$key] ?? null) == ($after[$key] ?? null)) {
                continue;
            }

            $changes[] = [
                'label' => $label,
                'before' => $this->formatRevisionValue($key, $before[$key] ?? null),
                'after' => $this->formatRevisionValue($key, $after[$key] ?? null),
            ];
        }

        $beforeTargets = (array) ($before['targets'] ?? []);
        $afterTargets = (array) ($after['targets'] ?? []);
        foreach ($afterTargets as $sequence => $price) {
            if (array_key_exists($sequence, $beforeTargets)
                && abs((float) $beforeTargets[$sequence] - (float) $price) <= 0.00000001) {
                continue;
            }

            $changes[] = [
                'label' => 'Target '.(int) $sequence,
                'before' => array_key_exists($sequence, $beforeTargets)
                    ? number_format((float) $beforeTargets[$sequence], 2)
                    : '—',
                'after' => number_format((float) $price, 2),
            ];
        }

        return $changes;
    }

    private function formatRevisionValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($key) {
            'strength', 'direction', 'timeframe' => strtoupper(str_replace('_', ' ', (string) $value)),
            'confluence_score' => number_format((float) $value, 2).'%',
            'entry_min', 'entry_max', 'stop_loss' => number_format((float) $value, 2),
            'risk_reward' => '1 : '.rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.'),
            'expires_at' => date('M d, Y · H:i', strtotime((string) $value)),
            default => (string) $value,
        };
    }

    private function baseQuery(User $user)
    {
        return SignalDelivery::query()
            ->where('user_id', $user->id)
            ->with([
                'distribution',
                'signal.stock',
                'signal.marketInstrument.stock',
                'signal.marketInstrument.forexPair',
                'signal.targets',
            ]);
    }
}
