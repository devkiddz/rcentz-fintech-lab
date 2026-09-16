<?php

namespace App\Services\Legacy;

use App\Models\Stock;
use App\Models\StockTransaction;
use App\Models\User;
use App\Services\CopyTradingService;
use App\Services\FinancialActivityService;
use Illuminate\Support\Facades\DB;

/**
 * Legacy StockTradePlan execution path.
 *
 * This engine intentionally preserves the pre-position-engine behaviour for
 * historical StockTradePlan rows. New market-contract trading must use
 * StockTradeExecutor + TradePositionService instead.
 */
class LegacyStockExecutionEngine
{
    public function __construct(
        private FinancialActivityService $activity,
        private CopyTradingService $copyTrading,
    ) {}

    public function buy(User $user, Stock $stock, float $quantity, string $context = 'automatic trade plan'): StockTransaction
    {
        if (! $stock->is_active) throw new \RuntimeException('Stock is not active.');
        if ($quantity <= 0) throw new \RuntimeException('Quantity must be greater than zero.');

        $price = (float) $stock->current_price;
        $total = $quantity * $price;
        $transaction = DB::transaction(function () use ($user, $stock, $quantity, $price, $total, $context) {
            $wallet = $user->wallet()->lockForUpdate()->firstOrFail();
            if (! $wallet->canWithdraw($total)) {
                throw new \RuntimeException('Insufficient available balance for automatic buy-back.');
            }

            $before = $this->activity->snapshot($wallet);
            $reference = $this->activity->reference('STK-AUTO-BUY');

            $walletTransaction = $wallet->transactions()->create([
                'payment_method_id' => 1,
                'type' => 'investment',
                'direction' => 'debit',
                'amount' => $total,
                'fee' => 0,
                'status' => 'completed',
                'reference_id' => $reference,
                'description' => "Automatic purchase of {$quantity} shares of {$stock->symbol}",
            ]);

            $tx = StockTransaction::create([
                'user_id' => $user->id,
                'stock_id' => $stock->id,
                'wallet_transaction_id' => $walletTransaction->id,
                'type' => 'buy',
                'quantity' => $quantity,
                'price_per_share' => $price,
                'total_amount' => $total,
                'fee' => 0,
                'status' => 'completed',
                'executed_at' => now(),
            ]);

            $holding = $user->stockHoldings()->where('stock_id', $stock->id)->lockForUpdate()->first();
            if ($holding) {
                $newQty = (float)$holding->quantity + $quantity;
                $newInvested = (float)$holding->total_invested + $total;
                $holding->update([
                    'quantity' => $newQty,
                    'average_buy_price' => $newInvested / $newQty,
                    'total_invested' => $newInvested,
                    'current_value' => $newQty * $price,
                    'unrealized_gain_loss' => ($newQty * $price) - $newInvested,
                    'unrealized_gain_loss_percentage' => $newInvested > 0 ? ((($newQty * $price) - $newInvested) / $newInvested) * 100 : 0,
                ]);
            } else {
                $user->stockHoldings()->create([
                    'stock_id' => $stock->id,
                    'quantity' => $quantity,
                    'average_buy_price' => $price,
                    'total_invested' => $total,
                    'current_value' => $total,
                    'unrealized_gain_loss' => 0,
                    'unrealized_gain_loss_percentage' => 0,
                ]);
            }

            $wallet->deductFunds($total);
            $this->activity->record(
                $user,'stock.buy','Automatic buy '.$stock->symbol,
                ucfirst($context).": purchased {$quantity} shares of {$stock->symbol}.",
                $reference,'completed','debit',$total,$wallet,$walletTransaction,null,$before,
                ['stock_id'=>$stock->id,'quantity'=>$quantity,'automatic'=>true,'engine'=>'legacy_trade_plan'],
                'system',null
            );

            return $tx;
        });

        try { $this->copyTrading->mirrorCompletedTrade($transaction); } catch (\Throwable $e) {
            \Log::warning('Copy mirroring failed after legacy automatic buy', ['trade_id'=>$transaction->id,'error'=>$e->getMessage()]);
        }

        return $transaction;
    }

    public function sell(User $user, Stock $stock, float $quantity, string $context = 'automatic trade plan'): StockTransaction
    {
        if (! $stock->is_active) throw new \RuntimeException('Stock is not active.');
        if ($quantity <= 0) throw new \RuntimeException('Quantity must be greater than zero.');

        $price = (float) $stock->current_price;

        $transaction = DB::transaction(function () use ($user, $stock, $quantity, $price, $context) {
            $holding = $user->stockHoldings()
                ->where('stock_id', $stock->id)
                ->lockForUpdate()
                ->first();

            if (! $holding || (float)$holding->quantity < $quantity) {
                throw new \RuntimeException('Not enough shares remain for the planned automatic sale.');
            }

            $wallet = $user->wallet()->lockForUpdate()->firstOrFail();
            $before = $this->activity->snapshot($wallet);
            $gross = $quantity * $price;
            $reference = $this->activity->reference('STK-AUTO-SELL');

            $walletTransaction = $wallet->transactions()->create([
                'payment_method_id' => 1,
                'type' => 'investment',
                'direction' => 'credit',
                'amount' => $gross,
                'fee' => 0,
                'status' => 'completed',
                'reference_id' => $reference,
                'description' => "Automatic sale of {$quantity} shares of {$stock->symbol}",
            ]);

            $tx = StockTransaction::create([
                'user_id' => $user->id,
                'stock_id' => $stock->id,
                'wallet_transaction_id' => $walletTransaction->id,
                'type' => 'sell',
                'quantity' => $quantity,
                'price_per_share' => $price,
                'total_amount' => $gross,
                'fee' => 0,
                'status' => 'completed',
                'executed_at' => now(),
            ]);

            $oldQty = (float)$holding->quantity;
            $remainingQty = $oldQty - $quantity;

            if ($remainingQty > 0) {
                $costBasisSold = (float)$holding->total_invested * ($quantity / $oldQty);
                $remainingInvested = max(0, (float)$holding->total_invested - $costBasisSold);
                $holding->update([
                    'quantity' => $remainingQty,
                    'total_invested' => $remainingInvested,
                    'current_value' => $remainingQty * $price,
                    'unrealized_gain_loss' => ($remainingQty * $price) - $remainingInvested,
                    'unrealized_gain_loss_percentage' => $remainingInvested > 0 ? ((($remainingQty * $price) - $remainingInvested) / $remainingInvested) * 100 : 0,
                ]);
            } else {
                $holding->delete();
            }

            $wallet->addFunds($gross);
            $this->activity->record(
                $user,'stock.sell','Automatic sell '.$stock->symbol,
                ucfirst($context).": sold {$quantity} shares of {$stock->symbol}.",
                $reference,'completed','credit',$gross,$wallet,$walletTransaction,null,$before,
                ['stock_id'=>$stock->id,'quantity'=>$quantity,'automatic'=>true,'engine'=>'legacy_trade_plan'],
                'system',null
            );

            return $tx;
        });

        try { $this->copyTrading->mirrorCompletedTrade($transaction); } catch (\Throwable $e) {
            \Log::warning('Copy mirroring failed after legacy automatic sell', ['trade_id'=>$transaction->id,'error'=>$e->getMessage()]);
        }

        return $transaction;
    }
}
