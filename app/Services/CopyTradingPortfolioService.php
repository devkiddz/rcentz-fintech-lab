<?php

namespace App\Services;

use App\Models\CopyRelationship;
use App\Models\CopyStrategy;
use App\Models\TradePosition;
use App\Models\User;

class CopyTradingPortfolioService
{
    public function buildForUser(User $user): array
    {
        $positions = TradePosition::query()
            ->with('stock')
            ->where('user_id', $user->id)
            ->where('context_type', 'copy_strategy')
            ->latest('opened_at')
            ->latest('id')
            ->get();

        $strategyIds = $positions->pluck('context_id')
            ->filter()
            ->unique()
            ->values();

        $strategies = $strategyIds->isEmpty()
            ? collect()
            : CopyStrategy::query()
                ->with('profile.user')
                ->whereIn('id', $strategyIds)
                ->get()
                ->keyBy('id');

        $relationships = $strategyIds->isEmpty()
            ? collect()
            : CopyRelationship::query()
                ->with(['provider', 'strategy'])
                ->where('follower_id', $user->id)
                ->whereIn('copy_strategy_id', $strategyIds)
                ->latest('started_at')
                ->latest('id')
                ->get()
                ->groupBy('copy_strategy_id')
                ->map(fn ($items) => $items->first());

        $items = $positions->map(function (TradePosition $position) use ($strategies, $relationships) {
            $strategy = $position->context_id
                ? $strategies->get((int) $position->context_id)
                : null;

            $relationship = $position->context_id
                ? $relationships->get((int) $position->context_id)
                : null;

            $initialCapital = round(
                (float) $position->entry_price * (float) $position->initial_quantity,
                2
            );

            $openCapital = round(
                (float) $position->entry_price * (float) $position->open_quantity,
                2
            );

            $realized = round((float) $position->realized_profit_loss, 2);
            $net = round((float) $position->current_profit_loss, 2);
            $unrealized = round($net - $realized, 2);

            return [
                'position' => $position,
                'stock' => $position->stock,
                'strategy' => $strategy,
                'relationship' => $relationship,
                'initial_capital' => $initialCapital,
                'open_capital' => $openCapital,
                'realized_profit_loss' => $realized,
                'unrealized_profit_loss' => $unrealized,
                'net_profit_loss' => $net,
                'return_percent' => round((float) $position->current_return_percent, 4),
            ];
        })->values();

        $open = $items->filter(fn (array $item) => $item['position']->is_open);
        $closed = $items->filter(fn (array $item) => ! $item['position']->is_open);

        $capital = round((float) $items->sum('initial_capital'), 2);
        $realized = round((float) $items->sum('realized_profit_loss'), 2);
        $unrealized = round((float) $items->sum('unrealized_profit_loss'), 2);
        $net = round((float) $items->sum('net_profit_loss'), 2);

        $uniqueRelationships = $relationships->values();
        $allocationLimit = round((float) $uniqueRelationships->sum('allocation_limit'), 2);
        $usedAllocation = round((float) $uniqueRelationships->sum('used_amount'), 2);

        $summary = [
            'positions' => $items->count(),
            'open_positions' => $open->count(),
            'closed_positions' => $closed->count(),
            'strategies' => $strategyIds->count(),
            'capital_traded' => $capital,
            'open_capital' => round((float) $open->sum('open_capital'), 2),
            'allocation_limit' => $allocationLimit,
            'used_allocation' => $usedAllocation,
            'remaining_allocation' => max(0, round($allocationLimit - $usedAllocation, 2)),
            'realized_profit_loss' => $realized,
            'unrealized_profit_loss' => $unrealized,
            'net_profit_loss' => $net,
            'return_percent' => $capital > 0 ? round(($net / $capital) * 100, 4) : 0.0,
            'wins' => $closed->filter(fn (array $item) => $item['realized_profit_loss'] > 0)->count(),
            'losses' => $closed->filter(fn (array $item) => $item['realized_profit_loss'] < 0)->count(),
        ];

        return [
            'positions' => $items,
            'summary' => $summary,
        ];
    }
}
