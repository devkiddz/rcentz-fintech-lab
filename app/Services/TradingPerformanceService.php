<?php

namespace App\Services;

use App\Models\BotProduct;
use App\Models\BotSubscription;
use App\Models\CopyRelationship;
use App\Models\CopyStrategy;
use App\Models\TradePosition;
use App\Models\TradingBot;
use App\Models\TradingBotExecution;
use Illuminate\Support\Collection;

class TradingPerformanceService
{
    private array $executionMarketCache = [];

    public function __construct(
        private MarketPriceRouter $prices,
        private MarketSettlementService $settlement,
        private BotMarketContextService $markets
    ) {}

    public function botProduct(BotProduct $product): array
    {
        $executions = TradingBotExecution::with(['bot.stock','bot.marketInstrument','marketInstrument','marketExecution'])
            ->whereHas('subscription', fn ($q) => $q->where('bot_product_id', $product->id))
            ->get();

        $metrics = $this->fromBotExecutions($executions, (float) ($product->default_trade_amount ?? 0));

        return $this->applyManualPerformance(
            $metrics,
            (bool) $product->use_manual_performance,
            $product->manual_profit_loss,
            $product->manual_return_percent,
            $product->manual_performance_label,
            $product->manual_performance_note
        );
    }

    public function botSubscription(BotSubscription $subscription): array
    {
        $executions = TradingBotExecution::with(['bot.stock','bot.marketInstrument','marketInstrument','marketExecution'])
            ->where('bot_subscription_id', $subscription->id)
            ->get();

        $metrics = $this->fromBotExecutions(
            $executions,
            (float) ($subscription->bot?->amount_per_trade ?? 0)
        );

        $subscription->loadMissing('product');

        return $this->applyManualPerformance(
            $metrics,
            (bool) $subscription->product?->use_manual_performance,
            $subscription->product?->manual_profit_loss,
            $subscription->product?->manual_return_percent,
            $subscription->product?->manual_performance_label,
            $subscription->product?->manual_performance_note
        );
    }

    public function executionMark(TradingBotExecution $execution): array
    {
        $execution->loadMissing([
            'bot.user.wallet',
            'bot.stock.marketInstrument',
            'bot.marketInstrument',
            'marketInstrument',
            'marketExecution',
        ]);

        $instrument = $execution->marketInstrument
            ?? $execution->bot?->marketInstrument
            ?? $execution->bot?->stock?->marketInstrument;

        $market = null;
        if ($instrument) {
            $cacheKey = (string) $instrument->id;
            $market = $this->executionMarketCache[$cacheKey]
                ??= $this->markets->forInstrument($instrument, 0);
        }
        $current = (float) ($market['current'] ?? 0.0);
        $entry = (float) $execution->price;
        $qty = (float) $execution->quantity;
        $amount = (float) $execution->amount;
        $pnl = 0.0;

        if ($execution->status === 'completed' && $entry > 0 && $qty > 0 && $instrument) {
            if ($execution->action === 'sell' && $execution->marketExecution?->realized_profit_loss !== null) {
                $pnl = (float) $execution->marketExecution->realized_profit_loss;
            } elseif ($current > 0) {
                $quotePnl = ($current - $entry) * $qty;

                if ($instrument->isStock()) {
                    $pnl = $execution->action === 'sell' ? -$quotePnl : $quotePnl;
                } else {
                    try {
                        $currency = strtoupper((string) ($execution->bot?->user?->wallet?->currency ?: 'USD'));
                        $converted = $this->settlement->profitLossToSettlement(
                            $instrument,
                            $quotePnl,
                            $current,
                            $currency,
                            false
                        );
                        $pnl = $execution->action === 'sell' ? -$converted : $converted;
                    } catch (\Throwable) {
                        $pnl = 0.0;
                    }
                }
            }
        }

        return [
            'current_price' => $current,
            'current_price_display' => $this->markets->displayPrice($instrument, $current),
            'entry_price_display' => $this->markets->displayPrice($instrument, $entry),
            'profit_loss' => $pnl,
            'return_percent' => $amount > 0 ? ($pnl / $amount) * 100 : 0.0,
        ];
    }

