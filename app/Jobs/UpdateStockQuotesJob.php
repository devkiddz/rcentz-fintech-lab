<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Stock;
use App\Models\StockQuote;
use App\Models\ApiRequestLog;
use App\Services\StockDataService;
use App\Services\YahooFinanceApiService;
use App\Events\StockPriceUpdated;
use App\Events\StockQuoteChanged;
use App\Events\MarketStatusChanged;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UpdateStockQuotesJob implements ShouldQueue
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
    public function handle(StockDataService $stockDataService, YahooFinanceApiService $yahooFinanceService): void
    {
        // info logs suppressed; only log errors

        try {
            // Get active stocks
            $stocks = Stock::where('is_active', true)->get();
            
            if ($stocks->isEmpty()) {
                // info/warning logs suppressed; only log errors
                return;
            }

            $updatedCount = 0;
            $errorCount = 0;

            foreach ($stocks as $stock) {
                try {
                    $this->updateStockQuote($stock, $stockDataService, $yahooFinanceService);
                    $updatedCount++;
                    
                    // Delay to respect Finnhub's 30 calls/sec rate limit
                    usleep(1100000); // 1.1 seconds (allows ~27 calls/sec with safety margin)
                    
                } catch (\Exception $e) {
                    Log::error("Failed to update quote for {$stock->symbol}", [
                        'error' => $e->getMessage(),
                        'stock_id' => $stock->id
                    ]);
                    $errorCount++;
                }
            }

            // info logs suppressed; only log errors

            // Broadcast market status update
            $this->broadcastMarketStatus();

        } catch (\Exception $e) {
            Log::error('UpdateStockQuotesJob failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update quote for a single stock
     */
    private function updateStockQuote(Stock $stock, StockDataService $stockDataService, YahooFinanceApiService $yahooFinanceService): void
    {
        $startTime = microtime(true);
        
        // Get Finnhub quote data
        $finnhubQuote = $stockDataService->getApiService()->getQuote($stock->symbol);
        if (!$finnhubQuote) {
            throw new \Exception("Failed to fetch Finnhub quote for {$stock->symbol}");
        }

        // Get Yahoo Finance volume data (only once a week to respect rate limits)
        $volume = null;
        $shouldUpdateVolume = !$stock->volume_updated_at || 
                             $stock->volume_updated_at->diffInDays(now()) >= 7;
        
        if ($shouldUpdateVolume && $yahooFinanceService->isAvailable()) {
            // info logs suppressed; only log errors
            $yahooQuote = $yahooFinanceService->getQuote($stock->symbol);
            if ($yahooQuote && isset($yahooQuote['volume'])) {
                $volume = $yahooQuote['volume'];
            }
        } else {
            // info logs suppressed; only log errors
        }

        // Get recommendations
        $recommendations = $stockDataService->getStockRecommendations($stock->symbol);
        $latestRecommendations = $recommendations ? $recommendations[0] ?? null : null;

        // Calculate change data
        $currentPrice = $finnhubQuote['c'] ?? 0;
        $previousClose = $finnhubQuote['pc'] ?? 0;
        $changeAmount = $currentPrice - $previousClose;
        $changePercentage = $previousClose > 0 ? (($changeAmount / $previousClose) * 100) : 0;

        // Create stock quote record
        $quoteData = [
            'symbol' => $stock->symbol,
            'current_price' => $currentPrice,
            'previous_close' => $previousClose,
            'change_amount' => $changeAmount,
            'change_percentage' => $changePercentage,
            'volume' => $volume, // Only set if volume was fetched
            'high' => $finnhubQuote['h'] ?? null,
            'low' => $finnhubQuote['l'] ?? null,
            'open' => $finnhubQuote['o'] ?? null,
            'fetched_at' => Carbon::now(),
        ];

        // Add recommendations if available
        if ($latestRecommendations) {
            $quoteData = array_merge($quoteData, [
                'strong_buy' => $latestRecommendations['strongBuy'] ?? 0,
                'buy' => $latestRecommendations['buy'] ?? 0,
                'hold' => $latestRecommendations['hold'] ?? 0,
                'sell' => $latestRecommendations['sell'] ?? 0,
                'strong_sell' => $latestRecommendations['strongSell'] ?? 0,
                'recommendation_date' => $latestRecommendations['period'] ?? null,
            ]);
        }

        // Save quote
        StockQuote::create($quoteData);

        // Prepare stock update data
        $stockUpdateData = [
            'current_price' => $currentPrice,
            'previous_close' => $previousClose,
            'change_amount' => $changeAmount,
            'change_percentage' => $changePercentage,
            'high' => $finnhubQuote['h'] ?? $stock->high,
            'low' => $finnhubQuote['l'] ?? $stock->low,
            'open' => $finnhubQuote['o'] ?? $stock->open,
            'last_updated' => Carbon::now(),
        ];

        // Only update volume if we fetched new volume data
        if ($volume !== null) {
            $stockUpdateData['volume'] = $volume;
            $stockUpdateData['volume_updated_at'] = Carbon::now();
        }

        // Update stock with latest data
        $stock->update($stockUpdateData);

        // Get the latest quote for broadcasting
        $latestQuote = StockQuote::where('symbol', $stock->symbol)
            ->orderBy('fetched_at', 'desc')
            ->first();

        // Dispatch broadcasting events
        if ($latestQuote) {
            // Broadcast stock quote change
            broadcast(new StockQuoteChanged($latestQuote));
            
            // Broadcast price update
            $priceData = [
                'current_price' => $currentPrice,
                'previous_close' => $previousClose,
                'change' => $changeAmount,
                'change_percent' => $changePercentage,
                'volume' => $stock->volume, // Use current volume from database
                'high' => $finnhubQuote['h'] ?? $stock->high,
                'low' => $finnhubQuote['l'] ?? $stock->low,
                'open' => $finnhubQuote['o'] ?? $stock->open,
            ];
            broadcast(new StockPriceUpdated($stock, $priceData));
        }

        // Log API request
        $responseTime = round((microtime(true) - $startTime) * 1000);
        ApiRequestLog::create([
            'api_provider' => 'finnhub',
            'endpoint' => 'quote',
            'symbol' => $stock->symbol,
            'response_time_ms' => $responseTime,
            'success' => true,
            'rate_limit_type' => 'market_data',
            'requested_at' => Carbon::now(),
        ]);

        if ($yahooFinanceService->isAvailable()) {
            ApiRequestLog::create([
                'api_provider' => 'yahoo_finance',
                'endpoint' => 'quote',
                'symbol' => $stock->symbol,
                'response_time_ms' => $responseTime,
                'success' => true,
                'rate_limit_type' => 'general',
                'requested_at' => Carbon::now(),
            ]);
        }

    }

    /**
     * Broadcast market status update
     */
    private function broadcastMarketStatus(): void
    {
        try {
            // Get top gainers, losers, and most active stocks
            $gainers = Stock::where('is_active', true)
                ->orderBy('change_percentage', 'desc')
                ->limit(5)
                ->get(['symbol', 'company_name', 'current_price', 'change_percentage']);

            $losers = Stock::where('is_active', true)
                ->orderBy('change_percentage', 'asc')
                ->limit(5)
                ->get(['symbol', 'company_name', 'current_price', 'change_percentage']);

            $mostActive = Stock::where('is_active', true)
                ->orderBy('volume', 'desc')
                ->limit(5)
                ->get(['symbol', 'company_name', 'current_price', 'volume']);

            $marketData = [
                'status' => 'open', // You can make this dynamic based on market hours
                'gainers' => $gainers->toArray(),
                'losers' => $losers->toArray(),
                'most_active' => $mostActive->toArray(),
            ];

            broadcast(new MarketStatusChanged($marketData));

            // info logs suppressed; only log errors

        } catch (\Exception $e) {
            Log::error('Failed to broadcast market status', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('UpdateStockQuotesJob failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
