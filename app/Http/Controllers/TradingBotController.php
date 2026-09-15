<?php
namespace App\Http\Controllers;

use App\Models\BotProduct;
use App\Models\BotSubscription;
use App\Models\PaymentMethod;
use App\Models\TradingBot;
use App\Models\TradingBotExecution;
use App\Models\StockQuote;
use App\Models\StockCandle;
use App\Models\StockNews;
use App\Services\FinancialActivityService;
use App\Services\TradingBotService;
use App\Services\TradingPerformanceService;
use App\Services\MarketSessionService;
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
        $products = BotProduct::with('stock')
            ->withCount(['subscriptions as subscriber_count' => fn ($q) => $q->whereIn('status', ['active','paused'])])
            ->where('is_active', true)
            ->latest()
            ->get();

        foreach ($products as $product) {
            $product->performance_metrics = $performance->botProduct($product);

            $symbol = $product->stock?->symbol;
            $product->quote_history = $this->marketSeries($symbol, 36);
        }

        return view('ai-bots.marketplace', compact('products'));
    }

    public function show(BotProduct $product, TradingPerformanceService $performance)
    {
        abort_unless($product->is_active,404);
        $product->load('stock');

        $existing = Auth::user()->botSubscriptions()
            ->where('bot_product_id', $product->id)
            ->whereIn('status', ['active','paused'])
            ->latest()
            ->first();

        $metrics = $performance->botProduct($product);
        $symbol = $product->stock?->symbol;

        $quoteHistory = $this->marketSeries($symbol, 48);

        $latestNews = $symbol
            ? StockNews::where('symbol', $symbol)->latest('published_at')->limit(4)->get()
            : collect();

        $marketStatus = app(MarketSessionService::class)->status();

        return view('ai-bots.show', compact(
            'product','existing','metrics','quoteHistory','latestNews','marketStatus'
        ));
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

                $endsAt = match($product->billing_period){
                    'quarterly' => now()->addMonths(3),
                    'yearly' => now()->addYear(),
                    default => now()->addMonth(),
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
        $subscriptions = Auth::user()->botSubscriptions()
            ->with(['product.stock','bot'])
            ->latest()
            ->get();

        foreach ($subscriptions as $subscription) {
            $subscription->performance_metrics = $performance->botSubscription($subscription);

            $symbol = $subscription->product?->stock?->symbol;

            $quotes = collect($this->marketSeries($symbol, 96));

            $executions = TradingBotExecution::where('bot_subscription_id', $subscription->id)
                ->where('status', 'completed')
                ->orderBy('executed_at')
                ->get(['action','price','quantity','amount','executed_at']);

            $completed = $executions->filter(fn ($execution) => (float) $execution->quantity > 0);
            $totalQty = (float) $completed->sum('quantity');
            $weightedEntry = $totalQty > 0
                ? (float) ($completed->sum(fn ($execution) => (float) $execution->price * (float) $execution->quantity) / $totalQty)
                : null;

            $subscription->price_chart = [
                'symbol' => $symbol,
                'current' => (float) ($subscription->product?->stock?->current_price ?? 0),
                'previous_close' => (float) ($subscription->product?->stock?->previous_close ?? 0),
                'average_entry' => $weightedEntry,
                'quotes' => $quotes->values()->all(),
                'executions' => $executions->map(fn ($execution) => [
                    'action' => strtolower((string) $execution->action),
                    'price' => (float) $execution->price,
                    'quantity' => (float) $execution->quantity,
                    'amount' => (float) $execution->amount,
                    'time' => optional($execution->executed_at)->toIso8601String(),
                    'label' => optional($execution->executed_at)->format('H:i'),
                ])->values()->all(),
            ];
        }

        return view('ai-bots.my-bots', compact('subscriptions'));
    }

    public function subscriptions()
    {
        $subscriptions=Auth::user()->botSubscriptions()->with(['product.stock','bot'])->latest()->paginate(30);
        return view('ai-bots.subscriptions',compact('subscriptions'));
    }

    public function performance(TradingPerformanceService $performance)
    {
        $subscriptions = Auth::user()->botSubscriptions()->with(['product.stock','bot'])->latest()->get();
        $summary = ['trade_count'=>0,'completed_count'=>0,'volume'=>0.0,'profit_loss'=>0.0,'realized_profit_loss'=>0.0,'open_profit_loss'=>0.0,'performance_basis'=>0.0,'positive_count'=>0,'negative_count'=>0,'neutral_count'=>0];
        $botCards = collect();

        foreach ($subscriptions as $subscription) {
            $metrics = $performance->botSubscription($subscription);
            foreach (['trade_count','completed_count','positive_count','negative_count','neutral_count'] as $key) {
                $summary[$key] += $metrics[$key] ?? 0;
            }
            foreach (['volume','profit_loss','realized_profit_loss','open_profit_loss','performance_basis'] as $key) {
                $summary[$key] += $metrics[$key] ?? 0;
            }

            $symbol = $subscription->product?->stock?->symbol;
            $quotes = collect($this->marketSeries($symbol, 48));

            $stock = $subscription->product?->stock;

            $botCards->push([
                'name' => $subscription->product?->name ?? $subscription->bot?->name ?? 'Bot',
                'symbol' => $symbol,
                'status' => $subscription->bot?->status,
                'current' => (float) ($stock?->current_price ?? 0),
                'previous_close' => (float) ($stock?->previous_close ?? 0),
                'change_amount' => (float) ($stock?->change_amount ?? 0),
                'change_percentage' => (float) ($stock?->change_percentage ?? 0),
                'open' => (float) ($stock?->open ?? 0),
                'high' => (float) ($stock?->high ?? 0),
                'low' => (float) ($stock?->low ?? 0),
                'metrics' => $metrics,
                'quotes' => $quotes->all(),
            ]);
        }

        $summary['return_percent'] = $summary['performance_basis'] > 0
            ? ($summary['profit_loss'] / $summary['performance_basis']) * 100
            : 0;
        $resolved = $summary['positive_count'] + $summary['negative_count'];
        $summary['positive_execution_rate'] = $resolved > 0
            ? ($summary['positive_count'] / $resolved) * 100
            : 0;
        $summary['win_rate'] = $summary['positive_execution_rate'];

        $executions = TradingBotExecution::with(['subscription.product','bot.stock'])
            ->whereHas('subscription', fn ($q) => $q->where('user_id', Auth::id()))
            ->latest()->paginate(40);

        $symbols = $botCards->pluck('symbol')->filter()->unique()->values();

        $marketNews = StockNews::whereIn('symbol', $symbols)
            ->orderByDesc('published_at')
            ->limit(8)
            ->get();

        $marketContext = $botCards
            ->filter(fn ($bot) => ! empty($bot['symbol']))
            ->unique('symbol')
            ->values();

        $marketStatus = app(MarketSessionService::class)->status();

        return view('ai-bots.performance', compact(
            'executions','summary','botCards','marketNews','marketContext','marketStatus'
        ));
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

        $symbol = $execution->bot?->stock?->symbol;
        $quoteHistory = $this->marketSeries($symbol, 48);

        return view('ai-bots.execution-show', compact(
            'execution','currentPrice','profitLoss','returnPercent','quoteHistory'
        ));
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
            'stop_loss_percent'=>'nullable|numeric|min:0.01|max:100',
            'take_profit_percent'=>'nullable|numeric|min:0.01|max:100',
            'position_duration_minutes'=>'nullable|integer|min:1|max:43200',
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


    private function marketSeries(?string $symbol, int $limit = 48): array
    {
        if (! $symbol) {
            return [];
        }

        $candles = StockCandle::query()
            ->where('symbol', $symbol)
            ->where('interval', '15m')
            ->orderByDesc('started_at')
            ->limit($limit)
            ->get(['open','high','low','close','started_at'])
            ->sortBy('started_at')
            ->values();

        if ($candles->isNotEmpty()) {
            return $candles->map(fn ($candle) => [
                'open' => (float) $candle->open,
                'high' => (float) $candle->high,
                'low' => (float) $candle->low,
                'close' => (float) $candle->close,
                'price' => (float) $candle->close,
                'time' => optional($candle->started_at)->toIso8601String(),
                'label' => optional($candle->started_at)
                    ?->copy()
                    ->setTimezone(MarketSessionService::TIMEZONE)
                    ->format('H:i'),
            ])->all();
        }

        // Migration-safe fallback while the first real 15-minute candles accumulate.
        // Only use recent snapshots so old seeded/stale prices cannot destroy the
        // scale of the live chart before fresh candle truth exists.
        $recentQuotes = StockQuote::query()
            ->where('symbol', $symbol)
            ->where('fetched_at', '>=', now()->subDay())
            ->orderByDesc('fetched_at')
            ->limit($limit)
            ->get(['current_price','fetched_at'])
            ->sortBy('fetched_at')
            ->values();

        if ($recentQuotes->count() < 2) {
            return [];
        }

        return $recentQuotes
            ->map(fn ($quote) => [
                'price' => (float) $quote->current_price,
                'time' => optional($quote->fetched_at)->toIso8601String(),
                'label' => optional($quote->fetched_at)
                    ?->copy()
                    ->setTimezone(MarketSessionService::TIMEZONE)
                    ->format('H:i'),
            ])->all();
    }

    private function own(BotSubscription $subscription):void
    {
        abort_unless($subscription->user_id===Auth::id(),403);
    }
}
