<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotProduct;
use App\Models\BotSubscription;
use App\Models\MarketInstrument;
use App\Models\TradingBotExecution;
use App\Services\BotMarketContextService;
use App\Services\TradingPerformanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TradingBotController extends Controller
{
    public function index(TradingPerformanceService $performance)
    {
        $products = BotProduct::with(['stock','marketInstrument'])
            ->withCount([
                'subscriptions',
                'subscriptions as active_subscriptions_count' => fn ($q) => $q->whereIn('status', ['active','paused']),
            ])
            ->latest()
            ->paginate(30);

        foreach ($products as $product) {
            $product->performance_metrics = $performance->botProduct($product);
        }

        return view('admin.ai-bots.index',compact('products'));
    }

    public function create()
    {
        $instruments = $this->activeInstruments();
        return view('admin.ai-bots.create',compact('instruments'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if ($data['use_manual_performance']) {
            $data['manual_performance_updated_by'] = auth()->id();
            $data['manual_performance_updated_at'] = now();
        }

        BotProduct::create($data+['created_by'=>auth()->id(),'slug'=>Str::slug($request->name).'-'.Str::lower(Str::random(5))]);
        return redirect()->route('admin.ai-bots.index')->with('success','Bot product published.');
    }

    public function edit(BotProduct $botProduct)
    {
        $botProduct->loadMissing(['marketInstrument','stock']);
        $instruments = $this->activeInstruments();
        return view('admin.ai-bots.edit',compact('botProduct','instruments'));
    }

    public function update(Request $request, BotProduct $botProduct)
    {
        $data = $this->validated($request);
        if ($data['use_manual_performance']) {
            $data['manual_performance_updated_by'] = auth()->id();
            $data['manual_performance_updated_at'] = now();
        } else {
            $data['manual_performance_updated_by'] = null;
            $data['manual_performance_updated_at'] = null;
        }

        $botProduct->update($data);
        return redirect()->route('admin.ai-bots.index')->with('success','Bot product updated.');
    }

    public function destroy(BotProduct $botProduct)
    {
        if($botProduct->subscriptions()->whereIn('status',['active','paused'])->exists()){
            return back()->with('error','Deactivate this bot instead; active customer subscriptions exist.');
        }
        $botProduct->delete();
        return back()->with('success','Bot product removed.');
    }

    public function toggle(BotProduct $botProduct)
    {
        $botProduct->update(['is_active'=>!$botProduct->is_active]);
        return back()->with('success','Bot availability updated.');
    }

    public function subscriptions()
    {
        $subscriptions=BotSubscription::with([
            'user',
            'product.stock',
            'product.marketInstrument',
            'bot.marketInstrument',
        ])->latest()->paginate(40);

        return view('admin.ai-bots.subscriptions',compact('subscriptions'));
    }

    public function executions()
    {
        $executions = TradingBotExecution::with([
            'subscription.user',
            'subscription.product.marketInstrument',
            'bot.stock',
            'bot.marketInstrument',
            'marketInstrument',
            'stockTransaction',
            'brokerOrder',
            'marketExecution',
        ])->latest()->paginate(50);

        return view('admin.ai-bots.executions', compact('executions'));
    }

    public function executionShow(
        TradingBotExecution $execution,
        TradingPerformanceService $performance,
        BotMarketContextService $markets
    ) {
        $execution->load([
            'subscription.user',
            'subscription.product.marketInstrument',
            'bot.stock',
            'bot.marketInstrument',
            'marketInstrument',
            'stockTransaction',
            'brokerOrder',
            'marketExecution',
        ]);

        $instrument = $execution->marketInstrument ?? $execution->bot?->marketInstrument ?? $execution->bot?->stock?->marketInstrument;
        $market = $instrument ? $markets->forInstrument($instrument, 0) : null;
        $mark = $performance->executionMark($execution);

        return view('admin.ai-bots.execution-show', [
            'execution' => $execution,
            'market' => $market,
            'currentPrice' => $mark['current_price'],
            'currentPriceDisplay' => $mark['current_price_display'],
            'entryPriceDisplay' => $mark['entry_price_display'],
            'profitLoss' => $mark['profit_loss'],
            'returnPercent' => $mark['return_percent'],
        ]);
    }

    private function validated(Request $request):array
    {
        $data = $request->validate([
            'name'=>'required|string|max:120',
            'description'=>'nullable|string|max:2000',
            'market_instrument_id'=>'required|exists:market_instruments,id',
            'strategy'=>'required|in:dca,price_below,price_above',
            'action'=>'required|in:buy,sell',
            'risk_level'=>'required|in:low,medium,high',
            'price'=>'required|numeric|min:0|max:100000',
            'billing_period'=>'required|in:one_time,monthly,quarterly,yearly',
            'minimum_balance'=>'required|numeric|min:0|max:1000000',
            'max_user_allocation'=>'nullable|numeric|min:1|max:1000000',
            'default_interval_minutes'=>'required|integer|min:5|max:10080',
            'default_max_daily_trades'=>'required|integer|min:1|max:24',
            'default_trade_amount'=>'nullable|numeric|min:1|max:100000',
            'default_trigger_price'=>'nullable|numeric|min:0.00000001',
            'allow_user_trade_amount'=>'nullable|boolean',
            'allow_user_trigger_price'=>'nullable|boolean',
            'is_active'=>'nullable|boolean',
            'use_manual_performance'=>'nullable|boolean',
            'manual_profit_loss'=>'nullable|numeric|min:-100000000|max:100000000',
            'manual_return_percent'=>'nullable|numeric|min:-10000|max:10000',
            'manual_performance_label'=>'nullable|string|max:60|required_if:use_manual_performance,1',
            'manual_performance_note'=>'nullable|string|max:255',
            'manual_performance_source'=>'nullable|in:profit_loss,return_percent',
        ]);

        $data += [
            'allow_user_trade_amount'=>$request->boolean('allow_user_trade_amount'),
            'allow_user_trigger_price'=>$request->boolean('allow_user_trigger_price'),
            'is_active'=>$request->boolean('is_active'),
            'use_manual_performance'=>$request->boolean('use_manual_performance'),
        ];

        $instrument = MarketInstrument::query()
            ->with('canonicalStock')
            ->whereKey($data['market_instrument_id'])
            ->firstOrFail();

        if (! $instrument->is_active) {
            abort(422, 'Selected market instrument is inactive.');
        }

        $data['stock_id'] = $instrument->isStock()
            ? ($instrument->canonicalStock?->id ?? $instrument->stock_id)
            : null;

        if ($instrument->isStock() && ! $data['stock_id']) {
            abort(422, 'Selected Stock instrument has no canonical Stock child.');
        }

        if ($data['use_manual_performance']) {
            $base = (float) ($data['max_user_allocation'] ?? $data['minimum_balance'] ?? $data['default_trade_amount'] ?? 0);
            $source = $data['manual_performance_source'] ?? null;

            if ($base > 0 && $source === 'profit_loss' && $data['manual_profit_loss'] !== null) {
                $data['manual_return_percent'] = ((float) $data['manual_profit_loss'] / $base) * 100;
            } elseif ($base > 0 && $source === 'return_percent' && $data['manual_return_percent'] !== null) {
                $data['manual_profit_loss'] = $base * ((float) $data['manual_return_percent'] / 100);
            } elseif ($base > 0 && $data['manual_profit_loss'] !== null) {
                $data['manual_return_percent'] = ((float) $data['manual_profit_loss'] / $base) * 100;
            }
        }

        unset($data['manual_performance_source']);
        return $data;
    }

    private function activeInstruments()
    {
        return MarketInstrument::query()
            ->active()
            ->with(['canonicalStock','canonicalForexPair','canonicalCryptoPair'])
            ->whereIn('asset_class', [
                MarketInstrument::ASSET_STOCK,
                MarketInstrument::ASSET_FOREX,
                MarketInstrument::ASSET_CRYPTO,
            ])
            ->orderBy('asset_class')
            ->orderBy('display_symbol')
            ->get();
    }
}
