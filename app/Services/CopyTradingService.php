<?php

namespace App\Services;

use App\Models\CopyRelationship;
use App\Models\CopyTradeExecution;
use App\Models\StockHolding;
use App\Models\StockTransaction;
use Illuminate\Support\Facades\Log;

class CopyTradingService
{
    public function __construct(private StockTradeExecutor $executor) {}

    public function mirrorCompletedTrade(StockTransaction $providerTrade): void
    {
        if ($providerTrade->status !== 'completed') {
            return;
        }

        $query = CopyRelationship::with([
                'follower.kyc',
                'provider.copyTraderProfile',
                'strategy',
            ])
            ->where('provider_id', $providerTrade->user_id)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>', now());
            })
            ->whereHas('strategy', function ($q) {
                $q->where('is_active', true)
                  ->where('is_public', true);
            });

        // New truth path:
        // an attributed provider trade mirrors only contracts for that strategy.
        if ($providerTrade->copy_strategy_id) {
            $query->where('copy_strategy_id', $providerTrade->copy_strategy_id);
        } else {
            // Compatibility only for historical / legacy provider trades that
            // existed before strategy attribution was introduced.
            Log::warning(
                'Legacy provider trade has no strategy attribution; provider-wide mirroring remains enabled for this transaction.',
                [
                    'provider_trade_id' => $providerTrade->id,
                    'provider_id' => $providerTrade->user_id,
                ]
            );
        }

        foreach ($query->get() as $relationship) {
            $this->mirrorOne($relationship, $providerTrade);
        }
    }

    private function mirrorOne(
        CopyRelationship $relationship,
        StockTransaction $providerTrade
    ): void {
        $requested = min(
            (float) $providerTrade->total_amount
                * ((float) $relationship->copy_ratio_percent / 100),
            (float) $relationship->max_trade_amount
        );

        if ($providerTrade->type === 'buy') {
            $requested = min(
                $requested,
                (float) $relationship->remaining_allocation
            );
        }

        if ($requested <= 0) {
            $this->execution(
                $relationship,
                $providerTrade,
                null,
                0,
                0,
                'skipped',
                'Allocation or trade cap reached.'
            );
            return;
        }

        $follower = $relationship->follower;

        if (! $follower || ! $follower->kyc || ! $follower->kyc->isApproved()) {
            $this->execution(
                $relationship,
                $providerTrade,
                null,
                $requested,
                0,
                'skipped',
                'Follower KYC is not approved.'
            );
            return;
        }

        try {
            $providerPrice = (float) $providerTrade->price_per_share;
            $quantity = $requested / $providerPrice;

            if ($providerTrade->type === 'sell') {
                $holding = StockHolding::where('user_id', $follower->id)
                    ->where('stock_id', $providerTrade->stock_id)
                    ->first();

                if (! $holding) {
                    $this->execution(
                        $relationship,
                        $providerTrade,
                        null,
                        $requested,
                        0,
                        'skipped',
                        'No copied holding is available to sell.'
                    );
                    return;
                }

                $quantity = min($quantity, (float) $holding->quantity);
            }

            if ($quantity <= 0) {
                $this->execution(
                    $relationship,
                    $providerTrade,
                    null,
                    $requested,
                    0,
                    'skipped',
                    'Executable quantity is zero.'
                );
                return;
            }

            $trade = $providerTrade->type === 'buy'
                ? $this->executor->buy(
                    $follower,
                    $providerTrade->stock,
                    $quantity,
                    'copy_trade',
                    $relationship->id,
                    $relationship->copy_strategy_id
                )
                : $this->executor->sell(
                    $follower,
                    $providerTrade->stock,
                    $quantity,
                    'copy_trade',
                    $relationship->id,
                    $relationship->copy_strategy_id
                );

            $executed = (float) $trade->total_amount;

            $relationship->update([
                'used_amount' => $providerTrade->type === 'buy'
                    ? min(
                        (float) $relationship->allocation_limit,
                        (float) $relationship->used_amount + $executed
                    )
                    : max(
                        0,
                        (float) $relationship->used_amount - $executed
                    ),
            ]);

            $this->execution(
                $relationship,
                $providerTrade,
                $trade,
                $requested,
                $executed,
                'completed',
                null
            );

            NotificationService::createSystemNotification(
                $follower,
                'Copy trade executed',
                $providerTrade->stock->symbol.' '.$providerTrade->type
                    .' mirrored for $'.number_format($executed, 2).'.',
                [
                    'type' => 'copy_trade',
                    'relationship_id' => $relationship->id,
                    'stock_transaction_id' => $trade->id,
                    'copy_strategy_id' => $relationship->copy_strategy_id,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Copy trade failed', [
                'relationship_id' => $relationship->id,
                'provider_trade_id' => $providerTrade->id,
                'copy_strategy_id' => $relationship->copy_strategy_id,
                'error' => $e->getMessage(),
            ]);

            $this->execution(
                $relationship,
                $providerTrade,
                null,
                $requested,
                0,
                'failed',
                substr($e->getMessage(), 0, 255)
            );
        }
    }

    private function execution(
        CopyRelationship $relationship,
        StockTransaction $providerTrade,
        ?StockTransaction $followerTrade,
        float $requested,
        float $executed,
        string $status,
        ?string $reason
    ): void {
        CopyTradeExecution::create([
            'copy_relationship_id' => $relationship->id,
            'provider_stock_transaction_id' => $providerTrade->id,
            'follower_stock_transaction_id' => $followerTrade?->id,
            'action' => $providerTrade->type,
            'requested_amount' => $requested,
            'executed_amount' => $executed,
            'status' => $status,
            'failure_reason' => $reason,
            'executed_at' => now(),
        ]);
    }
}
