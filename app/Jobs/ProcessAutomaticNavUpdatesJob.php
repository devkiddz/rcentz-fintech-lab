<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\AutomaticNavUpdate;
use App\Models\InvestmentPlan;
use App\Services\InvestmentPriceMonitorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessAutomaticNavUpdatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting automatic NAV updates processing');

        try {
            // Get all due automatic NAV updates
            $dueUpdates = AutomaticNavUpdate::due()
                ->with(['investmentPlan.holdings'])
                ->get();

            if ($dueUpdates->isEmpty()) {
                Log::info('No automatic NAV updates due for processing');
                return;
            }

            $processedCount = 0;
            $errorCount = 0;

            foreach ($dueUpdates as $update) {
                try {
                    $this->processAutomaticUpdate($update);
                    $processedCount++;
                    
                    // Small delay between updates to prevent overwhelming the system
                    usleep(100000); // 0.1 seconds
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error("Failed to process automatic NAV update", [
                        'update_id' => $update->id,
                        'plan_name' => $update->investmentPlan->name,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Automatic NAV updates processing completed', [
                'processed' => $processedCount,
                'errors' => $errorCount,
                'total_due' => $dueUpdates->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Automatic NAV updates job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Process a single automatic NAV update
     */
    private function processAutomaticUpdate(AutomaticNavUpdate $update): void
    {
        DB::beginTransaction();

        try {
            $plan = $update->investmentPlan;
            $oldNav = $plan->nav;
            $navChangeAmount = $update->getNavChangeAmount();
            $newNav = $oldNav + $navChangeAmount;

            // Ensure NAV doesn't go below minimum threshold
            if ($newNav < 0.0001) {
                Log::warning("Automatic NAV update would result in negative NAV, skipping", [
                    'update_id' => $update->id,
                    'plan_name' => $plan->name,
                    'old_nav' => $oldNav,
                    'change_amount' => $navChangeAmount,
                    'would_be_nav' => $newNav,
                ]);
                
                // Still update the execution time to prevent repeated attempts
                $this->updateExecutionTimes($update);
                DB::commit();
                return;
            }

            // Calculate change percentage
            $navChangePercentage = $oldNav > 0 ? (($navChangeAmount / $oldNav) * 100) : 0;

            // Update NAV history
            $navHistory = $plan->nav_history ?? [];
            $navHistory[] = [
                'date' => Carbon::now('UTC')->toISOString(),
                'nav' => $oldNav,
                'change' => $navChangeAmount,
                'change_percentage' => $navChangePercentage,
                'reason' => 'Automatic update: ' . $update->name,
                'automatic_update_id' => $update->id,
            ];

            // Keep only last 30 entries
            if (count($navHistory) > 30) {
                $navHistory = array_slice($navHistory, -30);
            }

            // Update the investment plan
            $plan->update([
                'previous_nav' => $oldNav,
                'nav' => $newNav,
                'nav_change' => $navChangeAmount,
                'nav_change_percentage' => $navChangePercentage,
                'nav_history' => $navHistory,
                'last_nav_update' => Carbon::now('UTC'),
                'last_updated' => Carbon::now('UTC'),
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

            // Update execution tracking
            $this->updateExecutionTimes($update);

            DB::commit();

            // Call the price monitor service after successful NAV update
            try {
                $monitorResult = InvestmentPriceMonitorService::monitorHoldings();
                Log::info("Price monitoring triggered after automatic NAV update", [
                    'update_id' => $update->id,
                    'plan_name' => $plan->name,
                    'monitor_result' => $monitorResult,
                ]);
            } catch (\Exception $e) {
                Log::error("Price monitoring failed after automatic NAV update", [
                    'update_id' => $update->id,
                    'plan_name' => $plan->name,
                    'error' => $e->getMessage(),
                ]);
                // Don't fail the main operation if monitoring fails
            }

            Log::info("Automatic NAV update processed successfully", [
                'update_id' => $update->id,
                'plan_name' => $plan->name,
                'old_nav' => $oldNav,
                'new_nav' => $newNav,
                'change_amount' => $navChangeAmount,
                'change_percentage' => $navChangePercentage,
                'holdings_updated' => $holdingsUpdated,
                'total_executions' => $update->total_executions,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update execution times and counters for the automatic update
     */
    private function updateExecutionTimes(AutomaticNavUpdate $update): void
    {
        $now = Carbon::now('UTC');
        $nextExecution = $update->calculateNextExecution();

        // If next execution would be after end date, deactivate the update
        $isActive = $nextExecution->lte($update->end_date);

        $update->update([
            'last_executed_at' => $now,
            'next_execution_at' => $nextExecution,
            'total_executions' => $update->total_executions + 1,
            'is_active' => $isActive,
        ]);

        if (!$isActive) {
            Log::info("Automatic NAV update completed and deactivated", [
                'update_id' => $update->id,
                'plan_name' => $update->investmentPlan->name,
                'total_executions' => $update->total_executions + 1,
                'end_date' => $update->end_date->format('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessAutomaticNavUpdatesJob failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
