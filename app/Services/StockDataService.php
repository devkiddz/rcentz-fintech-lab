<?php

namespace App\Services;

use App\Contracts\StockApiInterface;
use App\Models\Stock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StockDataService
{
    private StockApiInterface $apiService;
    private YahooFinanceApiService $yahooFinanceService;

    public function __construct(StockApiInterface $apiService, YahooFinanceApiService $yahooFinanceService)
    {
        $this->apiService = $apiService;
        $this->yahooFinanceService = $yahooFinanceService;
    }

    /**
     * Update stock data from API
     */
    public function updateStockData(Stock $stock): bool
    {
        try {
            // Get quote data from Finnhub
            $quote = $this->apiService->getQuote($stock->symbol);
            if (!$quote) {
                // info/warning logs suppressed; only log errors
                return false;
            }

            // Get volume data from Yahoo Finance if available
            $volume = null;
            if ($this->yahooFinanceService->isAvailable()) {
                $yahooQuote = $this->yahooFinanceService->getQuote($stock->symbol);
                if ($yahooQuote && isset($yahooQuote['volume'])) {
                    $volume = $yahooQuote['volume'];
                }
            }

            // Get company profile for additional data
            $profile = $this->apiService->getCompanyProfile($stock->symbol);

            // Update stock with new data
            $stock->update([
                'current_price' => $quote['c'] ?? $stock->current_price,
                'previous_close' => $quote['pc'] ?? $stock->previous_close,
                'change_amount' => ($quote['c'] ?? 0) - ($quote['pc'] ?? 0),
                'change_percentage' => $this->calculateChangePercentage($quote['c'] ?? 0, $quote['pc'] ?? 0),
                'volume' => $volume ?? $quote['v'] ?? $stock->volume, // Use Yahoo Finance volume if available
                'high' => $quote['h'] ?? $stock->high,
                'low' => $quote['l'] ?? $stock->low,
                'open' => $quote['o'] ?? $stock->open,
                'last_updated' => Carbon::now(),
            ]);

            // Update additional data from profile if available
            if ($profile) {
                $stock->update([
                    'sector' => $profile['finnhubIndustry'] ?? $stock->sector,
                    'industry' => $profile['finnhubIndustry'] ?? $stock->industry,
                    'logo_url' => $profile['logo'] ?? $stock->logo_url,
                    'market_cap' => $profile['marketCapitalization'] ?? $stock->market_cap,
                    'pe_ratio' => $profile['peRatio'] ?? $stock->pe_ratio,
                    'dividend_yield' => $profile['dividendYield'] ?? $stock->dividend_yield,
                    'fifty_two_week_high' => $profile['52WeekHigh'] ?? $stock->fifty_two_week_high,
                    'fifty_two_week_low' => $profile['52WeekLow'] ?? $stock->fifty_two_week_low,
                ]);
            }

            // info logs suppressed; only log errors
            return true;

        } catch (\Exception $e) {
            Log::error("Exception while updating stock data for {$stock->symbol}", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update multiple stocks
     */
    public function updateMultipleStocks(array $symbols): array
    {
        $results = [
            'success' => [],
            'failed' => []
        ];

        foreach ($symbols as $symbol) {
            $stock = Stock::where('symbol', $symbol)->first();
            if (!$stock) {
                $results['failed'][] = $symbol;
                continue;
            }

            if ($this->updateStockData($stock)) {
                $results['success'][] = $symbol;
            } else {
                $results['failed'][] = $symbol;
            }
        }

        return $results;
    }

    /**
     * Get cached stock data or fetch from API
     */
    public function getStockData(string $symbol, bool $forceRefresh = false): ?array
    {
        $cacheKey = "stock_data_{$symbol}";
        
        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $stock = Stock::where('symbol', $symbol)->first();
        if (!$stock) {
            return null;
        }

        $data = [
            'symbol' => $stock->symbol,
            'company_name' => $stock->company_name,
            'current_price' => $stock->current_price,
            'previous_close' => $stock->previous_close,
            'change_amount' => $stock->change_amount,
            'change_percentage' => $stock->change_percentage,
            'volume' => $stock->volume,
            'high' => $stock->high,
            'low' => $stock->low,
            'open' => $stock->open,
            'market_cap' => $stock->market_cap,
            'pe_ratio' => $stock->pe_ratio,
            'dividend_yield' => $stock->dividend_yield,
            'sector' => $stock->sector,
            'industry' => $stock->industry,
            'last_updated' => $stock->last_updated,
        ];

        // Cache for 5 minutes
        Cache::put($cacheKey, $data, 300);

        return $data;
    }

    /**
     * Get market overview data
     */
    public function getMarketOverview(): ?array
    {
        $cacheKey = 'market_overview';
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $overview = $this->apiService->getMarketOverview();
        if ($overview) {
            Cache::put($cacheKey, $overview, 300); // Cache for 5 minutes
        }

        return $overview;
    }

    /**
     * Get stock news
     */
    public function getStockNews(string $symbol, int $limit = 10): ?array
    {
        $cacheKey = "stock_news_{$symbol}";
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $news = $this->apiService->getNews($symbol, $limit);
        if ($news) {
            Cache::put($cacheKey, $news, 1800); // Cache for 30 minutes
        }

        return $news;
    }

    /**
     * Get historical data for charts
     */
    public function getHistoricalData(string $symbol, string $resolution = '1', int $days = 30): ?array
    {
        $cacheKey = "historical_data_{$symbol}_{$resolution}_{$days}";
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $from = Carbon::now()->subDays($days)->timestamp;
        $to = Carbon::now()->timestamp;

        $data = $this->apiService->getHistoricalData($symbol, $resolution, $from, $to);
        if ($data) {
            Cache::put($cacheKey, $data, 3600); // Cache for 1 hour
        }

        return $data;
    }

    /**
     * Check API health and rate limits
     */
    public function getApiStatus(): array
    {
        return [
            'finnhub' => [
                'available' => $this->apiService->isAvailable(),
                'rate_limits' => $this->apiService->getRateLimitInfo(),
                'remaining_calls' => $this->apiService->getRemainingCalls(),
            ],
            'yahoo_finance' => [
                'available' => $this->yahooFinanceService->isAvailable(),
                'rate_limits' => $this->yahooFinanceService->getRateLimitInfo(),
            ],
        ];
    }

    /**
     * Calculate change percentage
     */
    private function calculateChangePercentage(float $current, float $previous): float
    {
        if ($previous == 0) {
            return 0;
        }
        return (($current - $previous) / $previous) * 100;
    }

    /**
     * Clear cache for a specific stock
     */
    public function clearStockCache(string $symbol): void
    {
        Cache::forget("stock_data_{$symbol}");
        Cache::forget("stock_news_{$symbol}");
        Cache::forget("historical_data_{$symbol}_1_30");
    }

    /**
     * Get stock recommendations
     */
    public function getStockRecommendations(string $symbol): ?array
    {
        $cacheKey = "stock_recommendations_{$symbol}";
        
        return Cache::remember($cacheKey, 3600, function () use ($symbol) {
            return $this->apiService->getRecommendations($symbol);
        });
    }

    /**
     * Get the API service instance
     */
    public function getApiService(): StockApiInterface
    {
        return $this->apiService;
    }

    /**
     * Clear all stock-related cache
     */
    public function clearAllStockCache(): void
    {
        Cache::forget('market_overview');
        // Note: In production, you might want to use cache tags for better cache management
    }
} 