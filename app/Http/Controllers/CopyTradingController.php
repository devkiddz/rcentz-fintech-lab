<?php

namespace App\Http\Controllers;

use App\Models\CopyRelationship;
use App\Models\CopyStrategy;
use App\Models\CopyTraderProfile;
use App\Models\CopyTradeExecution;
use App\Models\StrategyProviderApplication;
use App\Services\MarketSessionService;
use App\Services\TradingPerformanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CopyTradingController extends Controller
{
    public function index()
    {
        return redirect()->route('copy-trading.marketplace');
    }

    public function marketplace(TradingPerformanceService $performance)
    {
        $user = Auth::user();

        $strategies = CopyStrategy::with(['profile.user'])
            ->withCount([
                'relationships as copier_count' => fn ($q) => $q->where('status', 'active')
                    ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now())),
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
                ->whereNotNull('follower_stock_transaction_id')
                ->latest('executed_at')
                ->first();

            $strategy->market_symbol = $latestExecution?->followerTrade?->stock?->symbol;
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
            if ($relationship->status === 'active' && $relationship->ends_at && $relationship->ends_at->isPast()) {
                $relationship->update([
                    'status' => 'completed',
                    'completed_at' => $relationship->completed_at ?? now(),
                ]);
                $relationship->refresh();
            }

            $relationship->performance_metrics = $performance->copyRelationship($relationship);

            $latestExecution = $relationship->executions()
                ->with('followerTrade.stock')
                ->where('status', 'completed')
                ->whereNotNull('follower_stock_transaction_id')
                ->latest('executed_at')
                ->first();

            $relationship->market_symbol = $latestExecution?->followerTrade?->stock?->symbol;
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
        $profile = Auth::user()->copyTraderProfile()->with(['strategies.relationships'])->first();
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
            $strategy->is_public &&
            $strategy->is_active &&
            $strategy->profile->approved_at &&
            $strategy->profile->is_accepting_copiers,
            404
        );

        $data = $request->validate([
            'allocation_limit' => 'required|numeric|min:'.$strategy->minimum_allocation.'|max:1000000',
            'max_trade_amount' => 'required|numeric|min:10|lte:allocation_limit',
            'copy_ratio_percent' => 'required|numeric|min:1|max:200',
            'duration_minutes' => 'required|integer|in:60,240,1440,10080,43200',
        ]);

        $existingActive = CopyRelationship::query()
            ->where('follower_id', $user->id)
            ->where('copy_strategy_id', $strategy->id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->exists();

        if ($existingActive) {
            return redirect()->route('copy-trading.my-copies')
                ->with('error', 'You already have a running contract for this strategy. Running copy contracts are locked until they end.');
        }

        $startedAt = now();
        $durationMinutes = (int) $data['duration_minutes'];

        CopyRelationship::create([
            'follower_id' => $user->id,
            'copy_strategy_id' => $strategy->id,
            'provider_id' => $strategy->profile->user_id,
            'allocation_limit' => $data['allocation_limit'],
            'used_amount' => 0,
            'max_trade_amount' => $data['max_trade_amount'],
            'copy_ratio_percent' => $data['copy_ratio_percent'],
            'duration_minutes' => $durationMinutes,
            'status' => 'active',
            'started_at' => $startedAt,
            'ends_at' => $startedAt->copy()->addMinutes($durationMinutes),
            'stopped_at' => null,
            'completed_at' => null,
        ]);

        return redirect()->route('copy-trading.my-copies')
            ->with('success', 'Copy trading activated for '.$strategy->name.'.');
    }

    public function editRelationship(CopyRelationship $relationship)
    {
        abort_unless($relationship->follower_id === Auth::id(), 403);

        return redirect()->route('copy-trading.my-copies')
            ->with('error', 'Copy contracts are immutable after activation. You can monitor the contract, but its allocation, duration and copy rules cannot be edited.');
    }

    public function updateRelationship(Request $request, CopyRelationship $relationship)
    {
        abort_unless($relationship->follower_id === Auth::id(), 403);

        return redirect()->route('copy-trading.my-copies')
            ->with('error', 'This copy contract is locked. Contract terms cannot be changed after activation.');
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
        $marketStatus = app(MarketSessionService::class)->status();

        return view('copy-trading.execution-show', compact(
            'execution',
            'currentPrice',
            'profitLoss',
            'returnPercent',
            'symbol',
            'marketStatus'
        ));
    }

    public function status(Request $request, CopyRelationship $relationship)
    {
        abort_unless($relationship->follower_id === Auth::id(), 403);

        return redirect()->route('copy-trading.my-copies')
            ->with('error', 'Running copy contracts cannot be manually paused, resized or rewritten. Lifecycle changes are controlled by the contract.');
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
}