    public function copyStrategy(CopyStrategy $strategy): array
    {
        $relationshipIds = CopyRelationship::query()
            ->where('copy_strategy_id', $strategy->id)
            ->pluck('id');

        $executions = $relationshipIds->isEmpty()
            ? collect()
            : \App\Models\CopyTradeExecution::query()
                ->with([
                    'relationship.follower.wallet',
                    'marketInstrument',
                    'followerMarketExecution.marketInstrument',
                    'followerMarketExecution.tradePosition.entryMarketExecutionTransaction',
                    'followerTrade.stock.marketInstrument',
                    'followerTrade.marketInstrument',
                ])
                ->whereIn('copy_relationship_id', $relationshipIds)
                ->get();

        $positions = $relationshipIds->isEmpty()
            ? collect()
            : TradePosition::query()
                ->with([
                    'user.wallet',
                    'marketInstrument',
                    'stock.marketInstrument',
                    'entryMarketExecutionTransaction',
                ])
                ->where('context_type', 'copy_relationship')
                ->whereIn('context_id', $relationshipIds)
                ->get();

        $metrics = $this->fromCopyPositions(
            $executions,
            $positions,
            (float) $strategy->minimum_allocation
        );

        return $this->applyManualPerformance(
            $metrics,
            (bool) $strategy->use_manual_performance,
            $strategy->manual_profit_loss,
            $strategy->manual_return_percent,
            $strategy->manual_performance_label,
            $strategy->manual_performance_note
        );
    }

    public function copyRelationship(CopyRelationship $relationship): array
    {
        $executions = $relationship->executions()
            ->with([
                'relationship.follower.wallet',
                'marketInstrument',
                'followerMarketExecution.marketInstrument',
                'followerMarketExecution.tradePosition.entryMarketExecutionTransaction',
                'followerTrade.stock.marketInstrument',
                'followerTrade.marketInstrument',
            ])
            ->get();

        $positions = TradePosition::query()
            ->with([
                'user.wallet',
                'marketInstrument',
                'stock.marketInstrument',
                'entryMarketExecutionTransaction',
            ])
            ->where('context_type', 'copy_relationship')
            ->where('context_id', $relationship->id)
            ->get();

        $metrics = $this->fromCopyPositions(
            $executions,
            $positions,
            (float) $relationship->allocation_limit
        );
        $metrics['copy_ratio_percent'] = (float) $relationship->copy_ratio_percent;

        $relationship->loadMissing('strategy');

        return $this->applyManualPerformance(
            $metrics,
            (bool) $relationship->strategy?->use_manual_performance,
            $relationship->strategy?->manual_profit_loss,
            $relationship->strategy?->manual_return_percent,
            $relationship->strategy?->manual_performance_label,
            $relationship->strategy?->manual_performance_note
        );
    }

