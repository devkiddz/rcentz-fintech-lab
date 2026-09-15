<?php
namespace App\Http\Controllers;

use App\Models\BotProduct;
use App\Models\BotSubscription;
use App\Models\PaymentMethod;
use App\Models\TradingBot;
use App\Models\TradingBotExecution;
use App\Services\FinancialActivityService;
use App\Services\TradingBotService;
use App\Services\TradingPerformanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TradingBotController extends Controller
{
    /** Backward-compatible V1 entry point. */
    public function index()
    {
        return redirect()->route('ai-bots.marketplace');
    }

    public function marketplace(TradingPerformanceService $performance)
    {
        $products=BotProduct::with('stock')->withCount(['subscriptions as subscriber_count'=>fn($q)=>$q->whereIn('status',['active','paused'])])->where('is_active',true)->latest()->get();
        foreach ($products as $product) { $product->performance_metrics = $performance->botProduct($product); }
        return view('ai-bots.marketplace',compact('products'));
    }

    public function show(BotProduct $product)
    {
        abort_unless($product->is_active,404);
        $product->load('stock');
        $existing=Auth::user()->botSubscriptions()->where('bot_product_id',$product->id)->whereIn('status',['active','paused'])->latest()->first();
        return view('ai-bots.show',compact('product','existing'));
    }

    public function subscribe(BotProduct $product, FinancialActivityService $activity)
    {
        $user=Auth::user();
        abort_unless($product->is_active,404);
        abort_if($user->botSubscriptions()->where('bot_product_id',$product->id)->whereIn('status',['active','paused'])->exists(),422,'You already have access to this bot.');

        try {
            $subscription=DB::transaction(function() use($user,$product,$activity){
                $wallet=$user->wallet()->lockForUpdate()->firstOrFail();
                $price=(float)$product->price;
                if((float)$wallet->available_balance < max($price,(float)$product->minimum_balance)){
                    throw new \RuntimeException('Available balance does not meet this bot requirement.');
                }

                $before=$activity->snapshot($wallet);
                if($price>0){
                    $method=PaymentMethod::firstOrCreate(['name'=>'Bot Marketplace'],['type'=>'traditional','details'=>'Internal bot marketplace purchase.','is_active'=>true,'allow_deposit'=>false,'allow_withdraw'=>false]);
                    $reference=$activity->reference('BOT-SUB');
                    $walletTx=$wallet->transactions()->create([
                        'payment_method_id'=>$method->id,'type'=>'investment','direction'=>'debit','amount'=>$price,'fee'=>0,
                        'status'=>'completed','reference_id'=>$reference,'description'=>'AI Trading Bot access: '.$product->name
                    ]);
                    $wallet->deductFunds($price);
                    $activity->record($user,'bot.subscription','AI Bot subscription','Purchased access to '.$product->name.'.',$reference,'completed','debit',$price,$wallet,$walletTx,null,$before,['bot_product_id'=>$product->id],'user',$user->id);
                }

                $endsAt=match($product->billing_period){
                    'monthly'=>now()->addMonth(),
                    'quarterly'=>now()->addMonths(3),
                    'yearly'=>now()->addYear(),
                    default=>null,
                };

                $bot=TradingBot::create([
                    'user_id'=>$user->id,
                    'stock_id'=>$product->stock_id,
                    'name'=>$product->name,
                    'strategy'=>$product->strategy,
                    'action'=>$product->action,
                    'amount_per_trade'=>$product->default_trade_amount,
                    'quantity_per_trade'=>null,
                    'trigger_price'=>$product->default_trigger_price,
                    'interval_minutes'=>$product->default_interval_minutes,
                    'max_daily_trades'=>$product->default_max_daily_trades,
                    'max_total_spend'=>$product->max_user_allocation,
                    'spent_total'=>0,
                    'status'=>'paused',
                    'next_run_at'=>now(),
                ]);

                return BotSubscription::create([
                    'user_id'=>$user->id,'bot_product_id'=>$product->id,'trading_bot_id'=>$bot->id,'price_paid'=>$price,
                    'status'=>'paused','starts_at'=>now(),'ends_at'=>$endsAt
                ]);
            });

            return redirect()->route('ai-bots.my-bots')->with('success','Bot access granted. Configure it, then activate when ready.');
        } catch(\Throwable $e) {
            return back()->with('error',$e->getMessage());
        }
    }

    public function myBots(TradingPerformanceService $performance)
    {
        $subscriptions=Auth::user()->botSubscriptions()->with(['product.stock','bot'])->latest()->get();
        foreach ($subscriptions as $subscription) { $subscription->performance_metrics = $performance->botSubscription($subscription); }
        return view('ai-bots.my-bots',compact('subscriptions'));
    }

    public function subscriptions()
    {
        $subscriptions=Auth::user()->botSubscriptions()->with(['product.stock','bot'])->latest()->paginate(30);
        return view('ai-bots.subscriptions',compact('subscriptions'));
    }

    public function performance(TradingPerformanceService $performance)
    {
        $subscriptions = Auth::user()->botSubscriptions()->with(['product.stock','bot'])->latest()->get();
        $summary = ['trade_count'=>0,'completed_count'=>0,'volume'=>0.0,'profit_loss'=>0.0,'winning_trades'=>0,'losing_trades'=>0];

        foreach ($subscriptions as $subscription) {
            $metrics = $performance->botSubscription($subscription);
            foreach (['trade_count','completed_count','winning_trades','losing_trades'] as $key) $summary[$key] += $metrics[$key];
            foreach (['volume','profit_loss'] as $key) $summary[$key] += $metrics[$key];
        }

        $summary['return_percent'] = $summary['volume'] > 0 ? ($summary['profit_loss'] / $summary['volume']) * 100 : 0;
        $resolved = $summary['winning_trades'] + $summary['losing_trades'];
        $summary['win_rate'] = $resolved > 0 ? ($summary['winning_trades'] / $resolved) * 100 : 0;

        $executions = \App\Models\TradingBotExecution::with(['subscription.product','bot.stock'])
            ->whereHas('subscription', fn ($q) => $q->where('user_id', Auth::id()))
            ->latest()->paginate(40);

        return view('ai-bots.performance', compact('executions','summary'));
    }

    public function configure(BotSubscription $subscription)
    {
        $this->own($subscription);
        $subscription->load(['product.stock','bot']);
        abort_unless($subscription->is_usable, 422, 'This bot subscription is not usable.');

        return view('ai-bots.configure', compact('subscription'));
    }

    public function executionShow(TradingBotExecution $execution)
    {
        $execution->load(['subscription.product','subscription.user','bot.stock','stockTransaction']);
        abort_unless($execution->subscription?->user_id === Auth::id(), 403);

        $currentPrice = (float) ($execution->bot?->stock?->current_price ?? 0);
        $entryPrice = (float) $execution->price;
        $quantity = (float) $execution->quantity;

        $profitLoss = 0.0;
        if ($execution->status === 'completed' && $entryPrice > 0 && $quantity > 0 && $currentPrice > 0) {
            $profitLoss = $execution->action === 'sell'
                ? ($entryPrice - $currentPrice) * $quantity
                : ($currentPrice - $entryPrice) * $quantity;
        }

        $returnPercent = (float) $execution->amount > 0
            ? ($profitLoss / (float) $execution->amount) * 100
            : 0;

        return view('ai-bots.execution-show', compact('execution','currentPrice','profitLoss','returnPercent'));
    }

    public function update(Request $request, BotSubscription $subscription)
    {
        $this->own($subscription);
        abort_unless($subscription->is_usable,422,'This bot subscription is not usable.');
        $product=$subscription->product;
        $bot=$subscription->bot;

        $data=$request->validate([
            'amount_per_trade'=>'nullable|numeric|min:1|max:100000',
            'quantity_per_trade'=>'nullable|numeric|min:0.000001|max:100000',
            'trigger_price'=>'nullable|numeric|min:0.01',
            'interval_minutes'=>'required|integer|min:5|max:10080',
            'max_daily_trades'=>'required|integer|min:1|max:24',
            'max_total_spend'=>'nullable|numeric|min:1|max:1000000',
        ]);

        if(!$product->allow_user_trade_amount){
            $data['amount_per_trade']=$product->default_trade_amount;
            $data['quantity_per_trade']=null;
        }
        // DCA is time-driven, so a price trigger has no meaning and must remain null.
        if($product->strategy === 'dca'){
            $data['trigger_price'] = null;
        } elseif(!$product->allow_user_trigger_price){
            $data['trigger_price']=$product->default_trigger_price;
        }

        if($product->max_user_allocation!==null && isset($data['max_total_spend'])){
            $data['max_total_spend']=min((float)$data['max_total_spend'],(float)$product->max_user_allocation);
        }
        $bot->update($data);
        return back()->with('success','Bot configuration updated.');
    }

    public function toggle(BotSubscription $subscription)
    {
        $this->own($subscription);
        abort_unless($subscription->is_usable,422,'This bot subscription is not usable.');
        $new=$subscription->status==='active'?'paused':'active';
        $subscription->update(['status'=>$new]);
        $subscription->bot->update(['status'=>$new,'next_run_at'=>$new==='active'?now():$subscription->bot->next_run_at]);
        return back()->with('success','Bot '.$new.'.');
    }

    public function run(BotSubscription $subscription, TradingBotService $service)
    {
        $this->own($subscription);
        abort_unless($subscription->is_usable,422,'This bot subscription is not usable.');
        $execution=$service->run($subscription->bot,true);
        return back()->with($execution->status==='completed'?'success':'error',$execution->status==='completed'?'Bot execution completed.':($execution->reason?:'Bot did not execute.'));
    }

    public function cancel(BotSubscription $subscription)
    {
        $this->own($subscription);
        $subscription->update(['status'=>'cancelled','cancelled_at'=>now()]);
        if($subscription->bot)$subscription->bot->update(['status'=>'paused']);
        return back()->with('success','Bot subscription cancelled.');
    }

    private function own(BotSubscription $subscription):void
    {
        abort_unless($subscription->user_id===Auth::id(),403);
    }
}
