<?php
namespace App\Services;

use App\Models\TradingBot;
use App\Models\TradingBotExecution;
use App\Models\TradePosition;
use Illuminate\Support\Facades\Log;

class TradingBotService
{
    public function __construct(
        private MarketTradeContractEngine $marketTrades
    ){}

    public function run(TradingBot $bot,bool $manual=false):TradingBotExecution
    {
        $bot->loadMissing(['user.kyc','stock','subscription.product']);
        $stock=$bot->stock; $user=$bot->user; $price=(float)$stock->current_price; $subscription=$bot->subscription;
        $marketplace=$this->marketTrades->activeMarketplace();

        if(!$subscription || !$subscription->is_usable) return $this->log($bot,null,0,$price,0,'skipped','Bot subscription is not active.');
        if(!$manual && ($bot->status!=='active' || ($bot->next_run_at && $bot->next_run_at->isFuture()))) return $this->log($bot,null,0,$price,0,'skipped','Bot is not due.');
        if(!$user->kyc || !$user->kyc->isApproved()) return $this->log($bot,null,0,$price,0,'skipped','KYC is not approved.');

        $today=$bot->executions()->where('status','completed')->whereDate('executed_at',today())->count();
        if($today >= $bot->max_daily_trades) return $this->finish($bot,$this->log($bot,null,0,$price,0,'skipped','Daily trade limit reached.'));

        if($bot->strategy==='price_below' && $price>(float)$bot->trigger_price) return $this->finish($bot,$this->log($bot,null,0,$price,0,'skipped','Price is above trigger.'));
        if($bot->strategy==='price_above' && $price<(float)$bot->trigger_price) return $this->finish($bot,$this->log($bot,null,0,$price,0,'skipped','Price is below trigger.'));

        $qty=(float)($bot->quantity_per_trade??0);
        if($qty<=0 && (float)$bot->amount_per_trade>0) $qty=(float)$bot->amount_per_trade/$price;
        if($qty<=0) return $this->finish($bot,$this->log($bot,null,0,$price,0,'failed','Trade size is not configured.'));

        $estimated=round($qty*$price,2);
        if($bot->action==='buy' && $bot->max_total_spend!==null && ((float)$bot->spent_total+$estimated)>(float)$bot->max_total_spend){
            return $this->finish($bot,$this->log($bot,null,$qty,$price,0,'skipped','Maximum total spend reached.'));
        }

        try{
            if($bot->action==='buy'){
                $trade=$this->marketTrades->openLong(
                    $user,
                    $stock,
                    $qty,
                    'trading_bot',
                    'trading_bot',
                    [
                        'stop_loss_percent'=>$bot->stop_loss_percent,
                        'take_profit_percent'=>$bot->take_profit_percent,
                        'duration_minutes'=>$bot->position_duration_minutes,
                        'metadata'=>['bot_id'=>$bot->id],
                    ],
                    $bot->id,
                    $bot->id,
                    null,
                    'system',
                    null,
                    null,
                    $marketplace
                );
                $bot->increment('spent_total',(float)$trade->total_amount);
            }else{
                $position=TradePosition::where('user_id',$user->id)
                    ->where('stock_id',$stock->id)
                    ->where('marketplace',$marketplace)
                    ->where('context_type','trading_bot')
                    ->where('context_id',$bot->id)
                    ->whereIn('status',['open','exit_queued'])
                    ->where('open_quantity','>',0)
                    ->oldest('opened_at')
                    ->first();

                if(! $position){
                    return $this->finish($bot,$this->log($bot,null,$qty,$price,0,'skipped','No bot-attributed open position is available to sell in the active marketplace.'));
                }

                $qty=min($qty,(float)$position->open_quantity);
                $trade=$this->marketTrades->closePosition($position,'bot_exit',$qty,'system',null);
            }

            $amount=(float)$trade->total_amount;
            $execution=$this->log($bot,$trade,$qty,(float)$trade->price_per_share,$amount,'completed',null);

            NotificationService::createSystemNotification(
                $user,'Trading bot executed',
                $bot->name.' completed a '.$bot->action.' on '.$stock->symbol.' for $'.number_format($amount,2).'.',
                ['type'=>'trading_bot','bot_id'=>$bot->id,'execution_id'=>$execution->id,'position_id'=>$trade->trade_position_id]
            );

            return $this->finish($bot,$execution);
        }catch(\Throwable $e){
            Log::warning('Trading bot execution failed',['bot_id'=>$bot->id,'error'=>$e->getMessage()]);
            return $this->finish($bot,$this->log($bot,null,$qty,$price,0,'failed',substr($e->getMessage(),0,255)));
        }
    }

    private function finish(TradingBot $bot,TradingBotExecution $execution):TradingBotExecution
    {
        $bot->update(['last_run_at'=>now(),'next_run_at'=>now()->addMinutes(max(5,(int)$bot->interval_minutes))]);
        return $execution;
    }

    private function log(TradingBot $bot,$trade,float $qty,float $price,float $amount,string $status,?string $reason):TradingBotExecution
    {
        return TradingBotExecution::create([
            'trading_bot_id'=>$bot->id,
            'bot_subscription_id'=>$bot->subscription?->id,
            'stock_transaction_id'=>$trade?->id,
            'action'=>$bot->action,
            'quantity'=>$qty,
            'price'=>$price,
            'amount'=>$amount,
            'status'=>$status,
            'reason'=>$reason,
            'executed_at'=>now(),
        ]);
    }
}