    private function fromBotExecutions(Collection $executions, float $minimumAmount): array
    {
        $completed = $executions->where('status', 'completed')->sortBy('executed_at');
        $volume = (float) $completed->sum('amount');

        $realizedPnl = 0.0;
        $realizedCostBasis = 0.0;
        $openPnl = 0.0;
        $openCostBasis = 0.0;
        $positive = 0;
        $negative = 0;
        $neutral = 0;

        foreach ($completed->groupBy('trading_bot_id') as $botExecutions) {
            /** @var TradingBot|null $bot */
            $bot = $botExecutions->last()?->bot;
            if (! $bot) {
                continue;
            }

            $positions = TradePosition::query()
                ->with([
                    'user.wallet',
                    'marketInstrument',
                    'stock.marketInstrument',
                    'entryMarketExecutionTransaction',
                ])
                ->where('context_type', 'trading_bot')
                ->where('context_id', $bot->id)
                ->orderBy('opened_at')
                ->get();

            if ($positions->isNotEmpty()) {
                foreach ($positions as $position) {
                    $basis = $this->positionBasis($position);
                    $initialQty = max((float) $position->initial_quantity, 0.00000001);
                    $openQty = max((float) $position->open_quantity, 0.0);
                    $closedRatio = min(1.0, max(0.0, ($initialQty - $openQty) / $initialQty));
                    $openRatio = min(1.0, max(0.0, $openQty / $initialQty));
                    $realized = (float) $position->realized_profit_loss;
                    $openMark = $this->openPositionPnl($position);
                    $net = $realized + $openMark;

                    $realizedPnl += $realized;
                    $openPnl += $openMark;
                    $realizedCostBasis += $basis * $closedRatio;
                    $openCostBasis += $basis * $openRatio;
                    $this->countMark($net, $positive, $negative, $neutral);
                }
                continue;
            }

            // Compatibility fallback for legacy executions that predate position attribution.
            $inventoryQty = 0.0;
            $inventoryCost = 0.0;
            $instrument = $bot->marketInstrument ?? $bot->stock?->marketInstrument;
            $currentPrice = $instrument ? ($this->safePrice($instrument) ?? 0.0) : (float) ($bot->stock?->current_price ?? 0);

            foreach ($botExecutions as $execution) {
                $qty = (float) $execution->quantity;
                $entry = (float) $execution->price;
                $amount = (float) $execution->amount;

                if ($qty <= 0 || $entry <= 0) {
                    continue;
                }

                if ($execution->action === 'buy') {
                    $inventoryQty += $qty;
                    $inventoryCost += $amount > 0 ? $amount : ($entry * $qty);
                    continue;
                }

                if ($execution->action === 'sell' && $inventoryQty > 0) {
                    $matchedQty = min($qty, $inventoryQty);
                    $averageCost = $inventoryQty > 0 ? $inventoryCost / $inventoryQty : 0.0;
                    $costBasis = $averageCost * $matchedQty;
                    $tradeRealized = (float) ($execution->marketExecution?->realized_profit_loss ?? (($entry * $matchedQty) - $costBasis));

                    $realizedPnl += $tradeRealized;
                    $realizedCostBasis += $costBasis;
                    $this->countMark($tradeRealized, $positive, $negative, $neutral);
                    $inventoryQty -= $matchedQty;
                    $inventoryCost = max(0, $inventoryCost - $costBasis);
                }
            }

            if ($inventoryQty > 0 && $inventoryCost > 0 && $currentPrice > 0 && $instrument) {
                $openCostBasis += $inventoryCost;
                $legacyOpen = $this->convertQuotePnl(
                    $instrument,
                    ($currentPrice * $inventoryQty) - ($inventoryCost > 0 ? $inventoryCost : 0),
                    $currentPrice,
                    $bot
                );
                $openPnl += $legacyOpen;
                $this->countMark($legacyOpen, $positive, $negative, $neutral);
            }
        }

        $totalPnl = $realizedPnl + $openPnl;
        $performanceBasis = $realizedCostBasis + $openCostBasis;

        return $this->summary(
            $executions,
            $completed,
            $volume,
            $totalPnl,
            $positive,
            $negative,
            $neutral,
            $minimumAmount,
            [
                'realized_profit_loss' => $realizedPnl,
                'open_profit_loss' => $openPnl,
                'realized_cost_basis' => $realizedCostBasis,
                'open_cost_basis' => $openCostBasis,
                'performance_basis' => $performanceBasis,
                'return_percent' => $performanceBasis > 0 ? ($totalPnl / $performanceBasis) * 100 : 0,
                'realized_return_percent' => $realizedCostBasis > 0 ? ($realizedPnl / $realizedCostBasis) * 100 : 0,
                'open_return_percent' => $openCostBasis > 0 ? ($openPnl / $openCostBasis) * 100 : 0,
            ]
        );
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
        if ($openQty <= 0) {
            return 0.0;
        }

        $instrument = $position->marketInstrument ?? $position->stock?->marketInstrument;
        if (! $instrument) {
            return 0.0;
        }

        $current = $this->safePrice($instrument, $position->marketplace);
        if (! $current || $current <= 0) {
            return 0.0;
        }

        $quotePnl = ($current - (float) $position->entry_price) * $openQty;
        if ($instrument->isStock()) {
            return $quotePnl;
        }

        try {
            $currency = strtoupper((string) ($position->user?->wallet?->currency ?: 'USD'));
            return $this->settlement->profitLossToSettlement($instrument, $quotePnl, $current, $currency, false);
        } catch (\Throwable) {
            return 0.0;
        }
    }

