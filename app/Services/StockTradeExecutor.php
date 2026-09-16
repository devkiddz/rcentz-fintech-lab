<?php

namespace App\Services;

use App\Models\PaymentMethod;
use App\Models\Stock;
use App\Models\StockHolding;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockTradeExecutor
{
    public function __construct(
        private FinancialActivityService $activity,
        private MarketSessionService $marketSession,
        private MarketPriceRouter $prices
    ) {}

    public function buy(
        User $user,
        Stock $stock,
        float $quantity,
        string $source,
        ?int $sourceId = null,
        ?int $copyStrategyId = null,
        string $actorType = 'system',
        ?int $actorId = null,
        ?int $positionId = null,
        ?string $marketplace = null
    ): StockTransaction {
        $marketplace = $this->prices->normalizeMarketplace($marketplace ?: $this->prices->activeMarketplace());
        $this->assertRegularMarketOpen($marketplace);

        if ($quantity <= 0 || ! $stock->is_active) {
            throw new RuntimeException('Trade is not executable.');
        }

        $price = $this->prices->price($stock, $marketplace);
        $amount = round($quantity * $price, 2);

        if ($amount <= 0) {
            throw new RuntimeException('Trade amount is invalid.');
        }

        return DB::transaction(function () use (
            $user,$stock,$quantity,$price,$amount,$source,$sourceId,$copyStrategyId,$actorType,$actorId,$positionId,$marketplace
        ) {
            $wallet = $user->wallet()->lockForUpdate()->firstOrFail();

            if (! $wallet->canWithdraw($amount)) {
                throw new RuntimeException('Insufficient available balance.');
            }

            $before = $this->activity->snapshot($wallet);
            $reference = $this->activity->reference(strtoupper(substr($source,0,8)).'-BUY');
            $method = $this->internalMethod();

            $walletTx = $wallet->transactions()->create([
                'payment_method_id'=>$method->id,
                'type'=>'investment',
                'direction'=>'debit',
                'amount'=>$amount,
                'fee'=>0,
                'status'=>'completed',
                'reference_id'=>$reference,
                'description'=>$this->label($source).' buy: '.$stock->symbol,
            ]);

            $trade = StockTransaction::create([
                'user_id'=>$user->id,
                'stock_id'=>$stock->id,
                'copy_strategy_id'=>$copyStrategyId,
                'execution_source'=>$source,
                'marketplace'=>$marketplace,
                'initiated_by_user_id'=>$actorId,
                'trade_position_id'=>$positionId,
                'wallet_transaction_id'=>$walletTx->id,
                'type'=>'buy',
                'quantity'=>$quantity,
                'price_per_share'=>$price,
                'total_amount'=>$amount,
                'fee'=>0,
                'status'=>'completed',
                'executed_at'=>now(),
            ]);

            $holding = StockHolding::where('user_id',$user->id)
                ->where('stock_id',$stock->id)
                ->where('marketplace',$marketplace)
                ->lockForUpdate()
                ->first();

            if ($holding) {
                $newQty=(float)$holding->quantity+$quantity;
                $newInvested=(float)$holding->total_invested+$amount;
                $current=$newQty*$price;
                $holding->update([
                    'quantity'=>$newQty,
                    'average_buy_price'=>$newInvested/$newQty,
                    'total_invested'=>$newInvested,
                    'current_value'=>$current,
                    'unrealized_gain_loss'=>$current-$newInvested,
                    'unrealized_gain_loss_percentage'=>$newInvested>0?(($current-$newInvested)/$newInvested)*100:0,
                ]);
            } else {
                StockHolding::create([
                    'user_id'=>$user->id,
                    'stock_id'=>$stock->id,
                    'marketplace'=>$marketplace,
                    'quantity'=>$quantity,
                    'average_buy_price'=>$price,
                    'total_invested'=>$amount,
                    'current_value'=>$amount,
                    'unrealized_gain_loss'=>0,
                    'unrealized_gain_loss_percentage'=>0,
                ]);
            }

            $wallet->deductFunds($amount);

            $this->activity->record(
                $user,
                $source.'.buy',
                $this->label($source).' buy',
                'Purchased '.number_format($quantity,6).' shares of '.$stock->symbol.'.',
                $reference,
                'completed',
                'debit',
                $amount,
                $wallet,
                $walletTx,
                null,
                $before,
                [
                    'source'=>$source,
                    'source_id'=>$sourceId,
                    'copy_strategy_id'=>$copyStrategyId,
                    'stock_id'=>$stock->id,
                    'quantity'=>$quantity,
                    'marketplace'=>$marketplace,
                    'execution_price'=>$price,
                ],
                $actorType,
                $actorId
            );

            return $trade;
        });
    }

    public function sell(
        User $user,
        Stock $stock,
        float $quantity,
        string $source,
        ?int $sourceId = null,
        ?int $copyStrategyId = null,
        string $actorType = 'system',
        ?int $actorId = null,
        ?int $positionId = null,
        bool $allowClosedSessionSettlement = false,
        ?float $executionPrice = null,
        ?string $marketplace = null
    ): StockTransaction {
        $marketplace = $this->prices->normalizeMarketplace($marketplace ?: $this->prices->activeMarketplace());

        if (! $allowClosedSessionSettlement) {
            $this->assertRegularMarketOpen($marketplace);
        }

        if ($quantity <= 0 || ! $stock->is_active) {
            throw new RuntimeException('Trade is not executable.');
        }

        $price=$executionPrice !== null
            ? (float)$executionPrice
            : $this->prices->price($stock, $marketplace);

        if ($price <= 0) {
            throw new RuntimeException('Execution market price is invalid.');
        }

        return DB::transaction(function () use (
            $user,$stock,$quantity,$price,$source,$sourceId,$copyStrategyId,$actorType,$actorId,$positionId,
            $allowClosedSessionSettlement,$marketplace
        ) {
            $wallet=$user->wallet()->lockForUpdate()->firstOrFail();
            $holding=StockHolding::where('user_id',$user->id)
                ->where('stock_id',$stock->id)
                ->where('marketplace',$marketplace)
                ->lockForUpdate()
                ->first();

            if (! $holding || (float)$holding->quantity < $quantity) {
                throw new RuntimeException('Insufficient stock holding.');
            }

            $amount=round($quantity*$price,2);
            $before=$this->activity->snapshot($wallet);
            $reference=$this->activity->reference(strtoupper(substr($source,0,8)).'-SELL');
            $method=$this->internalMethod();

            $walletTx=$wallet->transactions()->create([
                'payment_method_id'=>$method->id,
                'type'=>'investment',
                'direction'=>'credit',
                'amount'=>$amount,
                'fee'=>0,
                'status'=>'completed',
                'reference_id'=>$reference,
                'description'=>$this->label($source).' sell: '.$stock->symbol,
            ]);

            $trade=StockTransaction::create([
                'user_id'=>$user->id,
                'stock_id'=>$stock->id,
                'copy_strategy_id'=>$copyStrategyId,
                'execution_source'=>$source,
                'marketplace'=>$marketplace,
                'initiated_by_user_id'=>$actorId,
                'trade_position_id'=>$positionId,
                'wallet_transaction_id'=>$walletTx->id,
                'type'=>'sell',
                'quantity'=>$quantity,
                'price_per_share'=>$price,
                'total_amount'=>$amount,
                'fee'=>0,
                'status'=>'completed',
                'executed_at'=>now(),
            ]);

            $oldQty=(float)$holding->quantity;
            $remaining=$oldQty-$quantity;
            $proportion=$quantity/$oldQty;
            $remainingInvested=max(0,(float)$holding->total_invested-((float)$holding->total_invested*$proportion));

            if($remaining>0){
                $current=$remaining*$price;
                $holding->update([
                    'quantity'=>$remaining,
                    'total_invested'=>$remainingInvested,
                    'current_value'=>$current,
                    'unrealized_gain_loss'=>$current-$remainingInvested,
                    'unrealized_gain_loss_percentage'=>$remainingInvested>0?(($current-$remainingInvested)/$remainingInvested)*100:0,
                ]);
            }else{
                $holding->delete();
            }

            $wallet->addFunds($amount);

            $this->activity->record(
                $user,
                $source.'.sell',
                $this->label($source).' sell',
                'Sold '.number_format($quantity,6).' shares of '.$stock->symbol.'.',
                $reference,
                'completed',
                'credit',
                $amount,
                $wallet,
                $walletTx,
                null,
                $before,
                [
                    'source'=>$source,
                    'source_id'=>$sourceId,
                    'copy_strategy_id'=>$copyStrategyId,
                    'stock_id'=>$stock->id,
                    'quantity'=>$quantity,
                    'marketplace'=>$marketplace,
                    'execution_price'=>$price,
                    'session_settlement'=>$allowClosedSessionSettlement,
                ],
                $actorType,
                $actorId
            );

            return $trade;
        });
    }

    private function assertRegularMarketOpen(?string $marketplace = null): void
    {
        if (! $this->prices->requiresRegularSession($marketplace)) {
            return;
        }

        if (! $this->marketSession->isOpen()) {
            $status = str_replace('_', ' ', $this->marketSession->status());

            throw new RuntimeException(
                'Regular U.S. equity market is '.$status.'. This live execution cannot be filled at a stale stored price.'
            );
        }
    }

    private function internalMethod(): PaymentMethod
    {
        return PaymentMethod::firstOrCreate(
            ['name'=>'Internal Trading'],
            [
                'type'=>'traditional',
                'details'=>'Internal stock execution ledger method.',
                'is_active'=>true,
                'allow_deposit'=>false,
                'allow_withdraw'=>false,
            ]
        );
    }

    private function label(string $source): string
    {
        return match($source){
            'copy_trade'=>'Copy trade',
            'trading_bot'=>'Trading bot',
            'admin_strategy_trade'=>'Strategy trade',
            'admin_direct_trade'=>'Admin trade',
            'admin_user_trade'=>'Trade for user',
            'position_exit'=>'Position exit',
            'position_kill'=>'Killed trade',
            'position_reentry'=>'Position re-entry',
            default=>'Stock trade',
        };
    }
}
