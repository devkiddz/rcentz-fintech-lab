<?php
namespace App\Services;

use App\Models\BrokerOrder;
use App\Models\MarketExecutionTransaction;
use App\Models\MarketInstrument;
use App\Models\TradePosition;
use App\Models\TradingBot;
use App\Models\TradingBotExecution;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TradingBotService
{
    public function __construct(
        private BrokerOrderService $brokerOrders,
        private MarketPriceRouter $prices,
        private MarketSettlementService $settlement,
        private FeatureAccessService $featureAccess
    ){}

    public function run(TradingBot $bot,bool $manual=false):TradingBotExecution
    {
        $bot->loadMissing([
            'user.kyc',
            'user.wallet',
            'marketInstrument',
            'stock.marketInstrument',
            'subscription.product.marketInstrument',
        ]);

        $user=$bot->user;
        $subscription=$bot->subscription;
        $instrument=$bot->marketInstrument ?? $bot->stock?->marketInstrument;
        $marketplace=$this->prices->activeMarketplace();

        if(!$subscription || !$subscription->is_usable){
            return $this->log($bot,null,null,0,0,0,'skipped','Bot subscription is not active.',$instrument);
        }
        if(!$manual && ($bot->status!=='active' || ($bot->next_run_at && $bot->next_run_at->isFuture()))){
            return $this->log($bot,null,null,0,0,0,'skipped','Bot is not due.',$instrument);
        }
        if($bot->action!=='sell' && !$this->featureAccess->allows($user, FeatureAccessService::BOT_TRADER)){
            $bot->update(['status'=>'paused']);
            return $this->log($bot,null,null,0,0,0,'skipped','Bot paused because active membership with Bot Trader access is required.',$instrument);
        }
        if(!$user->kyc || !$user->kyc->isApproved()){
            return $this->log($bot,null,null,0,0,0,'skipped','KYC is not approved.',$instrument);
        }
        if(!$instrument){
            return $this->finish($bot,$this->log($bot,null,null,0,0,0,'failed','Bot MarketInstrument authority is unavailable.',null));
        }

        try{
            $price=(float)$this->prices->price($instrument,$marketplace);
            if($price<=0){
                throw new RuntimeException('Authoritative market price is unavailable.');
            }
        }catch(\Throwable $e){
            return $this->finish($bot,$this->log($bot,null,null,0,0,0,'failed',substr($e->getMessage(),0,255),$instrument));
        }

        $today=$bot->executions()->where('status','completed')->whereDate('executed_at',today())->count();
        if($today >= $bot->max_daily_trades){
            return $this->finish($bot,$this->log($bot,null,null,0,$price,0,'skipped','Daily trade limit reached.',$instrument));
        }

        if($bot->strategy==='price_below' && $price>(float)$bot->trigger_price){
            return $this->finish($bot,$this->log($bot,null,null,0,$price,0,'skipped','Price is above trigger.',$instrument));
        }
        if($bot->strategy==='price_above' && $price<(float)$bot->trigger_price){
            return $this->finish($bot,$this->log($bot,null,null,0,$price,0,'skipped','Price is below trigger.',$instrument));
        }

        try{
            $size=$this->size($bot,$instrument,$price,$marketplace);
        }catch(\Throwable $e){
            return $this->finish($bot,$this->log($bot,null,null,0,$price,0,'failed',substr($e->getMessage(),0,255),$instrument));
        }

        if($bot->action==='buy' && $bot->max_total_spend!==null){
            $remaining=max(0,(float)$bot->max_total_spend-(float)$bot->spent_total);
            if($size['estimated_capital']>$remaining+0.000001){
                return $this->finish($bot,$this->log(
                    $bot,null,null,$size['display_quantity'],$price,0,'skipped','Maximum total spend reached.',$instrument
                ));
            }
        }

        $context=[
            'execution_source'=>'trading_bot',
            'execution_source_id'=>$bot->id,
            'context_type'=>'trading_bot',
            'context_id'=>$bot->id,
            'actor_type'=>'system',
            'actor_id'=>null,
            'exit_reason'=>'bot_exit',
            'metadata'=>[
                'trading_bot_id'=>$bot->id,
                'bot_subscription_id'=>$subscription->id,
                'bot_product_id'=>$subscription->bot_product_id,
            ],
        ];

        $risk=[
            'stop_loss_percent'=>$bot->stop_loss_percent,
            'take_profit_percent'=>$bot->take_profit_percent,
            'duration_minutes'=>$bot->position_duration_minutes,
            'metadata'=>[
                'bot_id'=>$bot->id,
                'bot_subscription_id'=>$subscription->id,
            ],
        ];

        try{
            if($bot->action==='buy'){
                $key=$this->idempotencyKey($bot,$manual,'buy');
                $order=$this->brokerOrders->placeMarketOrder(
                    $user,
                    $instrument,
                    'buy',
                    $size['order_quantity'],
                    $size['quantity_mode'],
                    $key,
                    $risk,
                    $context
                );
            }else{
                $position=TradePosition::query()
                    ->where('user_id',$user->id)
                    ->where('market_instrument_id',$instrument->id)
                    ->where('marketplace',$marketplace)
                    ->where('context_type','trading_bot')
                    ->where('context_id',$bot->id)
                    ->whereIn('status',['open','exit_queued'])
                    ->where('open_quantity','>',0)
                    ->oldest('opened_at')
                    ->first();

                if(!$position){
                    return $this->finish($bot,$this->log(
                        $bot,null,null,$size['display_quantity'],$price,0,'skipped',
                        'No bot-attributed open position is available to sell in the active marketplace.',$instrument
                    ));
                }

                $qty=min($size['display_quantity'],(float)$position->open_quantity);
                if($qty<=0){
                    return $this->finish($bot,$this->log($bot,null,null,0,$price,0,'failed','Executable close quantity is zero.',$instrument));
                }

                $key=$this->idempotencyKey($bot,$manual,'sell',$position->id);
                $order=$this->brokerOrders->placePositionClose($user,$position,$qty,$key,$context);
            }

            $order->loadMissing('execution');
            $execution=$order->execution;
            if($order->status!==BrokerOrder::STATUS_FILLED || !$execution || $execution->status!=='completed'){
                throw new RuntimeException('Bot BrokerOrder did not produce a completed unified execution receipt.');
            }

            $amount=(float)($execution->settlement_amount ?? $execution->gross_value);

            $record=$this->log(
                $bot,
                $order,
                $execution,
                (float)$execution->quantity,
                (float)$execution->price,
                $amount,
                'completed',
                null,
                $instrument
            );

            if($bot->action==='buy'){
                $spent=(float)$bot->executions()
                    ->where('status','completed')
                    ->where('action','buy')
                    ->sum('amount');
                $bot->update(['spent_total'=>round($spent,2)]);
            }

            NotificationService::createSystemNotification(
                $user,
                'Trading bot executed',
                $bot->name.' completed a '.$bot->action.' on '.($instrument->display_symbol ?: $instrument->symbol).' for $'.number_format($amount,2).'.',
                [
                    'type'=>'trading_bot',
                    'bot_id'=>$bot->id,
                    'execution_id'=>$record->id,
                    'broker_order_id'=>$order->id,
                    'market_execution_transaction_id'=>$execution->id,
                    'position_id'=>$execution->trade_position_id,
                    'market_instrument_id'=>$instrument->id,
                ]
            );

            return $this->finish($bot,$record);
        }catch(\Throwable $e){
            Log::warning('Trading bot execution failed',[
                'bot_id'=>$bot->id,
                'market_instrument_id'=>$instrument->id,
                'error'=>$e->getMessage(),
            ]);

            $existingOrder=isset($key)
                ? BrokerOrder::query()->where('user_id',$user->id)->where('idempotency_key',$key)->first()
                : null;
            $existingOrder?->loadMissing('execution');

            return $this->finish($bot,$this->log(
                $bot,
                $existingOrder,
                $existingOrder?->execution,
                $size['display_quantity'],
                $price,
                0,
                'failed',
                substr($e->getMessage(),0,255),
                $instrument
            ));
        }
    }

    private function size(TradingBot $bot,MarketInstrument $instrument,float $price,string $marketplace):array
    {
        $configuredQty=(float)($bot->quantity_per_trade??0);
        $configuredAmount=(float)($bot->amount_per_trade??0);
        $walletCurrency=strtoupper((string)($bot->user?->wallet?->currency ?: 'USD'));
        $requireFresh=$marketplace==='live';

        if($configuredQty>0){
            $units=$configuredQty;
            $estimated=$instrument->isStock()
                ? round($units*$price,2)
                : round($this->settlement->amountForBaseUnits($instrument,$units,$price,$walletCurrency,$requireFresh),2);

            return [
                'order_quantity'=>$units,
                'quantity_mode'=>'units',
                'display_quantity'=>$units,
                'estimated_capital'=>$estimated,
            ];
        }

        if($configuredAmount<=0){
            throw new RuntimeException('Trade size is not configured.');
        }

        if($instrument->isStock()){
            $units=$configuredAmount/$price;
            if($units<=0){
                throw new RuntimeException('Stock bot quantity resolved to zero.');
            }

            return [
                'order_quantity'=>$units,
                'quantity_mode'=>'units',
                'display_quantity'=>$units,
                'estimated_capital'=>round($units*$price,2),
            ];
        }

        $oneUnit=$this->settlement->amountForBaseUnits($instrument,1.0,$price,$walletCurrency,$requireFresh);
        if($oneUnit<=0){
            throw new RuntimeException('Settlement conversion is unavailable for bot sizing.');
        }

        $units=round($configuredAmount/$oneUnit,8);
        if($instrument->isForex() && $units<1){
            throw new RuntimeException('Forex bot amount resolves below the minimum executable base-unit quantity.');
        }
        if($units<=0){
            throw new RuntimeException('Bot amount resolves below supported execution precision.');
        }

        if($instrument->isCrypto() && $bot->action==='buy'){
            return [
                'order_quantity'=>$configuredAmount,
                'quantity_mode'=>'settlement_amount',
                'display_quantity'=>$units,
                'estimated_capital'=>round($configuredAmount,2),
            ];
        }

        return [
            'order_quantity'=>$units,
            'quantity_mode'=>'units',
            'display_quantity'=>$units,
            'estimated_capital'=>round($this->settlement->amountForBaseUnits($instrument,$units,$price,$walletCurrency,$requireFresh),2),
        ];
    }

    private function idempotencyKey(TradingBot $bot,bool $manual,string $side,?int $positionId=null):string
    {
        $token=$manual
            ? 'manual-'.now()->utc()->format('YmdHi')
            : 'due-'.($bot->next_run_at?->copy()->utc()->format('YmdHis') ?? now()->utc()->startOfMinute()->format('YmdHis'));

        return implode(':',array_filter([
            'trading-bot',
            (string)$bot->id,
            $side,
            $positionId ? 'position-'.$positionId : null,
            $token,
        ]));
    }

    private function finish(TradingBot $bot,TradingBotExecution $execution):TradingBotExecution
    {
        $bot->update([
            'last_run_at'=>now(),
            'next_run_at'=>now()->addMinutes(max(5,(int)$bot->interval_minutes)),
        ]);
        return $execution;
    }

    private function log(
        TradingBot $bot,
        ?BrokerOrder $order,
        ?MarketExecutionTransaction $execution,
        float $qty,
        float $price,
        float $amount,
        string $status,
        ?string $reason,
        ?MarketInstrument $instrument
    ):TradingBotExecution {
        $attributes=[
            'trading_bot_id'=>$bot->id,
            'bot_subscription_id'=>$bot->subscription?->id,
            'market_instrument_id'=>$instrument?->id,
            'broker_order_id'=>$order?->id,
            'market_execution_transaction_id'=>$execution?->id,
            'stock_transaction_id'=>$execution?->native_type==='stock_transaction' ? $execution->native_id : null,
            'action'=>$bot->action,
            'quantity'=>$qty,
            'price'=>$price,
            'amount'=>$amount,
            'status'=>$status,
            'reason'=>$reason,
            'executed_at'=>$execution?->executed_at ?? now(),
        ];

        if($order){
            return TradingBotExecution::updateOrCreate(
                ['broker_order_id'=>$order->id],
                $attributes
            );
        }

        return TradingBotExecution::create($attributes);
    }
}