    private function convertQuotePnl($instrument, float $quotePnl, float $price, TradingBot $bot): float
    {
        if ($instrument->isStock()) {
            return $quotePnl;
        }

        try {
            $bot->loadMissing('user.wallet');
            $currency = strtoupper((string) ($bot->user?->wallet?->currency ?: 'USD'));
            return $this->settlement->profitLossToSettlement($instrument, $quotePnl, $price, $currency, false);
        } catch (\Throwable) {
            return 0.0;
        }
    }

    private function safePrice($instrument, ?string $marketplace = null): ?float
    {
        try {
            $price = (float) $this->prices->price($instrument, $marketplace);
            return $price > 0 ? $price : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function fromCopyPositions(Collection $executions, Collection $positions, float $minimumAmount): array
    {
        $completed = $executions->where('status', 'completed');
        $volume = (float) $completed->sum('executed_amount');

        if ($positions->isEmpty()) {
            return $this->fromCopyExecutionMarks($executions, $minimumAmount);
        }

        $realized = 0.0;
        $open = 0.0;
        $realizedBasis = 0.0;
        $openBasis = 0.0;
        $positive = 0;
        $negative = 0;
        $neutral = 0;

        foreach ($positions as $position) {
            $basis = $this->positionBasis($position);
            $initialQty = max((float) $position->initial_quantity, 0.00000001);
            $openQty = max((float) $position->open_quantity, 0.0);
            $closedRatio = min(1.0, max(0.0, ($initialQty - $openQty) / $initialQty));
            $openRatio = min(1.0, max(0.0, $openQty / $initialQty));
            $positionRealized = (float) $position->realized_profit_loss;
            $positionOpen = $this->openPositionPnl($position);
            $net = $positionRealized + $positionOpen;

            $realized += $positionRealized;
            $open += $positionOpen;
            $realizedBasis += $basis * $closedRatio;
            $openBasis += $basis * $openRatio;
            $this->countMark($net, $positive, $negative, $neutral);
        }

        $total = $realized + $open;
        $basis = $realizedBasis + $openBasis;

        return $this->summary(
            $executions,
            $completed,
            $volume,
            $total,
            $positive,
            $negative,
            $neutral,
            $minimumAmount,
            [
                'realized_profit_loss' => $realized,
                'open_profit_loss' => $open,
                'realized_cost_basis' => $realizedBasis,
                'open_cost_basis' => $openBasis,
                'performance_basis' => $basis,
                'return_percent' => $basis > 0 ? ($total / $basis) * 100 : 0,
                'realized_return_percent' => $realizedBasis > 0 ? ($realized / $realizedBasis) * 100 : 0,
                'open_return_percent' => $openBasis > 0 ? ($open / $openBasis) * 100 : 0,
            ]
        );
    }

    private function fromCopyExecutionMarks(Collection $executions, float $minimumAmount): array
    {
        $completed = $executions->where('status', 'completed');
        $volume = (float) $completed->sum('executed_amount');
        $pnl = 0.0;
        $positive = 0;
        $negative = 0;
        $neutral = 0;

        foreach ($completed as $copyExecution) {
            $marketExecution = $copyExecution->followerMarketExecution;
            $instrument = $copyExecution->marketInstrument
                ?? $marketExecution?->marketInstrument
                ?? $copyExecution->followerTrade?->marketInstrument
                ?? $copyExecution->followerTrade?->stock?->marketInstrument;

            if (! $instrument) {
                continue;
            }

            $quantity = (float) ($marketExecution?->quantity ?? $copyExecution->followerTrade?->quantity ?? 0);
            $entry = (float) ($marketExecution?->price ?? $copyExecution->followerTrade?->price_per_share ?? 0);
            if ($quantity <= 0 || $entry <= 0) {
                continue;
            }

            if (($marketExecution?->side ?? $copyExecution->action) === 'sell' && $marketExecution?->realized_profit_loss !== null) {
                $mark = (float) $marketExecution->realized_profit_loss;
            } else {
                $current = $this->safePrice($instrument, $marketExecution?->marketplace ?? $copyExecution->followerTrade?->marketplace);
                if (! $current || $current <= 0) {
                    $mark = 0.0;
                } else {
                    $quotePnl = ($current - $entry) * $quantity;
                    if ($instrument->isStock()) {
                        $mark = $quotePnl;
                    } else {
                        try {
                            $currency = strtoupper((string) ($copyExecution->relationship?->follower?->wallet?->currency ?: 'USD'));
                            $mark = $this->settlement->profitLossToSettlement(
                                $instrument,
                                $quotePnl,
                                $current,
                                $currency,
                                false
                            );
                        } catch (\Throwable) {
                            $mark = 0.0;
                        }
                    }
                }
            }

            $pnl += $mark;
            $this->countMark($mark, $positive, $negative, $neutral);
        }

        return $this->summary(
            $executions,
            $completed,
            $volume,
            $pnl,
            $positive,
            $negative,
            $neutral,
            $minimumAmount
        );
    }

    private function countMark(
        float $value,
        int &$positive,
        int &$negative,
        int &$neutral
    ): void {
        if ($value > 0) {
            $positive++;
        } elseif ($value < 0) {
            $negative++;
        } else {
            $neutral++;
        }
    }

    private function applyManualPerformance(
        array $metrics,
        bool $enabled,
        mixed $manualProfitLoss,
        mixed $manualReturnPercent,
        ?string $label,
        ?string $note
    ): array {
        return app(\App\Services\Simulation\ManualPerformanceEngine::class)->apply(
            $metrics,
            $enabled,
            $manualProfitLoss,
            $manualReturnPercent,
            $label,
            $note
        );
    }

    private function summary(
        Collection $all,
        Collection $completed,
        float $volume,
        float $pnl,
        int $positive,
        int $negative,
        int $neutral,
        float $minimumAmount,
        array $extra = []
    ): array {
        $resolved = $positive + $negative;

        return array_merge([
            'trade_count' => $all->count(),
            'completed_count' => $completed->count(),
            'skipped_count' => $all->whereIn('status', ['skipped', 'failed'])->count(),
            'volume' => $volume,
            'profit_loss' => $pnl,
            'return_percent' => $volume > 0 ? ($pnl / $volume) * 100 : 0,
            'positive_execution_rate' => $resolved > 0 ? ($positive / $resolved) * 100 : 0,
            'win_rate' => $resolved > 0 ? ($positive / $resolved) * 100 : 0,
            'positive_count' => $positive,
            'negative_count' => $negative,
            'neutral_count' => $neutral,
            'winning_trades' => $positive,
            'losing_trades' => $negative,
            'minimum_amount' => $minimumAmount,
            'realized_profit_loss' => 0.0,
            'open_profit_loss' => $pnl,
            'realized_cost_basis' => 0.0,
            'open_cost_basis' => $volume,
            'performance_basis' => $volume,
            'realized_return_percent' => 0.0,
            'open_return_percent' => $volume > 0 ? ($pnl / $volume) * 100 : 0,
        ], $extra);
    }
}
