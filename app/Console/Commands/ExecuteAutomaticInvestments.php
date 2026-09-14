<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\AutomaticInvestmentPlan;
use App\Models\WalletTransaction;
use App\Models\InvestmentTransaction;
use App\Models\InvestmentHolding;
use App\Services\NotificationService;
use App\Models\User;

class ExecuteAutomaticInvestments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'investments:execute-automatic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute automatic investment plans that are due';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // info logs suppressed; only log errors

        // Get all due automatic investment plans
        $duePlans = AutomaticInvestmentPlan::with(['user', 'investmentPlan'])
            ->where('is_active', true)
            ->where('next_investment_date', '<=', now())
            ->get();

        // info logs suppressed; only log errors

        $successCount = 0;
        $errorCount = 0;

        foreach ($duePlans as $plan) {
            try {
                $this->processInvestmentPlan($plan);
                $successCount++;
                // info logs suppressed; only log errors
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("✗ Failed to process investment plan {$plan->id}: {$e->getMessage()}");
                Log::error("Automatic investment failed for plan {$plan->id}: " . $e->getMessage());
            }
        }

        // info logs suppressed; only log errors
    }

    /**
     * Process a single automatic investment plan
     */
    private function processInvestmentPlan(AutomaticInvestmentPlan $plan)
    {
        DB::beginTransaction();

        try {
            $user = $plan->user;
            $wallet = $user->wallet;
            $investmentPlan = $plan->investmentPlan;

            // Check if user has sufficient funds
            if (!$wallet || !$wallet->canWithdraw($plan->amount)) {
                $balance = $wallet ? $wallet->balance : 0;
                throw new \Exception("Insufficient funds in wallet. Required: \${$plan->amount}, Available: \${$balance}");
            }

            // Create wallet transaction
            $walletTransaction = $wallet->transactions()->create([
                'payment_method_id' => 1, // Default payment method
                'type' => 'investment',
                'amount' => $plan->amount,
                'fee' => 0.00,
                'status' => 'completed',
                'description' => "Automatic investment in {$investmentPlan->name}",
            ]);

            // Create investment transaction
            $investmentTransaction = InvestmentTransaction::create([
                'user_id' => $user->id,
                'investment_plan_id' => $investmentPlan->id,
                'wallet_transaction_id' => $walletTransaction->id,
                'type' => 'automatic_investment',
                'units' => $plan->amount / $investmentPlan->nav,
                'nav_at_transaction' => $investmentPlan->nav,
                'total_amount' => $plan->amount,
                'fee' => 0.00,
                'status' => 'completed',
                'executed_at' => now(),
            ]);

            // Update or create holding
            $holding = $user->investmentHoldings()
                ->where('investment_plan_id', $investmentPlan->id)
                ->first();

            if ($holding) {
                // Update existing holding
                $totalUnits = $holding->units + $investmentTransaction->units;
                $totalInvested = $holding->total_invested + $plan->amount;
                $averageCost = $totalInvested / $totalUnits;

                $holding->update([
                    'units' => $totalUnits,
                    'average_cost' => $averageCost,
                    'total_invested' => $totalInvested,
                    'current_value' => $totalUnits * $investmentPlan->nav,
                    'unrealized_gain_loss' => ($totalUnits * $investmentPlan->nav) - $totalInvested,
                    'unrealized_gain_loss_percentage' => $totalInvested > 0 ? ((($totalUnits * $investmentPlan->nav) - $totalInvested) / $totalInvested) * 100 : 0,
                ]);
            } else {
                // Create new holding
                $user->investmentHoldings()->create([
                    'investment_plan_id' => $investmentPlan->id,
                    'units' => $investmentTransaction->units,
                    'average_cost' => $investmentPlan->nav,
                    'total_invested' => $plan->amount,
                    'current_value' => $investmentTransaction->units * $investmentPlan->nav,
                    'unrealized_gain_loss' => 0,
                    'unrealized_gain_loss_percentage' => 0,
                ]);
            }

            // Deduct funds from wallet
            $wallet->deductFunds($plan->amount);

            // Update next investment date
            $plan->update(['next_investment_date' => $plan->calculateNextInvestmentDate()]);

            // Create notification
            NotificationService::createAutomaticInvestmentNotification(
                $user,
                $plan->investmentPlan->name,
                $plan->amount
            );

            // info logs suppressed; only log errors

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
