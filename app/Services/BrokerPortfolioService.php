<?php

namespace App\Services;

use App\Models\BrokerOrder;
use App\Models\MarketExecutionTransaction;
use App\Models\MarketHolding;
use App\Models\StockHolding;
use App\Models\TradePosition;
use App\Models\User;

final class BrokerPortfolioService
{
    public function __construct(
        private MarketPriceRouter $prices,
        private MarketSettlementService $settlement
    ) {}

    public function build(User $user): array
    {
        $marketplace = $this->prices->activeMarketplace();
        $walletCurrency = strtoupper((string) ($user->wallet?->currency ?: 'USD'));
        $items = collect();

        $stockHoldings = StockHolding::query()
            ->with(['stock', 'marketInstrument'])
            ->where('user_id', $user->id)
            ->where('marketplace', $marketplace)
            ->where('quantity', '>', 0)
            ->get();

        foreach ($stockHoldings as $holding) {
            $instrument = $holding->marketInstrument ?? $holding->stock?->marketInstrument;
            if (! $instrument) {
                continue;
            }

            $quantity = (float) $holding->quantity;
            $invested = (float) $holding->total_invested;
            $markPrice = $this->safePrice($instrument, $marketplace);
            $markValue = $markPrice !== null
                ? round($quantity * $markPrice, 2)
                : (float) $holding->current_value;
            $pnl = $markValue - $invested;

            $items->push([
                'instrument' => $instrument,
                'asset_class' => 'stock',
                'quantity' => $quantity,
                'average_entry_price' => (float) $holding->average_buy_price,
                'total_invested' => $invested,
                'mark_price' => $markPrice,
                'mark_value' => $markValue,
                'profit_loss' => $pnl,
                'return_percent' => $invested > 0 ? ($pnl / $invested) * 100 : 0,
                'settlement_currency' => $walletCurrency,
                'source' => $holding,
            ]);
        }

        $marketHoldings = MarketHolding::query()
            ->with('marketInstrument')
            ->where('user_id', $user->id)
            ->where('marketplace', $marketplace)
            ->where('quantity', '>', 0)
            ->get();

        foreach ($marketHoldings as $holding) {
            $instrument = $holding->marketInstrument;
            if (! $instrument) {
                continue;
            }

            $quantity = (float) $holding->quantity;
            $invested = (float) $holding->total_invested;
            $markPrice = $this->safePrice($instrument, $marketplace);
            $markValue = (float) $holding->current_value;
            $pnl = (float) $holding->unrealized_gain_loss;

            if ($markPrice !== null) {
                try {
                    if ($instrument->isForex()) {
                        $quotePnl = ($markPrice - (float) $holding->average_entry_price) * $quantity;
                        $pnl = $this->settlement->profitLossToSettlement(
                            $instrument,
                            $quotePnl,
                            $markPrice,
                            (string) $holding->settlement_currency,
                            false
                        );
                        $markValue = max(0, $invested + $pnl);
                    } else {
                        $markValue = $this->settlement->amountForBaseUnits(
                            $instrument,
                            $quantity,
                            $markPrice,
                            (string) $holding->settlement_currency,
                            false
                        );
                        $pnl = $markValue - $invested;
                    }
                } catch (\Throwable) {
                    $markValue = (float) $holding->current_value;
                    $pnl = (float) $holding->unrealized_gain_loss;
                }
            }

            $items->push([
                'instrument' => $instrument,
                'asset_class' => $instrument->asset_class,
                'quantity' => $quantity,
                'average_entry_price' => (float) $holding->average_entry_price,
                'total_invested' => $invested,
                'mark_price' => $markPrice,
                'mark_value' => round($markValue, 2),
                'profit_loss' => $pnl,
                'return_percent' => $invested > 0 ? ($pnl / $invested) * 100 : 0,
                'settlement_currency' => strtoupper((string) $holding->settlement_currency),
                'source' => $holding,
            ]);
        }

        $items = $items->sortByDesc('mark_value')->values();
        $totalInvested = (float) $items->sum('total_invested');
        $totalValue = (float) $items->sum('mark_value');
        $totalPnl = $totalValue - $totalInvested;

        $positions = TradePosition::query()
            ->with(['marketInstrument', 'stock.marketInstrument'])
            ->where('user_id', $user->id)
            ->where('marketplace', $marketplace)
            ->whereIn('status', ['open', 'exit_queued'])
            ->where('open_quantity', '>', 0)
            ->latest('opened_at')
            ->get();

        $recentOrders = BrokerOrder::query()
            ->with(['marketInstrument', 'execution'])
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(8)
            ->get();

        $recentExecutions = MarketExecutionTransaction::query()
            ->with('marketInstrument')
            ->where('user_id', $user->id)
            ->latest('executed_at')
            ->latest('id')
            ->limit(8)
            ->get();

        return [
            'marketplace' => $marketplace,
            'walletCurrency' => $walletCurrency,
            'holdings' => $items,
            'positions' => $positions,
            'recentOrders' => $recentOrders,
            'recentExecutions' => $recentExecutions,
            'summary' => [
                'holdings' => $items->count(),
                'open_positions' => $positions->count(),
                'total_invested' => round($totalInvested, 2),
                'mark_value' => round($totalValue, 2),
                'profit_loss' => round($totalPnl, 2),
                'return_percent' => $totalInvested > 0 ? ($totalPnl / $totalInvested) * 100 : 0,
            ],
        ];
    }

    private function safePrice($instrument, string $marketplace): ?float
    {
        try {
            $value = $this->prices->price($instrument, $marketplace);
            return $value > 0 ? (float) $value : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
