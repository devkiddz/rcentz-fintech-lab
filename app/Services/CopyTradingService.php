<?php
namespace App\Services;
use App\Models\CopyRelationship; use App\Models\CopyTradeExecution; use App\Models\StockHolding; use App\Models\StockTransaction; use Illuminate\Support\Facades\Log;
class CopyTradingService {
 public function __construct(private StockTradeExecutor $executor){}
 public function mirrorCompletedTrade(StockTransaction $providerTrade): void {
  if($providerTrade->status!=='completed')return;
  $relationships=CopyRelationship::with(['follower.kyc','provider.copyTraderProfile','strategy'])->where('provider_id',$providerTrade->user_id)->where('status','active')->whereHas('strategy',fn($q)=>$q->where('is_active',true)->where('is_public',true))->get();
  foreach($relationships as $r){$this->mirrorOne($r,$providerTrade);}
 }
 private function mirrorOne(CopyRelationship $r,StockTransaction $providerTrade):void{
  $requested=min((float)$providerTrade->total_amount*((float)$r->copy_ratio_percent/100),(float)$r->max_trade_amount);
  if($providerTrade->type==='buy')$requested=min($requested,$r->remaining_allocation);
  if($requested<=0){$this->execution($r,$providerTrade,null,0,0,'skipped','Allocation or trade cap reached.');return;}
  $f=$r->follower; if(!$f || !$f->kyc || !$f->kyc->isApproved()){$this->execution($r,$providerTrade,null,$requested,0,'skipped','Follower KYC is not approved.');return;}
  try{
   $price=(float)$providerTrade->price_per_share; $qty=$requested/$price;
   if($providerTrade->type==='sell'){$h=StockHolding::where('user_id',$f->id)->where('stock_id',$providerTrade->stock_id)->first(); if(!$h){$this->execution($r,$providerTrade,null,$requested,0,'skipped','No copied holding is available to sell.');return;} $qty=min($qty,(float)$h->quantity);}
   if($qty<=0){$this->execution($r,$providerTrade,null,$requested,0,'skipped','Executable quantity is zero.');return;}
   $trade=$providerTrade->type==='buy'?$this->executor->buy($f,$providerTrade->stock,$qty,'copy_trade',$r->id):$this->executor->sell($f,$providerTrade->stock,$qty,'copy_trade',$r->id);
   $executed=(float)$trade->total_amount;
   $r->update(['used_amount'=>$providerTrade->type==='buy'?min((float)$r->allocation_limit,(float)$r->used_amount+$executed):max(0,(float)$r->used_amount-$executed)]);
   $this->execution($r,$providerTrade,$trade,$requested,$executed,'completed',null);
   NotificationService::createSystemNotification($f,'Copy trade executed',$providerTrade->stock->symbol.' '.$providerTrade->type.' mirrored for $'.number_format($executed,2).'.',['type'=>'copy_trade','relationship_id'=>$r->id,'stock_transaction_id'=>$trade->id]);
  }catch(\Throwable $e){Log::warning('Copy trade failed',['relationship_id'=>$r->id,'provider_trade_id'=>$providerTrade->id,'error'=>$e->getMessage()]);$this->execution($r,$providerTrade,null,$requested,0,'failed',substr($e->getMessage(),0,255));}
 }
 private function execution(CopyRelationship $r,StockTransaction $p,?StockTransaction $f,float $requested,float $executed,string $status,?string $reason):void{CopyTradeExecution::create(['copy_relationship_id'=>$r->id,'provider_stock_transaction_id'=>$p->id,'follower_stock_transaction_id'=>$f?->id,'action'=>$p->type,'requested_amount'=>$requested,'executed_amount'=>$executed,'status'=>$status,'failure_reason'=>$reason,'executed_at'=>now()]);}
}
