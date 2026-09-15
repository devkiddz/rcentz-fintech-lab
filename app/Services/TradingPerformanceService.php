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

        $positions = \App\Models\TradePosition::with('stock')
            ->where('context_type', 'copy_relationship')
            ->where('context_id', $relationship->id)
            ->get();

        if ($positions->isNotEmpty()) {
            $realized = (float) $positions->sum('realized_profit_loss');
            $open = 0.0;
            $basis = 0.0;
            $positive = 0;
            $negative = 0;
            $neutral = 0;

            foreach ($positions as $position) {
                $entry = (float) $position->entry_price;
                $initial = (float) $position->initial_quantity;
                $openQty = (float) $position->open_quantity;
                $basis += $entry * $initial;

                $positionPnl = (float) $position->realized_profit_loss;
                if ($openQty > 0 && $position->stock) {
                    $mark = ((float) $position->stock->current_price - $entry) * $openQty;
                    $open += $mark;
                    $positionPnl += $mark;
                }

                $this->countMark($positionPnl,$positive,$negative,$neutral);
            }

            $total = $realized + $open;
            $completed = $executions->where('status','completed');
            $volume = (float) $completed->sum('executed_amount');

            $metrics = $this->summary(
                $executions,$completed,$volume,$total,$positive,$negative,$neutral,
                (float)$relationship->allocation_limit,
                [
                    'realized_profit_loss'=>$realized,
                    'open_profit_loss'=>$open,
                    'performance_basis'=>$basis,
                    'return_percent'=>$basis>0?($total/$basis)*100:0,
                    'realized_return_percent'=>$basis>0?($realized/$basis)*100:0,
                    'open_return_percent'=>$basis>0?($open/$basis)*100:0,
                ]
            );
        } else {
            $metrics = $this->fromCopyExecutions($executions,(float)$relationship->allocation_limit);
        }

        $metrics['copy_ratio_percent']=(float)$relationship->copy_ratio_percent;
        $relationship->loadMissing('strategy');

        return $this->applyManualPerformance(
            $metrics,
            (bool)$relationship->strategy?->use_manual_performance,
            $relationship->strategy?->manual_profit_loss,
            $relationship->strategy?->manual_return_percent,
            $relationship->strategy?->manual_performance_label,
            $relationship->strategy?->manual_performance_note
        );
    }