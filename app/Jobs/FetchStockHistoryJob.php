<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Stock;
use App\Models\StockPriceHistory;
use App\Models\ApiRequestLog;
use App\Services\StockDataService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\YahooFinanceApiService;

class FetchStockHistoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 3;

    private ?string $symbol;
    private string $interval;

    /**
     * Create a new job instance.
     */
    public function __construct(?string $symbol = null, string $interval = '1D')
    {
        $this->symbol = $symbol;
        $this->interval = $interval;
    }

    /**
     * Execute the job.
     */
    public function handle(StockDataService $stockDataService): void
    {
        // info logs suppressed; only log errors

        try {
            if ($this->symbol) {
                // Fetch history for specific symbol
                $this->fetchHistoryForSymbol($this->symbol, $stockDataService);
            } else {
                // Fetch history for all active stocks
                $stocks = Stock::where('is_active', true)->get();
                
                foreach ($stocks as $stock) {
                    try {
                        $this->fetchHistoryForSymbol($stock->symbol, $stockDataService);
                        sleep(2); // 2 seconds delay between stocks for rate limiting
                    } catch (\Exception $e) {
                        Log::error("Failed to fetch history for {$stock->symbol}", [
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('FetchStockHistoryJob failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Fetch historical data for a specific symbol
     */
    private function fetchHistoryForSymbol(string $symbol, StockDataService $stockDataService): void
    {
        $startTime = microtime(true);

        // Get historical data from Yahoo Finance API
        $yahooFinanceService = app(YahooFinanceApiService::class);
        $historicalData = $yahooFinanceService->getHistoricalData($symbol, $this->interval, 30);
        
        if (!$historicalData || !isset($historicalData['body'])) {
            // info/warning logs suppressed; only log errors
            return;
        }

        $candles = $historicalData['body'] ?? [];
        $savedCount = 0;
        $skippedCount = 0;

        foreach ($candles as $candle) {
            try {
                // Check if record already exists
                $existing = StockPriceHistory::where('symbol', $symbol)
                    ->where('timestamp', $candle['timestamp'])
                    ->where('interval', $this->interval)
                    ->first();

                if ($existing) {
                    $skippedCount++;
                    continue;
                }

                // Create new record
                StockPriceHistory::create([
                    'symbol' => $symbol,
                    'open' => $candle['open'],
                    'high' => $candle['high'],
                    'low' => $candle['low'],
                    'close' => $candle['close'],
                    'volume' => $candle['volume'],
                    'timestamp' => $candle['timestamp'],
                    'interval' => $this->interval,
                ]);

                $savedCount++;
            } catch (\Exception $e) {
                Log::error("Failed to save historical data for {$symbol}", [
                    'timestamp' => $candle['timestamp'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Log API request
        $responseTime = round((microtime(true) - $startTime) * 1000);
        ApiRequestLog::create([
            'api_provider' => 'yahoo_finance',
            'endpoint' => 'historical_data',
            'symbol' => $symbol,
            'response_time_ms' => $responseTime,
            'success' => true,
            'rate_limit_type' => 'general',
            'requested_at' => Carbon::now(),
        ]);

        // info logs suppressed; only log errors
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('FetchStockHistoryJob failed', [
            'symbol' => $this->symbol,
            'interval' => $this->interval,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
