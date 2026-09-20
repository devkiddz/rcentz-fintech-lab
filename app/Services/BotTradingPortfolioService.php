<?php

namespace App\Services;

use App\Models\TradePosition;
use App\Models\TradingBot;
use App\Models\TradingBotExecution;
use App\Models\User;

class BotTradingPortfolioService
{
    public function __construct(
        private MarketPriceRouter $prices,
        private MarketSettlementService $settlement
    ) {}

    public function buildForUser(User $user): array
    {
        $bots = TradingBot::query()
            ->with(['stock', 'marketInstrument', 'subscription'])
            ->where('user_id', $user->id)
            ->latest('id')
            ->get();

        $botIds = $bots->pluck('id');

        $positions = $botIds->isEmpty()
            ? collect()
            : TradePosition::query()
                ->with([
                    'stock.marketInstrument',
                    'marketInstrument',
                    'user.wallet',
                    'entryMarketExecutionTransaction',
                ])
                ->where('user_id', $user->id)
                ->where('context_type', 'trading_bot')
                ->whereIn('context_id', $botIds)
                ->latest('opened_at')
                ->latest('id')
                ->get();

        $positionsByBot = $positions->groupBy('context_id');

        $executions = $botIds->isEmpty()
            ? collect()
            : TradingBotExecution::query()
                ->with(['bot.stock', 'bot.marketInstrument', 'marketInstrument', 'subscription', 'brokerOrder', 'marketExecution'])
                ->whereIn('trading_bot_id', $botIds)
                ->latest('executed_at')
                ->latest('id')
                ->get();

        $items = $bots->map(function (TradingBot $bot) use ($positionsByBot, $executions) {
            $botPositions = $positionsByBot->get($bot->id, collect());

            $positionItems = $botPositions->map(function (TradePosition $position) {
                $basis = $this->positionBasis($position);
                $initialQty = max((float) $position->initial_quantity, 0.00000001);
                $openQty = max((float) $position->open_quantity, 0.0);
                $openCapital = round($basis * min(1, $openQty / $initialQty), 2);
                $realized = round((float) $position->realized_profit_loss, 2);
                $unrealized = round($this->openPositionPnl($position), 2);
                $net = round($realized + $unrealized, 2);
                $instrument = $position->marketInstrument ?? $position->stock?->marketInstrument;

                return [
                    'position' => $position,
                    'instrument' => $instrument,
                    'stock' => $position->stock,
                    'initial_capital' => round($basis, 2),
                    'open_capital' => $openCapital,
                    'realized_profit_loss' => $realized,
                    'unrealized_profit_loss' => $unrealized,
                    'net_profit_loss' => $net,
                    'return_percent' => $basis > 0 ? round(($net / $basis) * 100, 4) : 0.0,
                ];
            })->values();

            $open = $positionItems->filter(fn (array $item) => $item['position']->is_open);
            $closed = $positionItems->filter(fn (array $item) => ! $item['position']->is_open);

            $capital = round((float) $positionItems->sum('initial_capital'), 2);
            $realized = round((float) $positionItems->sum('realized_profit_loss'), 2);
            $unrealized = round((float) $positionItems->sum('unrealized_profit_loss'), 2);
            $net = round((float) $positionItems->sum('net_profit_loss'), 2);

            $botExecutions = $executions->where('trading_bot_id', $bot->id)->values();

            return [
                'bot' => $bot,
                'instrument' => $bot->marketInstrument ?? $bot->stock?->marketInstrument,
                'subscription' => $bot->subscription,
                'positions' => $positionItems,
                'executions' => $botExecutions,
                'capital_traded' => $capital,
                'open_capital' => round((float) $open->sum('open_capital'), 2),
                'realized_profit_loss' => $realized,
                'unrealized_profit_loss' => $unrealized,
                'net_profit_loss' => $net,
                'return_percent' => $capital > 0 ? round(($net / $capital) * 100, 4) : 0.0,
                'open_positions' => $open->count(),
                'closed_positions' => $closed->count(),
                'wins' => $closed->filter(fn (array $item) => $item['realized_profit_loss'] > 0)->count(),
                'losses' => $closed->filter(fn (array $item) => $item['realized_profit_loss'] < 0)->count(),
            ];
        })->values();

        $allPositionItems = $items->flatMap(fn (array $item) => $item['positions']);

        $capital = round((float) $items->sum('capital_traded'), 2);
        $realized = round((float) $items->sum('realized_profit_loss'), 2);
        $unrealized = round((float) $items->sum('unrealized_profit_loss'), 2);
        $net = round((float) $items->sum('net_profit_loss'), 2);

        $summary = [
            'bots' => $items->count(),
            'active_bots' => $items->filter(fn (array $item) => $item['bot']->is_active)->count(),
            'positions' => $allPositionItems->count(),
            'open_positions' => (int) $items->sum('open_positions'),
            'closed_positions' => (int) $items->sum('closed_positions'),
            'capital_traded' => $capital,
            'open_capital' => round((float) $items->sum('open_capital'), 2),
            'realized_profit_loss' => $realized,
            'unrealized_profit_loss' => $unrealized,
            'net_profit_loss' => $net,
            'return_percent' => $capital > 0 ? round(($net / $capital) * 100, 4) : 0.0,
            'wins' => (int) $items->sum('wins'),
            'losses' => (int) $items->sum('losses'),
            'executions' => $executions->count(),
        ];

        return [
            'bots' => $items,
            'summary' => $summary,
            'activity' => $executions->take(12)->values(),
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
