<?php

namespace App\Services;

use App\Models\BotProduct;
use App\Models\BotSubscription;
use App\Models\CopyRelationship;
use App\Models\CopyStrategy;
use App\Models\TradingBotExecution;
use Illuminate\Support\Collection;

class TradingPerformanceService
{
    public function botProduct(BotProduct $product): array
    {
        $executions = TradingBotExecution::with('bot.stock')
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
        $executions = TradingBotExecution::with('bot.stock')
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

    public function copyStrategy(CopyStrategy $strategy): array
    {
        $executions = \App\Models\CopyTradeExecution::with('followerTrade.stock')
            ->whereHas('relationship', fn ($q) => $q->where('copy_strategy_id', $strategy->id))
            ->get();

        $metrics = $this->fromCopyExecutions($executions, (float) $strategy->minimum_allocation);

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
        $executions = $relationship->executions()->with('followerTrade.stock')->get();
        $metrics = $this->fromCopyExecutions($executions, (float) $relationship->allocation_limit);
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

    /**
     * Bot performance now distinguishes:
     * - realized P/L: profit/loss locked in by bot sell executions
     * - open P/L: mark-to-market P/L on remaining bot-attributed inventory
     * - total/current P/L: realized + open P/L
     *
     * Inventory accounting uses average cost per runtime bot and only the bot's
     * own execution ledger, so one bot cannot borrow another bot's performance.
     */
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
            $inventoryQty = 0.0;
            $inventoryCost = 0.0;
            $currentPrice = (float) ($botExecutions->last()?->bot?->stock?->current_price ?? 0);

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

                    $markPnl = $currentPrice > 0 ? ($currentPrice - $entry) * $qty : 0.0;
                    $this->countMark($markPnl, $positive, $negative, $neutral);
                    continue;
                }

                if ($execution->action === 'sell' && $inventoryQty > 0) {
                    $matchedQty = min($qty, $inventoryQty);
                    $averageCost = $inventoryQty > 0 ? $inventoryCost / $inventoryQty : 0.0;
                    $costBasis = $averageCost * $matchedQty;
                    $proceeds = $entry * $matchedQty;
                    $tradeRealized = $proceeds - $costBasis;

                    $realizedPnl += $tradeRealized;
                    $realizedCostBasis += $costBasis;
                    $this->countMark($tradeRealized, $positive, $negative, $neutral);

                    $inventoryQty -= $matchedQty;
                    $inventoryCost = max(0, $inventoryCost - $costBasis);
                } else {
                    // A sell with no bot-attributed inventory is not assigned a
                    // fabricated cost basis. It remains execution volume only.
                    $neutral++;
                }
            }

            if ($inventoryQty > 0 && $inventoryCost > 0 && $currentPrice > 0) {
                $openCostBasis += $inventoryCost;
                $openPnl += ($currentPrice * $inventoryQty) - $inventoryCost;
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

    private function fromCopyExecutions(Collection $executions, float $minimumAmount): array
    {
        $completed = $executions->where('status', 'completed');
        $volume = (float) $completed->sum('executed_amount');
        $pnl = 0.0;
        $positive = 0;
        $negative = 0;
        $neutral = 0;

        foreach ($completed as $execution) {
            $trade = $execution->followerTrade;
            if (! $trade || ! $trade->stock) continue;

            $qty = (float) $trade->quantity;
            $entry = (float) $trade->price_per_share;
            $current = (float) $trade->stock->current_price;
            $tradePnl = $trade->type === 'sell'
                ? ($entry - $current) * $qty
                : ($current - $entry) * $qty;

            $pnl += $tradePnl;
            $this->countMark($tradePnl, $positive, $negative, $neutral);
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
        if ($value > 0) $positive++;
        elseif ($value < 0) $negative++;
        else $neutral++;
    }

    private function applyManualPerformance(
        array $metrics,
        bool $enabled,
        mixed $manualProfitLoss,
        mixed $manualReturnPercent,
        ?string $label,
        ?string $note
    ): array {
        $metrics['actual_profit_loss'] = $metrics['profit_loss'];
        $metrics['actual_return_percent'] = $metrics['return_percent'];
        $metrics['is_manual_performance'] = $enabled;
        $metrics['performance_label'] = $enabled ? trim((string) $label) : null;
        $metrics['performance_note'] = $enabled ? $note : null;

        if (! $enabled) {
            return $metrics;
        }

        if ($manualProfitLoss !== null) {
            $metrics['profit_loss'] = (float) $manualProfitLoss;
        }

        if ($manualReturnPercent !== null) {
            $metrics['return_percent'] = (float) $manualReturnPercent;
        }

        return $metrics;
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
            // Compatibility key for untouched views; semantically this is now
            // Positive Execution Rate, not realized win rate.
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
