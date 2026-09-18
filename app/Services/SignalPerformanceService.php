<?php

namespace App\Services;

use App\Models\Signal;
use App\Models\SignalTarget;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class SignalPerformanceService
{
    public function platform(int $recentLimit = 10): array
    {
        return $this->summarize($this->signalQuery(), $recentLimit);
    }

    public function forUser(User $user, int $recentLimit = 10): array
    {
        return $this->summarize(
            $this->signalQuery()->whereHas('deliveries', fn (Builder $query) => $query->where('user_id', $user->id)),
            $recentLimit
        );
    }

    private function summarize(Builder $base, int $recentLimit): array
    {
        $recentLimit = max(1, min(50, $recentLimit));

        $total = (clone $base)->count();
        $open = (clone $base)->whereIn('status', Signal::OPEN_STATUSES)->count();
        $terminal = (clone $base)->whereIn('status', Signal::TERMINAL_STATUSES)->count();
        $active = (clone $base)->where('status', 'active')->count();
        $closed = (clone $base)->where('status', 'closed')->count();
        $stopped = (clone $base)->where('status', 'stopped')->count();
        $expired = (clone $base)->where('status', 'expired')->count();
        $invalidated = (clone $base)->where('status', 'invalidated')->count();
        $cancelled = (clone $base)->where('status', 'cancelled')->count();
        $resolved = $closed + $stopped;

        $signalIds = (clone $base)->pluck('id');
        $targets = SignalTarget::query()->whereIn('signal_id', $signalIds)->count();
        $targetsHit = SignalTarget::query()
            ->whereIn('signal_id', $signalIds)
            ->where('status', 'hit')
            ->count();

        $averageConfluence = (clone $base)->whereNotNull('confluence_score')->avg('confluence_score');

        $recent = (clone $base)
            ->with(['stock', 'marketInstrument.stock', 'marketInstrument.forexPair', 'targets'])
            ->whereIn('status', Signal::TERMINAL_STATUSES)
            ->orderByRaw('COALESCE(closed_at, invalidated_at, cancelled_at, updated_at) DESC')
            ->limit($recentLimit)
            ->get()
            ->map(fn (Signal $signal) => [
                'signal_id' => $signal->id,
                'symbol' => $signal->instrument_symbol,
                'asset_class' => $signal->asset_class,
                'direction' => $signal->direction,
                'timeframe' => $signal->timeframe,
                'status' => $signal->status,
                'targets_hit' => $signal->targets->where('status', 'hit')->count(),
                'targets_total' => $signal->targets->count(),
                'confluence_score' => $signal->confluence_score !== null ? (float) $signal->confluence_score : null,
                'ended_at' => $signal->closed_at
                    ?? $signal->invalidated_at
                    ?? $signal->cancelled_at
                    ?? $signal->updated_at,
            ])
            ->values()
            ->all();

        return [
            'total' => $total,
            'open' => $open,
            'active' => $active,
            'terminal' => $terminal,
            'closed' => $closed,
            'stopped' => $stopped,
            'expired' => $expired,
            'invalidated' => $invalidated,
            'cancelled' => $cancelled,
            'resolved' => $resolved,
            'resolved_success_rate' => $resolved > 0 ? round(($closed / $resolved) * 100, 2) : null,
            'targets_total' => $targets,
            'targets_hit' => $targetsHit,
            'target_reach_rate' => $targets > 0 ? round(($targetsHit / $targets) * 100, 2) : null,
            'average_confluence' => $averageConfluence !== null ? round((float) $averageConfluence, 2) : null,
            'recent_terminal' => $recent,
        ];
    }

    private function signalQuery(): Builder
    {
        return Signal::query();
    }
}
