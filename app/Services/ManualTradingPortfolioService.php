<?php

namespace App\Services;

use App\Models\TradePosition;
use App\Models\TradePositionEvent;
use App\Models\User;

class ManualTradingPortfolioService
{
    public function buildForUser(User $user): array
    {
        $positions = TradePosition::query()
            ->with('stock')
            ->where('user_id', $user->id)
            ->where('context_type', 'manual_trade')
            ->latest('opened_at')
            ->latest('id')
            ->get();

        $positionIds = $positions->pluck('id');

        $events = $positionIds->isEmpty()
            ? collect()
            : TradePositionEvent::query()
                ->with('position.stock')
                ->whereIn('trade_position_id', $positionIds)
                ->latest('created_at')
                ->latest('id')
                ->get();

        $items = $positions->map(function (TradePosition $position) {
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

        $net = round((float) $items->sum('net_profit_loss'), 2);
        $realized = round((float) $items->sum('realized_profit_loss'), 2);
        $unrealized = round((float) $items->sum('unrealized_profit_loss'), 2);
        $capital = round((float) $items->sum('initial_capital'), 2);
        $openCapital = round((float) $open->sum('open_capital'), 2);

        $summary = [
            'positions' => $items->count(),
            'open_positions' => $open->count(),
            'closed_positions' => $closed->count(),
            'capital_traded' => $capital,
            'open_capital' => $openCapital,
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
            'activity' => $events->take(12)->values(),
        ];
    }
}
