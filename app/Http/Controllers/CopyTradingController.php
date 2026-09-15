<?php

namespace App\Http\Controllers;

use App\Models\CopyRelationship;
use App\Models\CopyStrategy;
use App\Models\CopyTraderProfile;
use App\Models\CopyTradeExecution;
use App\Models\StockCandle;
use App\Models\StockQuote;
use App\Models\StrategyProviderApplication;
use App\Services\MarketSessionService;
use App\Services\TradingPerformanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CopyTradingController extends Controller
{
    /** Backward-compatible V1 entry point. */
    public function index()
    {
        return redirect()->route('copy-trading.marketplace');
    }

    public function marketplace(TradingPerformanceService $performance)
    {
        $user = Auth::user();

        $strategies = CopyStrategy::with(['profile.user'])
            ->withCount([
                'relationships as copier_count' => fn ($q) => $q->where('status', 'active'),
            ])
            ->where('is_public', true)
            ->where('is_active', true)
            ->whereHas('profile', fn ($q) => $q
                ->whereNotNull('approved_at')
                ->where('is_public', true)
                ->where('is_accepting_copiers', true))
            ->whereHas('profile.user', fn ($q) => $q->where('id', '!=', $user->id))
            ->latest()
            ->get();

        foreach ($strategies as $strategy) {
            $strategy->performance_metrics = $performance->copyStrategy($strategy);

            $latestExecution = CopyTradeExecution::with('followerTrade.stock')
                ->whereHas('relationship', fn ($q) => $q->where('copy_strategy_id', $strategy->id))
                ->where('status', 'completed')
                ->latest('executed_at')
                ->first();

            $symbol = $latestExecution?->followerTrade?->stock?->symbol;
            $strategy->market_symbol = $symbol;
            $strategy->market_series = $this->marketSeries($symbol, 36);
        }

        $marketStatus = app(MarketSessionService::class)->status();

        return view('copy-trading.marketplace', compact('strategies', 'marketStatus'));
    }

    public function myCopies(TradingPerformanceService $performance)
    {
        $relationships = CopyRelationship::with(['strategy.profile.user', 'provider'])
            ->where('follower_id', Auth::id())
            ->latest()
            ->get();

        foreach ($relationships as $relationship) {
            $relationship->performance_metrics = $performance->copyRelationship($relationship);

            $latestExecution = $relationship->executions()
                ->with('followerTrade.stock')
                ->where('status', 'completed')
                ->latest('executed_at')
                ->first();

            $symbol = $latestExecution?->followerTrade?->stock?->symbol;
            $relationship->market_symbol = $symbol;
            $relationship->market_series = $this->marketSeries($symbol, 48);
        }

        $marketStatus = app(MarketSessionService::class)->status();

        return view('copy-trading.my-copies', compact('relationships', 'marketStatus'));
    }

    public function applyForm()
    {
        $latest = Auth::user()->strategyProviderApplications()->latest()->first();
        return view('copy-trading.apply', compact('latest'));
    }

    public function apply(Request $request)
    {
        $user = Auth::user();

        if ($user->copyTraderProfile?->approved_at) {
            return redirect()->route('copy-trading.provider.dashboard')
                ->with('success', 'You are already an approved strategy provider.');
        }

        if ($user->strategyProviderApplications()->where('status', 'pending')->exists()) {
            return back()->with('error', 'You already have a pending provider application.');
        }

        $data = $request->validate([
            'display_name' => 'required|string|max:100',
            'experience' => 'required|string|max:2000',
            'strategy_summary' => 'required|string|max:2000',
            'risk_level' => 'required|in:low,medium,high',
        ]);

        $user->strategyProviderApplications()->create($data + ['status' => 'pending']);

        return back()->with('success', 'Strategy provider application submitted for admin review.');
    }

    public function providerDashboard(TradingPerformanceService $performance)
    {
        $profile = Auth::user()->copyTraderProfile()
            ->with(['strategies.relationships'])
            ->first();

        abort_unless($profile && $profile->approved_at, 403, 'Strategy provider approval is required.');

        foreach ($profile->strategies as $strategy) {
            $strategy->performance_metrics = $performance->copyStrategy($strategy);
        }

        $executions = CopyTradeExecution::with(['relationship.follower', 'providerTrade.stock'])
            ->whereHas('relationship', fn ($q) => $q->where('provider_id', Auth::id()))
            ->latest()
            ->limit(20)
            ->get();

        return view('copy-trading.provider-dashboard', compact('profile', 'executions'));
    }

    public function storeStrategy(Request $request)
    {
        $profile = Auth::user()->copyTraderProfile;
        abort_unless($profile && $profile->approved_at, 403, 'Strategy provider approval is required.');

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:1500',
            'risk_level' => 'required|in:low,medium,high',
            'minimum_allocation' => 'required|numeric|min:50|max:1000000',
            'recommended_allocation' => 'nullable|numeric|min:50|max:1000000',
        ]);

        $profile->strategies()->create($data + ['is_public' => true, 'is_active' => true]);

        return back()->with('success', 'Strategy published to the marketplace.');
    }

    public function toggleStrategy(CopyStrategy $strategy)
    {
        abort_unless($strategy->profile?->user_id === Auth::id(), 403);
        $strategy->update(['is_active' => ! $strategy->is_active]);

        return back()->with('success', 'Strategy availability updated.');
    }

    public function follow(Request $request, CopyStrategy $strategy)
    {
        $user = Auth::user();
        $strategy->load('profile');

        abort_if($strategy->profile->user_id === $user->id, 422);
        abort_unless(
            $strategy->is_public
            && $strategy->is_active
            && $strategy->profile->approved_at
            && $strategy->profile->is_accepting_copiers,
            404
        );

        $data = $request->validate([
            'allocation_limit' => 'required|numeric|min:'.$strategy->minimum_allocation.'|max:1000000',
            'max_trade_amount' => 'required|numeric|min:10|lte:allocation_limit',
            'copy_ratio_percent' => 'required|numeric|min:1|max:200',
        ]);

        // Strategy identity belongs in the relationship key. A provider may publish
        // several independent strategies without one overwriting another.
        CopyRelationship::updateOrCreate(
            [
                'follower_id' => $user->id,
                'copy_strategy_id' => $strategy->id,
            ],
            $data + [
                'provider_id' => $strategy->profile->user_id,
                'status' => 'active',
                'started_at' => now(),
                'stopped_at' => null,
            ]
        );

        return redirect()->route('copy-trading.my-copies')
            ->with('success', 'Copy trading activated for '.$strategy->name.'.');
    }

    public function editRelationship(CopyRelationship $relationship)
    {
        abort_unless($relationship->follower_id === Auth::id(), 403);
        $relationship->load(['strategy.profile.user', 'provider']);

        return view('copy-trading.edit-relationship', compact('relationship'));
    }

    public function updateRelationship(Request $request, CopyRelationship $relationship)
    {
        abort_unless($relationship->follower_id === Auth::id(), 403);
        $relationship->load('strategy');

        $minimum = (float) ($relationship->strategy?->minimum_allocation ?? 50);

        $data = $request->validate([
            'allocation_limit' => 'required|numeric|min:'.$minimum.'|max:1000000',
            'max_trade_amount' => 'required|numeric|min:10|lte:allocation_limit',
            'copy_ratio_percent' => 'required|numeric|min:1|max:200',
            'status' => 'required|in:active,paused,stopped',
        ]);

        $data['stopped_at'] = $data['status'] === 'stopped' ? now() : null;
        $relationship->update($data);

        return redirect()->route('copy-trading.my-copies')
            ->with('success', 'Copy trading settings updated.');
    }

    public function executionShow(CopyTradeExecution $execution)
    {
        $execution->load([
            'relationship.strategy.profile.user',
            'relationship.provider',
            'followerTrade.stock',
            'providerTrade.stock',
        ]);

        abort_unless($execution->relationship?->follower_id === Auth::id(), 403);

        $trade = $execution->followerTrade;
        $currentPrice = (float) ($trade?->stock?->current_price ?? 0);
        $entryPrice = (float) ($trade?->price_per_share ?? 0);
        $quantity = (float) ($trade?->quantity ?? 0);

        $profitLoss = 0.0;
        if ($execution->status === 'completed' && $trade && $entryPrice > 0 && $quantity > 0 && $currentPrice > 0) {
            $profitLoss = $trade->type === 'sell'
                ? ($entryPrice - $currentPrice) * $quantity
                : ($currentPrice - $entryPrice) * $quantity;
        }

        $returnPercent = (float) $execution->executed_amount > 0
            ? ($profitLoss / (float) $execution->executed_amount) * 100
            : 0;

        $symbol = $trade?->stock?->symbol;
        $quoteHistory = $this->marketSeries($symbol, 48);
        $marketStatus = app(MarketSessionService::class)->status();

        return view('copy-trading.execution-show', compact(
            'execution',
            'currentPrice',
            'profitLoss',
            'returnPercent',
            'quoteHistory',
            'marketStatus'
        ));
    }

    public function status(Request $request, CopyRelationship $relationship)
    {
        abort_unless($relationship->follower_id === Auth::id(), 403);

        $data = $request->validate([
            'status' => 'required|in:active,paused,stopped',
        ]);

        $relationship->update([
            'status' => $data['status'],
            'stopped_at' => $data['status'] === 'stopped' ? now() : null,
        ]);

        return back()->with('success', 'Copy relationship updated.');
    }

    public function executions()
    {
        $items = CopyTradeExecution::with([
            'relationship.strategy.profile.user',
            'providerTrade.stock',
            'followerTrade.stock',
        ])
            ->whereHas('relationship', fn ($q) => $q->where('follower_id', Auth::id()))
            ->latest()
            ->paginate(30);

        return view('copy-trading.executions', compact('items'));
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

        return $recentQuotes->map(fn ($quote) => [
            'price' => (float) $quote->current_price,
            'time' => optional($quote->fetched_at)->toIso8601String(),
            'label' => optional($quote->fetched_at)
                ?->copy()
                ->setTimezone(MarketSessionService::TIMEZONE)
                ->format('H:i'),
        ])->all();
    }
}
