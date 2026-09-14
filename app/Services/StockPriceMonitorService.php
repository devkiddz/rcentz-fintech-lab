<?php

namespace App\Services;

use App\Models\StockHolding;
use App\Models\StockWatchlist;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockPriceMonitorService
{
    /**
     * Monitor all stock holdings for price changes
     */
    public static function monitorHoldings()
    {
        $notificationsCreated = 0;
        $holdingsUpdated = 0;

        // Get all stock holdings with current prices
        $holdings = StockHolding::with(['user', 'stock'])
            ->whereHas('stock', function($query) {
                $query->where('is_active', true);
            })
            ->get();

        foreach ($holdings as $holding) {
            $averageBuyPrice = $holding->average_buy_price;
            $currentPrice = $holding->stock->current_price;
            
            if ($averageBuyPrice > 0 && $currentPrice > 0) {
                $percentageChange = (($currentPrice - $averageBuyPrice) / $averageBuyPrice) * 100;
                
                // Update holding values
                $currentValue = $holding->quantity * $currentPrice;
                $unrealizedGainLoss = $currentValue - ($holding->quantity * $averageBuyPrice);
                
                // Compute delta since last notification (if any)
                $previousNotified = $holding->last_notified_change_percentage ?? null;
                $deltaChange = $previousNotified === null
                    ? $percentageChange
                    : ($percentageChange - (float) $previousNotified);

                // Always update live values
                $holding->update([
                    'current_value' => $currentValue,
                    'unrealized_gain_loss' => $unrealizedGainLoss,
                    'unrealized_gain_loss_percentage' => $percentageChange,
                ]);
                $holdingsUpdated++;

                // Always update baseline for next delta computation
                $holding->update([
                    'last_notified_change_percentage' => $percentageChange,
                ]);

                // Threshold: notify only when absolute delta >= 0.5%
                if (abs($deltaChange) >= 0.5) {
                    NotificationService::createStockUpdateNotification(
                        $holding->user,
                        $holding->stock->symbol,
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
     * Check price alerts for watchlist items
     */
    public static function checkPriceAlerts()
    {
        $alertsTriggered = 0;

        // Get all watchlist items with price alerts
        $watchlistItems = StockWatchlist::with(['user', 'stock'])
            ->whereNotNull('alert_price')
            ->get();

        foreach ($watchlistItems as $item) {
            $currentPrice = $item->stock->current_price;
            $alertPrice = $item->alert_price;
            
            if ($currentPrice > 0 && $alertPrice > 0) {
                $shouldTrigger = false;
                
                switch ($item->alert_type) {
                    case 'above':
                        $shouldTrigger = $currentPrice >= $alertPrice;
                        break;
                    case 'below':
                        $shouldTrigger = $currentPrice <= $alertPrice;
                        break;
                    case 'change':
                        // For change alerts, use the alert_price as the percentage threshold
                        $previousPrice = $item->stock->previous_close ?? $currentPrice;
                        $percentageChange = (($currentPrice - $previousPrice) / $previousPrice) * 100;
                        $shouldTrigger = abs($percentageChange) >= $alertPrice;
                        break;
                }
                
                if ($shouldTrigger) {
                    // Create price alert notification
                    NotificationService::createPriceAlertNotification(
                        $item->user,
                        $item->stock->symbol,
                        $currentPrice,
                        $alertPrice
                    );
                    $alertsTriggered++;
                    
                    // Deactivate the alert to prevent spam (delete the watchlist item)
                    $item->delete();
                    
                    Log::info("Price alert triggered: {$item->stock->symbol} reached alert price for user {$item->user->name}");
                }
            }
        }

        return [
            'alerts_triggered' => $alertsTriggered,
        ];
    }



    /**
     * Run complete stock monitoring process
     */
    public static function runMonitoring()
    {
        // info logs suppressed; only log errors
        
        // Monitor holdings
        $holdingsResult = self::monitorHoldings();
        
        // Check price alerts
        $alertsResult = self::checkPriceAlerts();
        
        $summary = [
            'holdings_updated' => $holdingsResult['holdings_updated'],
            'notifications_created' => $holdingsResult['notifications_created'],
            'alerts_triggered' => $alertsResult['alerts_triggered'],
        ];
        
        // info logs suppressed; only log errors
        
        return $summary;
    }
}
