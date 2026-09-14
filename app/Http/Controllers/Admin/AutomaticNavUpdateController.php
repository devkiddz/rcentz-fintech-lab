<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutomaticNavUpdate;
use App\Models\InvestmentPlan;
use App\Services\TimezoneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutomaticNavUpdateController extends Controller
{
    public function index()
    {
        $automaticUpdates = AutomaticNavUpdate::with(['investmentPlan', 'creator'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total_updates' => AutomaticNavUpdate::count(),
            'active_updates' => AutomaticNavUpdate::active()->count(),
            'due_updates' => AutomaticNavUpdate::due()->count(),
            'total_executions' => AutomaticNavUpdate::sum('total_executions'),
        ];

        return view('admin.investments.automatic-nav-updates.index', compact('automaticUpdates', 'stats'));
    }

    public function create()
    {
        $investmentPlans = InvestmentPlan::active()->orderBy('name')->get();
        
        return view('admin.investments.automatic-nav-updates.create', compact('investmentPlans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'investment_plan_id' => 'required|exists:investment_plans,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'update_type' => 'required|in:increase,decrease',
            'update_amount' => 'required|numeric|min:0.0001|max:999.9999',
            'update_interval_value' => 'required|integer|min:1|max:999',
            'update_interval_unit' => 'required|in:minutes,hours,days',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ]);

        try {
            DB::beginTransaction();

            // Convert user input dates to UTC for database storage
            $startDate = TimezoneService::convertToUtc($request->start_date);
            $endDate = TimezoneService::convertToUtc($request->end_date);

            // Calculate next execution time (in UTC)
            $nextExecution = $startDate->copy();

            $automaticUpdate = AutomaticNavUpdate::create([
                'investment_plan_id' => $request->investment_plan_id,
                'name' => $request->name,
                'description' => $request->description,
                'update_type' => $request->update_type,
                'update_amount' => $request->update_amount,
                'update_interval_value' => $request->update_interval_value,
                'update_interval_unit' => $request->update_interval_unit,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'next_execution_at' => $nextExecution,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            Log::info("Automatic NAV update created", [
                'id' => $automaticUpdate->id,
                'plan' => $automaticUpdate->investmentPlan->name,
                'type' => $request->update_type,
                'amount' => $request->update_amount,
                'interval' => $request->update_interval_value . ' ' . $request->update_interval_unit,
                'created_by' => Auth::user()->name,
            ]);

            return redirect()->route('admin.investments.automatic-nav-updates.index')
                ->with('success', 'Automatic NAV update created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create automatic NAV update", [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create automatic NAV update. Please try again.');
        }
    }

    public function show(AutomaticNavUpdate $automaticNavUpdate)
    {
        $automaticNavUpdate->load(['investmentPlan', 'creator']);
        
        return view('admin.investments.automatic-nav-updates.show', compact('automaticNavUpdate'));
    }

    public function edit(AutomaticNavUpdate $automaticNavUpdate)
    {
        $investmentPlans = InvestmentPlan::active()->orderBy('name')->get();
        
        return view('admin.investments.automatic-nav-updates.edit', compact('automaticNavUpdate', 'investmentPlans'));
    }

    public function update(Request $request, AutomaticNavUpdate $automaticNavUpdate)
    {
        $request->validate([
            'investment_plan_id' => 'required|exists:investment_plans,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'update_type' => 'required|in:increase,decrease',
            'update_amount' => 'required|numeric|min:0.0001|max:999.9999',
            'update_interval_value' => 'required|integer|min:1|max:999',
            'update_interval_unit' => 'required|in:minutes,hours,days',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);

            // Recalculate next execution if interval changed
            $nextExecution = $automaticNavUpdate->next_execution_at;
            if ($automaticNavUpdate->update_interval_value != $request->update_interval_value ||
                $automaticNavUpdate->update_interval_unit != $request->update_interval_unit) {
                $nextExecution = $automaticNavUpdate->calculateNextExecution();
            }

            $automaticNavUpdate->update([
                'investment_plan_id' => $request->investment_plan_id,
                'name' => $request->name,
                'description' => $request->description,
                'update_type' => $request->update_type,
                'update_amount' => $request->update_amount,
                'update_interval_value' => $request->update_interval_value,
                'update_interval_unit' => $request->update_interval_unit,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'next_execution_at' => $nextExecution,
                'is_active' => $request->has('is_active'),
            ]);

            DB::commit();

            Log::info("Automatic NAV update updated", [
                'id' => $automaticNavUpdate->id,
                'plan' => $automaticNavUpdate->investmentPlan->name,
                'updated_by' => Auth::user()->name,
            ]);

            return redirect()->route('admin.investments.automatic-nav-updates.index')
                ->with('success', 'Automatic NAV update updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update automatic NAV update", [
                'id' => $automaticNavUpdate->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update automatic NAV update. Please try again.');
        }
    }

    public function destroy(AutomaticNavUpdate $automaticNavUpdate)
    {
        try {
            $planName = $automaticNavUpdate->investmentPlan->name;
            $automaticNavUpdate->delete();

            Log::info("Automatic NAV update deleted", [
                'id' => $automaticNavUpdate->id,
                'plan' => $planName,
                'deleted_by' => Auth::user()->name,
            ]);

            return redirect()->route('admin.investments.automatic-nav-updates.index')
                ->with('success', 'Automatic NAV update deleted successfully.');

        } catch (\Exception $e) {
            Log::error("Failed to delete automatic NAV update", [
                'id' => $automaticNavUpdate->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete automatic NAV update. Please try again.');
        }
    }

    public function toggle(AutomaticNavUpdate $automaticNavUpdate)
    {
        try {
            $automaticNavUpdate->update([
                'is_active' => !$automaticNavUpdate->is_active,
            ]);

            $status = $automaticNavUpdate->is_active ? 'activated' : 'deactivated';

            Log::info("Automatic NAV update {$status}", [
                'id' => $automaticNavUpdate->id,
                'plan' => $automaticNavUpdate->investmentPlan->name,
                'toggled_by' => Auth::user()->name,
            ]);

            return redirect()->back()
                ->with('success', "Automatic NAV update {$status} successfully.");

        } catch (\Exception $e) {
            Log::error("Failed to toggle automatic NAV update", [
                'id' => $automaticNavUpdate->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to toggle automatic NAV update. Please try again.');
        }
    }

    public function preview(Request $request)
    {
        try {
            $request->validate([
                'investment_plan_id' => 'required|exists:investment_plans,id',
                'update_type' => 'required|in:increase,decrease',
                'update_amount' => 'required|numeric|min:0.0001',
                'update_interval_value' => 'required|integer|min:1',
                'update_interval_unit' => 'required|in:minutes,hours,days',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
            ]);

            $plan = InvestmentPlan::findOrFail($request->investment_plan_id);
            
            // Parse dates with proper timezone handling using TimezoneService
            $startDate = TimezoneService::convertToUtc($request->start_date);
            $endDate = TimezoneService::convertToUtc($request->end_date);
        
            $updateAmount = $request->update_type === 'increase' 
                ? (float) $request->update_amount 
                : -(float) $request->update_amount;

            // Calculate execution schedule
            $executions = [];
            $currentDate = $startDate->copy();
            $currentNav = (float) $plan->nav;
            $executionCount = 0;
            $intervalValue = (int) $request->update_interval_value;

            while ($currentDate->lte($endDate) && $executionCount < 100) { // Limit to 100 for performance
                $executions[] = [
                    'date' => TimezoneService::formatForDisplay($currentDate),
                    'nav_before' => $currentNav,
                    'nav_after' => $currentNav + $updateAmount,
                    'change' => $updateAmount,
                ];

                $currentNav += $updateAmount;
                $executionCount++;

                // Calculate next execution
                switch ($request->update_interval_unit) {
                    case 'minutes':
                        $currentDate->addMinutes($intervalValue);
                        break;
                    case 'hours':
                        $currentDate->addHours($intervalValue);
                        break;
                    case 'days':
                        $currentDate->addDays($intervalValue);
                        break;
                }
            }

            return response()->json([
                'plan' => $plan->name,
                'total_executions' => count($executions),
                'final_nav' => $currentNav,
                'total_change' => $currentNav - $plan->nav,
                'executions' => array_slice($executions, 0, 10), // Show first 10
            ]);
        } catch (\Exception $e) {
            Log::error('Preview error: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to generate preview: ' . $e->getMessage()
            ], 500);
        }
    }
}
