<?php

namespace App\Http\Controllers;

use App\Models\CopyRelationship;
use App\Models\CopyStrategy;
use App\Models\CopyTraderProfile;
use App\Models\CopyTradeExecution;
use App\Models\StrategyProviderApplication;
use App\Services\CopyTradingSurfaceService;
use App\Services\FeatureAccessService;
use App\Services\TradingPerformanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CopyTradingController extends Controller
{
    public function index()
    {
        return redirect()->route('copy-trading.marketplace');
    }

    public function marketplace(TradingPerformanceService $performance, CopyTradingSurfaceService $surfaces)
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
            $surfaces->decorateStrategy($strategy);
        }

        return view('copy-trading.marketplace', compact('strategies'));
    }

    public function myCopies(
        TradingPerformanceService $performance,
        \App\Services\CopyRelationshipLifecycleService $lifecycle,
        CopyTradingSurfaceService $surfaces
    ) {
        $lifecycle->expireDue();

        $relationships = CopyRelationship::with(['strategy.profile.user', 'provider'])
            ->where('follower_id', Auth::id())
            ->latest()
            ->get();

        foreach ($relationships as $relationship) {
            $relationship->performance_metrics = $performance->copyRelationship($relationship);
            $surfaces->decorateRelationship($relationship);
        }

        return view('copy-trading.my-copies', compact('relationships'));
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

        $executions = CopyTradeExecution::with([
                'relationship.follower',
                'marketInstrument',
                'providerMarketExecution.marketInstrument',
                'providerTrade.stock.marketInstrument',
            ])
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

    public function follow(Request $request, CopyStrategy $strategy, FeatureAccessService $access)
    {
        $user = Auth::user();
        $access->require($user, FeatureAccessService::COPY_TRADER);
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

    public function executionShow(CopyTradeExecution $execution, CopyTradingSurfaceService $surfaces)
    {
        $execution->load(['relationship.strategy.profile.user', 'relationship.provider']);
        abort_unless($execution->relationship?->follower_id === Auth::id(), 403);

        return view('copy-trading.execution-show', [
            'execution' => $execution,
            ...$surfaces->detail($execution),
        ]);
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
            'relationship.provider.copyTraderProfile',
            'marketInstrument',
            'providerMarketExecution.marketInstrument',
            'followerMarketExecution.marketInstrument',
            'providerTrade.stock.marketInstrument',
            'followerTrade.stock.marketInstrument',
        ])
            ->whereHas('relationship', fn ($q) => $q->where('follower_id', Auth::id()))
            ->latest()
            ->paginate(30);

        return view('copy-trading.executions', compact('items'));
    }
}
