<?php

namespace App\Services;

use App\Contracts\StockApiInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class FinnhubApiService implements StockApiInterface
{
    private string $apiKey;
    private string $baseUrl;
    private array $rateLimits;
    private array $lastRequestTimes;

    public function __construct()
    {
        $this->apiKey = config('services.finnhub.api_key');
        $this->baseUrl = 'https://finnhub.io/api/v1';
        $this->rateLimits = [
            'market_data' => ['calls' => 25, 'period' => 1], // 25 calls per second (safety margin)
            'fundamental' => ['calls' => 15, 'period' => 1],  // 15 calls per second
            'general' => ['calls' => 10, 'period' => 1],       // 10 calls per second
        ];
        $this->lastRequestTimes = [];
    }

    /**
     * Get real-time quote for a stock symbol
     */
    public function getQuote(string $symbol): ?array
    {
        if (!$this->checkRateLimit('market_data')) {
            Log::warning("Rate limit exceeded for market data API call", ['symbol' => $symbol]);
            return null;
        }

        try {
            $response = Http::timeout(10)->get($this->baseUrl . '/quote', [
                'symbol' => $symbol,
                'token' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $this->logApiRequest('market_data');
                return $response->json();
            }

            Log::error("Failed to fetch quote for {$symbol}", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Exception while fetching quote for {$symbol}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get company profile for a stock symbol
     */
    public function getCompanyProfile(string $symbol): ?array
    {
        if (!$this->checkRateLimit('fundamental')) {
            Log::warning("Rate limit exceeded for fundamental data API call", ['symbol' => $symbol]);
            return null;
        }

        try {
            $response = Http::timeout(10)->get($this->baseUrl . '/stock/profile2', [
                'symbol' => $symbol,
                'token' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $this->logApiRequest('fundamental');
                return $response->json();
            }

            Log::error("Failed to fetch company profile for {$symbol}", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Exception while fetching company profile for {$symbol}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get historical price data for a stock symbol
     */
    public function getHistoricalData(string $symbol, string $resolution = '1', int $from = null, int $to = null): ?array
    {
        if (!$this->checkRateLimit('market_data')) {
            Log::warning("Rate limit exceeded for historical data API call", ['symbol' => $symbol]);
            return null;
        }

        $from = $from ?? Carbon::now()->subDays(30)->timestamp;
        $to = $to ?? Carbon::now()->timestamp;

        try {
            $response = Http::timeout(15)->get($this->baseUrl . '/stock/candle', [
                'symbol' => $symbol,
                'resolution' => $resolution,
                'from' => $from,
                'to' => $to,
                'token' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $this->logApiRequest('market_data');
                return $response->json();
            }

            Log::error("Failed to fetch historical data for {$symbol}", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Exception while fetching historical data for {$symbol}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get news for a stock symbol
     */
    public function getNews(string $symbol, int $limit = 10): ?array
    {
        if (!$this->checkRateLimit('general')) {
            Log::warning("Rate limit exceeded for news API call", ['symbol' => $symbol]);
            return null;
        }

        try {
            $response = Http::timeout(10)->get($this->baseUrl . '/company-news', [
                'symbol' => $symbol,
                'from' => Carbon::now()->subDays(7)->format('Y-m-d'),
                'to' => Carbon::now()->format('Y-m-d'),
                'token' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $this->logApiRequest('general');
                $news = $response->json();
                return array_slice($news, 0, $limit);
            }

            Log::error("Failed to fetch news for {$symbol}", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Exception while fetching news for {$symbol}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get analyst recommendations for a stock symbol
     */
    public function getRecommendations(string $symbol): ?array
    {
        if (!$this->checkRateLimit('fundamental')) {
            Log::warning("Rate limit exceeded for recommendations API call", ['symbol' => $symbol]);
            return null;
        }

        try {
            $response = Http::timeout(10)->get($this->baseUrl . '/stock/recommendation', [
                'symbol' => $symbol,
                'token' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $this->logApiRequest('fundamental');
                return $response->json();
            }

            Log::error("Failed to fetch recommendations for {$symbol}", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Exception while fetching recommendations for {$symbol}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get market overview/summary
     */
    public function getMarketOverview(): ?array
    {
        if (!$this->checkRateLimit('market_data')) {
            Log::warning("Rate limit exceeded for market overview API call");
            return null;
        }

        try {
            // Use major stocks instead of indices (free API limitation)
            $majorStocks = ['AAPL', 'MSFT', 'GOOGL']; // Apple, Microsoft, Google
            $overview = [];

            foreach ($majorStocks as $stock) {
                $quote = $this->getQuote($stock);
                if ($quote) {
                    $overview[$stock] = $quote;
                }
            }

            $this->logApiRequest('market_data');
            return $overview;
        } catch (\Exception $e) {
            Log::error("Exception while fetching market overview", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if API is available and working
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->baseUrl . '/quote', [
                'symbol' => 'AAPL',
                'token' => $this->apiKey,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("API availability check failed", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get remaining API calls for current period
     */
    public function getRemainingCalls(): int
    {
        $totalCalls = 0;
        foreach ($this->rateLimits as $type => $limit) {
            $calls = Cache::get("finnhub_calls_{$type}", 0);
            $totalCalls += $calls;
        }
        return $totalCalls;
    }

    /**
     * Get API rate limit information
     */
    public function getRateLimitInfo(): array
    {
        $info = [];
        foreach ($this->rateLimits as $type => $limit) {
            $calls = Cache::get("finnhub_calls_{$type}", 0);
            $info[$type] = [
                'calls_used' => $calls,
                'calls_limit' => $limit['calls'],
                'period_seconds' => $limit['period'],
                'remaining' => max(0, $limit['calls'] - $calls),
            ];
        }
        return $info;
    }

    /**
     * Check rate limit for specific API type with proper throttling
     */
    private function checkRateLimit(string $type): bool
    {
        $limit = $this->rateLimits[$type];
        $cacheKey = "finnhub_calls_{$type}";
        $calls = Cache::get($cacheKey, 0);
        
        if ($calls >= $limit['calls']) {
            // Wait for rate limit to reset
            sleep($limit['period']);
            Cache::forget($cacheKey);
            $calls = 0;
        }

        // Add minimum delay between requests to stay under 30/sec
        $lastRequestKey = "finnhub_last_request_{$type}";
        $lastRequest = Cache::get($lastRequestKey, 0);
        $now = microtime(true);
        $timeSinceLastRequest = $now - $lastRequest;
        
        // Ensure at least 40ms between requests (25 requests/sec max)
        $minInterval = 0.04; // 40ms
        if ($timeSinceLastRequest < $minInterval) {
            usleep(($minInterval - $timeSinceLastRequest) * 1000000);
        }
        
        Cache::put($lastRequestKey, microtime(true), 2);
        return true;
    }

    /**
     * Log API request for rate limiting
     */
    private function logApiRequest(string $type): void
    {
        $key = "finnhub_calls_{$type}";
        $limit = $this->rateLimits[$type];
        
        // Use sliding window approach for per-second rate limiting
        $windowKey = $key . '_' . time();
        Cache::add($windowKey, 0, $limit['period'] + 1);
        Cache::increment($windowKey);
        
        // Also track total calls for monitoring
        Cache::add($key, 0, $limit['period']);
        Cache::increment($key);
    }
} 