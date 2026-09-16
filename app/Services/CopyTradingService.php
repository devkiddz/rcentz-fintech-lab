<?php

namespace App\Services;

use App\Models\CopyRelationship;
use App\Models\CopyTradeExecution;
use App\Models\StockTransaction;
use App\Models\TradePosition;
use Illuminate\Support\Facades\Log;

class CopyTradingService
{
    public function __construct(
        private MarketTradeContractEngine $marketTrades
    ) {}

    public function mirrorCompletedTrade(StockTransaction $providerTrade): void
    {
        if($providerTrade->status!=='completed') return;

        $query=CopyRelationship::with(['follower.kyc','provider.copyTraderProfile','strategy'])
            ->where('provider_id',$providerTrade->user_id)
            ->where('status','active')
            ->where(fn($q)=>$q->whereNull('ends_at')->orWhere('ends_at','>',now()))
            ->whereHas('strategy',fn($q)=>$q->where('is_active',true)->where('is_public',true));

        if($providerTrade->copy_strategy_id){
            $query->where('copy_strategy_id',$providerTrade->copy_strategy_id);
        }else{
            Log::warning('Provider trade lacks strategy attribution; legacy provider-wide copy selection used.',[
                'provider_trade_id'=>$providerTrade->id
            ]);
        }

        foreach($query->get() as $relationship){
            $this->mirrorOne($relationship,$providerTrade);
        }
    }

    private function mirrorOne(CopyRelationship $relationship,StockTransaction $providerTrade):void
    {
        $requested=min(
            (float)$providerTrade->total_amount*((float)$relationship->copy_ratio_percent/100),
            (float)$relationship->max_trade_amount
        );

        if($providerTrade->type==='buy'){
            $requested=min($requested,(float)$relationship->remaining_allocation);
        }

        if($requested<=0){
            $this->execution($relationship,$providerTrade,null,0,0,'skipped','Allocation or trade cap reached.');
            return;
        }

        $follower=$relationship->follower;
        if(!$follower || !$follower->kyc || !$follower->kyc->isApproved()){
            $this->execution($relationship,$providerTrade,null,$requested,0,'skipped','Follower KYC is not approved.');
            return;
        }

        try{
            $providerPrice=(float)$providerTrade->price_per_share;
            $qty=$requested/$providerPrice;
            $marketplace=$providerTrade->marketplace ?: 'live';

            if($providerTrade->type==='buy'){
                $providerPosition=$providerTrade->position;
                $trade=$this->marketTrades->openLong(
                    $follower,
                    $providerTrade->stock,
                    $qty,
                    'copy_trade',
                    'copy_relationship',
                    [
                        'stop_loss_percent'=>$providerPosition?->stop_loss_percent,
                        'take_profit_percent'=>$providerPosition?->take_profit_percent,
                        'duration_minutes'=>$providerPosition?->duration_minutes,
                        'metadata'=>[
                            'copy_relationship_id'=>$relationship->id,
                            'provider_trade_id'=>$providerTrade->id,
                        ],
                    ],
                    $relationship->id,
                    $relationship->id,
                    $relationship->copy_strategy_id,
                    'system',
                    null,
                    $providerPosition?->id,
                    $marketplace
                );
            }else{
                $providerPositionId=$providerTrade->trade_position_id;

                $position=TradePosition::where('user_id',$follower->id)
                    ->where('stock_id',$providerTrade->stock_id)
                    ->where('marketplace',$marketplace)
                    ->where('context_type','copy_relationship')
                    ->where('context_id',$relationship->id)
                    ->when($providerPositionId,fn($q)=>$q->where('source_position_id',$providerPositionId))
                    ->whereIn('status',['open','exit_queued'])
                    ->where('open_quantity','>',0)
                    ->oldest('opened_at')
                    ->first();

                if(!$position){
                    $this->execution($relationship,$providerTrade,null,$requested,0,'skipped','No relationship-attributed open position is available to sell in the provider marketplace.');
                    return;
                }

                $qty=min($qty,(float)$position->open_quantity);
                if($qty<=0){
                    $this->execution($relationship,$providerTrade,null,$requested,0,'skipped','Executable quantity is zero.');
                    return;
                }

                $trade=$this->marketTrades->closePosition(
                    $position,
                    'provider_exit',
                    $qty,
                    'system',
                    null,
                    true,
                    (float)$providerTrade->price_per_share
                );
            }

            $executed=(float)$trade->total_amount;

            $relationship->update([
                'used_amount'=>$providerTrade->type==='buy'
                    ? min((float)$relationship->allocation_limit,(float)$relationship->used_amount+$executed)
                    : max(0,(float)$relationship->used_amount-$executed)
            ]);

            $this->execution($relationship,$providerTrade,$trade,$requested,$executed,'completed',null);

            NotificationService::createSystemNotification(
                $follower,'Copy trade executed',
                $providerTrade->stock->symbol.' '.$providerTrade->type.' mirrored for $'.number_format($executed,2).'.',
                [
                    'type'=>'copy_trade',
                    'relationship_id'=>$relationship->id,
                    'stock_transaction_id'=>$trade->id,
                    'position_id'=>$trade->trade_position_id,
                ]
            );
        }catch(\Throwable $e){
            Log::warning('Copy trade failed',[
                'relationship_id'=>$relationship->id,
                'provider_trade_id'=>$providerTrade->id,
                'error'=>$e->getMessage()
            ]);
            $this->execution($relationship,$providerTrade,null,$requested,0,'failed',substr($e->getMessage(),0,255));
        }
    }

    private function execution(
        CopyRelationship $relationship,StockTransaction $providerTrade,?StockTransaction $followerTrade,
        float $requested,float $executed,string $status,?string $reason
    ):void{
        CopyTradeExecution::create([
            'copy_relationship_id'=>$relationship->id,
            'provider_stock_transaction_id'=>$providerTrade->id,
            'follower_stock_transaction_id'=>$followerTrade?->id,
            'action'=>$providerTrade->type,
            'requested_amount'=>$requested,
            'executed_amount'=>$executed,
            'status'=>$status,
            'failure_reason'=>$reason,
            'executed_at'=>now(),
        ]);
    }
}
