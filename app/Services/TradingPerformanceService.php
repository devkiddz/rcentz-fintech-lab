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

        $metrics = $this->fromBotExecutions($executions, (float) ($subscription->bot?->amount_per_trade ?? 0));
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

    private function fromBotExecutions(Collection $executions, float $minimumAmount): array
    {
        $completed = $executions->where('status', 'completed');
        $volume = (float) $completed->sum('amount');
        $pnl = 0.0;
        $wins = 0;
        $losses = 0;

        foreach ($completed as $execution) {
            $stock = $execution->bot?->stock;
            if (! $stock) continue;

            $qty = (float) $execution->quantity;
            $entry = (float) $execution->price;
            $current = (float) $stock->current_price;
            $tradePnl = $execution->action === 'sell'
                ? ($entry - $current) * $qty
                : ($current - $entry) * $qty;

            $pnl += $tradePnl;
            if ($tradePnl > 0) $wins++;
            elseif ($tradePnl < 0) $losses++;
        }

        return $this->summary($executions, $completed, $volume, $pnl, $wins, $losses, $minimumAmount);
    }

    private function fromCopyExecutions(Collection $executions, float $minimumAmount): array
    {
        $completed = $executions->where('status', 'completed');
        $volume = (float) $completed->sum('copied_amount');
        $pnl = 0.0;
        $wins = 0;
        $losses = 0;

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
            if ($tradePnl > 0) $wins++;
            elseif ($tradePnl < 0) $losses++;
        }

        return $this->summary($executions, $completed, $volume, $pnl, $wins, $losses, $minimumAmount);
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

    private function summary(Collection $all, Collection $completed, float $volume, float $pnl, int $wins, int $losses, float $minimumAmount): array
    {
        $resolved = $wins + $losses;

        return [
            'trade_count' => $all->count(),
            'completed_count' => $completed->count(),
            'skipped_count' => $all->whereIn('status', ['skipped', 'failed'])->count(),
            'volume' => $volume,
            'profit_loss' => $pnl,
            'return_percent' => $volume > 0 ? ($pnl / $volume) * 100 : 0,
            'win_rate' => $resolved > 0 ? ($wins / $resolved) * 100 : 0,
            'winning_trades' => $wins,
            'losing_trades' => $losses,
            'minimum_amount' => $minimumAmount,
        ];
    }
}
