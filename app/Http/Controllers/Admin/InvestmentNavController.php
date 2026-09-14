<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvestmentPlan;
use App\Services\InvestmentPriceMonitorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvestmentNavController extends Controller
{
    public function index()
    {
        $plans = InvestmentPlan::with(['holdings' => function($query) {
            $query->with('user');
        }])
        ->withCount('holdings')
        ->orderBy('name')
        ->get();

        $stats = [
            'total_plans' => $plans->count(),
            'active_plans' => $plans->where('is_active', true)->count(),
            'plans_with_holdings' => $plans->where('holdings_count', '>', 0)->count(),
            'total_holdings' => $plans->sum('holdings_count'),
        ];

        return view('admin.investments.nav-updates.index', compact('plans', 'stats'));
    }

    public function update(Request $request, InvestmentPlan $plan)
    {
        $request->validate([
            'new_nav' => 'required|numeric|min:0.0001',
            'update_reason' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $oldNav = $plan->nav;
            $newNav = $request->new_nav;
            
            // Calculate NAV change
            $navChange = $newNav - $oldNav;
            $navChangePercentage = $oldNav > 0 ? (($navChange / $oldNav) * 100) : 0;

            // Update NAV history
            $navHistory = $plan->nav_history ?? [];
            $navHistory[] = [
                'date' => now()->toISOString(),
                'nav' => $oldNav,
                'change' => $navChange,
                'change_percentage' => $navChangePercentage,
                'reason' => $request->update_reason,
            ];

            // Keep only last 30 entries
            if (count($navHistory) > 30) {
                $navHistory = array_slice($navHistory, -30);
            }

            // Update the plan
            $plan->update([
                'previous_nav' => $oldNav,
                'nav' => $newNav,
                'nav_change' => $navChange,
                'nav_change_percentage' => $navChangePercentage,
                'nav_history' => $navHistory,
                'last_nav_update' => now(),
                'last_updated' => now(),
            ]);

            // Update all holdings for this plan
            $holdingsUpdated = 0;
            foreach ($plan->holdings as $holding) {
                $currentValue = $holding->units * $newNav;
                $unrealizedGainLoss = $currentValue - $holding->total_invested;
                $unrealizedGainLossPercentage = $holding->total_invested > 0 
                    ? (($unrealizedGainLoss / $holding->total_invested) * 100) 
                    : 0;

                $holding->update([
                    'current_value' => $currentValue,
                    'unrealized_gain_loss' => $unrealizedGainLoss,
                    'unrealized_gain_loss_percentage' => $unrealizedGainLossPercentage,
                ]);

                $holdingsUpdated++;
            }

            DB::commit();

            // Call the price monitor service after successful NAV update
            try {
                $monitorResult = InvestmentPriceMonitorService::monitorHoldings();
                Log::info("Price monitoring triggered after NAV update for plan {$plan->name}: " . json_encode($monitorResult));
            } catch (\Exception $e) {
                Log::error("Price monitoring failed after NAV update for plan {$plan->name}: " . $e->getMessage());
                // Don't fail the main operation if monitoring fails
            }

            Log::info("NAV updated for plan {$plan->name}: {$oldNav} -> {$newNav} ({$navChangePercentage}%)");

            return redirect()->route('admin.investments.nav-updates.index')
                ->with('success', "NAV updated successfully for {$plan->name}. {$holdingsUpdated} holdings updated.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update NAV for plan {$plan->name}: " . $e->getMessage());

            return redirect()->route('admin.investments.nav-updates.index')
                ->with('error', 'Failed to update NAV. Please try again.');
        }
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'updates' => 'required|array',
            'updates.*.plan_id' => 'required|exists:investment_plans,id',
            'updates.*.new_nav' => 'required|numeric|min:0.0001',
            'updates.*.reason' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $plansUpdated = 0;
            $holdingsUpdated = 0;

            foreach ($request->updates as $update) {
                $plan = InvestmentPlan::find($update['plan_id']);
                $oldNav = $plan->nav;
                $newNav = $update['new_nav'];
                
                // Calculate NAV change
                $navChange = $newNav - $oldNav;
                $navChangePercentage = $oldNav > 0 ? (($navChange / $oldNav) * 100) : 0;

                // Update NAV history
                $navHistory = $plan->nav_history ?? [];
                $navHistory[] = [
                    'date' => now()->toISOString(),
                    'nav' => $oldNav,
                    'change' => $navChange,
                    'change_percentage' => $navChangePercentage,
                    'reason' => $update['reason'] ?? null,
                ];

                // Keep only last 30 entries
                if (count($navHistory) > 30) {
                    $navHistory = array_slice($navHistory, -30);
                }

                // Update the plan
                $plan->update([
                    'previous_nav' => $oldNav,
                    'nav' => $newNav,
                    'nav_change' => $navChange,
                    'nav_change_percentage' => $navChangePercentage,
                    'nav_history' => $navHistory,
                    'last_nav_update' => now(),
                    'last_updated' => now(),
                ]);

                // Update all holdings for this plan
                foreach ($plan->holdings as $holding) {
                    $currentValue = $holding->units * $newNav;
                    $unrealizedGainLoss = $currentValue - $holding->total_invested;
                    $unrealizedGainLossPercentage = $holding->total_invested > 0 
                        ? (($unrealizedGainLoss / $holding->total_invested) * 100) 
                        : 0;

                    $holding->update([
                        'current_value' => $currentValue,
                        'unrealized_gain_loss' => $unrealizedGainLoss,
                        'unrealized_gain_loss_percentage' => $unrealizedGainLossPercentage,
                    ]);

                    $holdingsUpdated++;
                }

                $plansUpdated++;
            }

            DB::commit();

            // Call the price monitor service after successful bulk NAV update
            try {
                $monitorResult = InvestmentPriceMonitorService::monitorHoldings();
                Log::info("Price monitoring triggered after bulk NAV update: " . json_encode($monitorResult));
            } catch (\Exception $e) {
                Log::error("Price monitoring failed after bulk NAV update: " . $e->getMessage());
                // Don't fail the main operation if monitoring fails
            }

            Log::info("Bulk NAV update completed: {$plansUpdated} plans, {$holdingsUpdated} holdings updated");

            return redirect()->route('admin.investments.nav-updates.index')
                ->with('success', "Bulk NAV update completed. {$plansUpdated} plans and {$holdingsUpdated} holdings updated.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to perform bulk NAV update: " . $e->getMessage());

            return redirect()->route('admin.investments.nav-updates.index')
                ->with('error', 'Failed to perform bulk NAV update. Please try again.');
        }
    }

    public function show(InvestmentPlan $plan)
    {
        $plan->load(['holdings.user', 'holdings' => function($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        $navHistory = collect($plan->nav_history ?? [])->reverse();

        return view('admin.investments.nav-updates.show', compact('plan', 'navHistory'));
    }
}