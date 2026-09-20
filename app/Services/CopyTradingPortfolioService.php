<?php

namespace App\Services;

use App\Models\CopyRelationship;
use App\Models\TradePosition;
use App\Models\User;

class CopyTradingPortfolioService
{
    public function __construct(
        private MarketPriceRouter $prices,
        private MarketSettlementService $settlement
    ) {}

    public function buildForUser(User $user): array
    {
        $positions = TradePosition::query()
            ->with([
                'stock.marketInstrument',
                'marketInstrument',
                'user.wallet',
                'entryMarketExecutionTransaction',
            ])
            ->where('user_id', $user->id)
            ->where('context_type', 'copy_relationship')
            ->latest('opened_at')
            ->latest('id')
            ->get();

        $relationshipIds = $positions->pluck('context_id')->filter()->unique()->values();

        $relationships = $relationshipIds->isEmpty()
            ? collect()
            : CopyRelationship::query()
                ->with(['provider', 'strategy.profile.user'])
                ->where('follower_id', $user->id)
                ->whereIn('id', $relationshipIds)
                ->get()
                ->keyBy('id');

        $items = $positions->map(function (TradePosition $position) use ($relationships) {
            $relationship = $position->context_id
                ? $relationships->get((int) $position->context_id)
                : null;
            $strategy = $relationship?->strategy;
            $instrument = $position->marketInstrument ?? $position->stock?->marketInstrument;
            $basis = $this->positionBasis($position);
            $initialQty = max((float) $position->initial_quantity, 0.00000001);
            $openQty = max((float) $position->open_quantity, 0.0);
            $openCapital = round($basis * min(1, $openQty / $initialQty), 2);
            $realized = round((float) $position->realized_profit_loss, 2);
            $unrealized = round($this->openPositionPnl($position), 2);
            $net = round($realized + $unrealized, 2);

            return [
                'position' => $position,
                'instrument' => $instrument,
                'stock' => $position->stock,
                'strategy' => $strategy,
                'relationship' => $relationship,
                'initial_capital' => round($basis, 2),
                'open_capital' => $openCapital,
                'realized_profit_loss' => $realized,
                'unrealized_profit_loss' => $unrealized,
                'net_profit_loss' => $net,
                'return_percent' => $basis > 0 ? round(($net / $basis) * 100, 4) : 0.0,
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

        return [
            'positions' => $items,
            'summary' => [
                'positions' => $items->count(),
                'open_positions' => $open->count(),
                'closed_positions' => $closed->count(),
                'strategies' => $uniqueRelationships->pluck('copy_strategy_id')->filter()->unique()->count(),
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
            ],
        ];
    }

    private function positionBasis(TradePosition $position): float
    {
        if ($position->entryMarketExecutionTransaction) {
            return (float) ($position->entryMarketExecutionTransaction->settlement_amount
                ?? $position->entryMarketExecutionTransaction->gross_value
                ?? 0);
        }

        return (float) $position->entry_price * (float) $position->initial_quantity;
    }

    private function openPositionPnl(TradePosition $position): float
    {
        $openQty = (float) $position->open_quantity;
        if ($openQty <= 0) return 0.0;

        $instrument = $position->marketInstrument ?? $position->stock?->marketInstrument;
        if (! $instrument) return 0.0;

        try {
            $current = (float) $this->prices->price($instrument, $position->marketplace ?: null);
            if ($current <= 0) return 0.0;

            $quotePnl = ($current - (float) $position->entry_price) * $openQty;
            if ($instrument->isStock()) return $quotePnl;

            $currency = strtoupper((string) ($position->user?->wallet?->currency ?: 'USD'));
            return $this->settlement->profitLossToSettlement($instrument, $quotePnl, $current, $currency, false);
        } catch (\Throwable) {
            return 0.0;
        }
    }
}
