<?php

namespace App\Services;

use App\Models\InvestmentHolding;
use App\Models\InvestmentPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvestmentPriceMonitorService
{
    /**
     * Monitor all investment holdings for NAV changes
     */
    public static function monitorHoldings()
    {
        $notificationsCreated = 0;
        $holdingsUpdated = 0;

        // Get all investment holdings with active plans
        $holdings = InvestmentHolding::with(['user', 'investmentPlan'])
            ->whereHas('investmentPlan', function($query) {
                $query->where('is_active', true);
            })
            ->get();

        foreach ($holdings as $holding) {
            $totalInvested = $holding->total_invested;
            $currentNav = $holding->investmentPlan->nav;
            
            if ($totalInvested > 0 && $currentNav > 0) {
                $currentValue = $holding->units * $currentNav;
                $unrealizedGainLoss = $currentValue - $totalInvested;
                $unrealizedGainLossPercentage = ($unrealizedGainLoss / $totalInvested) * 100;
                
                // Compute delta since last notification (if any)
                $previousNotified = $holding->last_notified_change_percentage ?? null;
                $deltaChange = $previousNotified === null
                    ? $unrealizedGainLossPercentage
                    : ($unrealizedGainLossPercentage - (float) $previousNotified);

                // Always update live values
                $holding->update([
                    'current_value' => $currentValue,
                    'unrealized_gain_loss' => $unrealizedGainLoss,
                    'unrealized_gain_loss_percentage' => $unrealizedGainLossPercentage,
                ]);
                $holdingsUpdated++;

                // Always update baseline for next delta computation
                $holding->update([
                    'last_notified_change_percentage' => $unrealizedGainLossPercentage,
                ]);

                // Threshold: notify only when absolute delta >= 1%
                if (abs($deltaChange) >= 1.0) {
                    NotificationService::createInvestmentUpdateNotification(
                        $holding->user,
                        $holding->investmentPlan->name,
                        $deltaChange
                    );
                    $notificationsCreated++;

                    // info logs suppressed; only log errors
                }
            }
        }

        return [
            'notifications_created' => $notificationsCreated,
            'holdings_updated' => $holdingsUpdated,
        ];
    }

    /**
     * Check for NAV changes and update holdings
     */
    public static function checkNavChanges()
    {
        $plansUpdated = 0;
        $holdingsUpdated = 0;

        // Get all active investment plans
        $plans = InvestmentPlan::where('is_active', true)->get();

        foreach ($plans as $plan) {
            // Check if NAV has changed since last update
            if ($plan->last_nav_update && $plan->nav_change_percentage !== null) {
                $holdings = $plan->holdings;
                
                foreach ($holdings as $holding) {
                    $currentValue = $holding->units * $plan->nav;
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
        }

        return [
            'plans_updated' => $plansUpdated,
            'holdings_updated' => $holdingsUpdated,
        ];
    }

    /**
     * Send weekly portfolio performance summaries
     */
    public static function sendWeeklySummaries()
    {
        $summariesSent = 0;

        // Get all users with investment holdings
        $users = User::whereHas('investmentHoldings')->get();

        foreach ($users as $user) {
            $holdings = $user->investmentHoldings()->with('investmentPlan')->get();
            
            if ($holdings->count() > 0) {
                $totalInvested = $holdings->sum('total_invested');
                $totalCurrentValue = $holdings->sum('current_value');
                $totalGainLoss = $holdings->sum('unrealized_gain_loss');
                $totalGainLossPercentage = $totalInvested > 0 ? (($totalGainLoss / $totalInvested) * 100) : 0;

                // Only send if there's significant activity or change
                if (abs($totalGainLossPercentage) >= 0.5 || $totalGainLoss !== 0) {
                    NotificationService::createWeeklyPortfolioSummary(
                        $user,
                        $totalInvested,
                        $totalCurrentValue,
                        $totalGainLoss,
                        $totalGainLossPercentage
                    );
                    $summariesSent++;
                }
            }
        }

        return [
            'summaries_sent' => $summariesSent,
        ];
    }

    /**
     * Run complete investment monitoring process
     */
    public static function runMonitoring()
    {
        // info logs suppressed; only log errors
        
        // Monitor holdings for notifications
        $holdingsResult = self::monitorHoldings();
        
        // Check for NAV changes
        $navResult = self::checkNavChanges();
        
        $summary = [
            'holdings_updated' => $holdingsResult['holdings_updated'],
            'notifications_created' => $holdingsResult['notifications_created'],
            'plans_updated' => $navResult['plans_updated'],
            'nav_holdings_updated' => $navResult['holdings_updated'],
        ];
        
        // info logs suppressed; only log errors
        
        return $summary;
    }

    /**
     * Run weekly portfolio summaries
     */
    public static function runWeeklySummaries()
    {
        // info logs suppressed; only log errors
        
        try {
            $result = self::sendWeeklySummaries();
            
            // info logs suppressed
            
        } catch (\Exception $e) {
            Log::error("Investment weekly summaries error: " . $e->getMessage());
        }
        
        return $result;
    }
}
