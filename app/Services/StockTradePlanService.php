<?php

namespace App\Services;

use App\Models\StockTradePlan;
use App\Models\StockTransaction;

class StockTradePlanService
{
    public function createForTransaction(StockTransaction $transaction, array $data): ?StockTradePlan
    {
        $minutes = (int)($data['plan_duration_minutes'] ?? 0);
        if ($minutes <= 0) return null;

        $mode = in_array(($data['plan_mode'] ?? 'reminder'), ['reminder','automatic'], true)
            ? $data['plan_mode']
            : 'reminder';

        $action = $transaction->type === 'buy' ? 'sell' : 'buy_back';

        return StockTradePlan::create([
            'user_id' => $transaction->user_id,
            'stock_id' => $transaction->stock_id,
            'source_transaction_id' => $transaction->id,
            'source_type' => $transaction->type,
            'planned_action' => $action,
            'mode' => $mode,
            'duration_minutes' => $minutes,
            'quantity' => $transaction->quantity,
            'due_at' => now()->addMinutes($minutes),
            'status' => 'active',
        ]);
    }

    public function processDuePlans(
        \App\Services\Legacy\LegacyStockExecutionEngine $execution,
        MarketSessionService $marketSession
    ): array {
        $stats = ['review_due'=>0,'automatic_completed'=>0,'queued_closed'=>0,'failed'=>0];

        StockTradePlan::query()
            ->with(['user.wallet','stock'])
            ->whereIn('status',['active','due'])
            ->where('due_at','<=',now())
            ->orderBy('id')
            ->chunkById(100, function ($plans) use ($execution,$marketSession,&$stats) {
                foreach ($plans as $plan) {
                    if ($plan->mode === 'reminder') {
                        if ($plan->status !== 'due') {
                            $plan->update(['status'=>'due','notified_at'=>now()]);
                            NotificationService::createSystemNotification(
                                $plan->user,
                                $plan->stock->symbol.' trade plan is due',
                                $plan->planned_action === 'sell'
                                    ? 'Your planned holding period has ended. Review the position and decide whether to sell.'
                                    : 'Your planned re-entry period has ended. Review the market and decide whether to buy back.',
                                ['type'=>'stock_trade_plan_due','plan_id'=>$plan->id,'stock_id'=>$plan->stock_id]
                            );
                            $stats['review_due']++;
                        }
                        continue;
                    }

                    if (! $marketSession->isOpen()) {
                        if ($plan->status !== 'due') {
                            $plan->update(['status'=>'due','notified_at'=>now()]);
                            NotificationService::createSystemNotification(
                                $plan->user,
                                $plan->stock->symbol.' automatic trade queued',
                                'Your trade plan is due. Automatic execution is queued until the regular market session is open.',
                                ['type'=>'stock_trade_plan_queued','plan_id'=>$plan->id,'stock_id'=>$plan->stock_id]
                            );
                        }
                        $stats['queued_closed']++;
                        continue;
                    }

                    try {
                        $tx = $plan->planned_action === 'sell'
                            ? $execution->sell($plan->user,$plan->stock,(float)$plan->quantity,'trade horizon expired')
                            : $execution->buy($plan->user,$plan->stock,(float)$plan->quantity,'re-entry horizon expired');

                        $plan->update([
                            'status'=>'completed',
                            'executed_transaction_id'=>$tx->id,
                            'completed_at'=>now(),
                            'failure_reason'=>null,
                        ]);

                        NotificationService::createSystemNotification(
                            $plan->user,
                            $plan->stock->symbol.' automatic trade completed',
                            ($plan->planned_action === 'sell' ? 'Automatic sale' : 'Automatic buy-back')
                                .' executed at $'.number_format((float)$tx->price_per_share,2).'.',
                            ['type'=>'stock_trade_plan_completed','plan_id'=>$plan->id,'transaction_id'=>$tx->id]
                        );

                        $stats['automatic_completed']++;
                    } catch (\Throwable $e) {
                        $plan->update([
                            'status'=>'failed',
                            'failure_reason'=>mb_substr($e->getMessage(),0,1000),
                            'completed_at'=>now(),
                        ]);

                        NotificationService::createSystemNotification(
                            $plan->user,
                            $plan->stock->symbol.' automatic trade could not execute',
                            $e->getMessage(),
                            ['type'=>'stock_trade_plan_failed','plan_id'=>$plan->id]
                        );

                        $stats['failed']++;
                    }
                }
            });

        return $stats;
    }
}
