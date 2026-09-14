<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class YahooFinanceApiService
{
    private $baseUrl = 'https://yahoo-finance15.p.rapidapi.com/api/v1';
    private $apiKey;
    private $rateLimitPerSecond = 5;
    private $rateLimitPerMinute = 300;

    public function __construct()
    {
        $this->apiKey = config('services.yahoo_finance.api_key');
    }

    /**
     * Check if the service is available
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get real-time quote data for a stock (focusing on volume)
     */
    public function getQuote(string $symbol): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        // Check rate limit
        if (!$this->checkRateLimit()) {
            Log::warning("Yahoo Finance API rate limit exceeded for symbol: {$symbol}");
            return null;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-RapidAPI-Host' => 'yahoo-finance15.p.rapidapi.com',
                    'X-RapidAPI-Key' => $this->apiKey
                ])
                ->get($this->baseUrl . '/markets/quote', [
                    'ticker' => $symbol,
                    'type' => 'STOCKS'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['body']) && !empty($data['body'])) {
                    $body = $data['body'];
                    $primaryData = $body['primaryData'] ?? [];
                    
                    // Extract volume and convert to number
                    $volume = null;
                    if (isset($primaryData['volume']) && $primaryData['volume'] !== 'N/A') {
                        $volume = (int) str_replace(',', '', $primaryData['volume']);
                    }
                    
                    return [
                        'symbol' => $symbol,
                        'volume' => $volume,
                        'source' => 'yahoo_finance'
                    ];
                }
            }

            Log::warning("Yahoo Finance API failed for symbol: {$symbol}", [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error("Yahoo Finance API error for symbol: {$symbol}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get quotes for multiple symbols (rate limited to 5 per second)
     */
    public function getQuotes(array $symbols): array
    {
        if (!$this->isAvailable() || empty($symbols)) {
            return [];
        }

        $quotes = [];
        $batchSize = 5; // Process 5 at a time to respect rate limit
        $batches = array_chunk($symbols, $batchSize);

        foreach ($batches as $batch) {
            foreach ($batch as $symbol) {
                $quote = $this->getQuote($symbol);
                if ($quote) {
                    $quotes[$symbol] = $quote;
                }
            }

            // Add delay between batches to respect rate limit
            if (count($batches) > 1) {
                usleep(200000); // 0.2 second delay
            }
        }

        return $quotes;
    }

    /**
     * Get market overview data
     */
    public function getMarketOverview(): ?array
    {
        // Get quotes for major stocks for market overview
        $majorStocks = ['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'TSLA'];
        $quotes = $this->getQuotes($majorStocks);

        if (empty($quotes)) {
            return null;
        }

        return [
            'status' => 'open',
            'quotes' => $quotes,
            'updated_at' => now()->toISOString()
        ];
    }

    /**
     * Get company profile information
     */
    public function getCompanyProfile(string $symbol): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        // Check rate limit
        if (!$this->checkRateLimit()) {
            Log::warning("Yahoo Finance API rate limit exceeded for company profile: {$symbol}");
            return null;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-RapidAPI-Host' => 'yahoo-finance15.p.rapidapi.com',
                    'X-RapidAPI-Key' => $this->apiKey
                ])
                ->get($this->baseUrl . '/markets/quote', [
                    'ticker' => $symbol,
                    'type' => 'STOCKS'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['body']) && !empty($data['body'])) {
                    $body = $data['body'];
                    
                    return [
                        'symbol' => $symbol,
                        'name' => $body['companyName'] ?? null,
                        'exchange' => $body['exchange'] ?? null,
                        'stock_type' => $body['stockType'] ?? null,
                        'source' => 'yahoo_finance'
                    ];
                }
            }

            Log::warning("Yahoo Finance API failed for company profile: {$symbol}", [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error("Yahoo Finance API error for company profile: {$symbol}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check rate limits (5 requests per second)
     */
    private function checkRateLimit(): bool
    {
        $cacheKey = 'yahoo_finance_rate_limit';
        $requests = Cache::get($cacheKey, 0);

        if ($requests >= $this->rateLimitPerSecond) {
            return false;
        }

        Cache::put($cacheKey, $requests + 1, 1); // Reset every second
        return true;
    }

    /**
     * Get rate limit information
     */
    public function getRateLimitInfo(): array
    {
        $cacheKey = 'yahoo_finance_rate_limit';
        $requests = Cache::get($cacheKey, 0);

        return [
            'requests_per_second' => $requests,
            'limit_per_second' => $this->rateLimitPerSecond,
            'remaining_per_second' => max(0, $this->rateLimitPerSecond - $requests),
            'service' => 'yahoo_finance'
        ];
    }

    /**
     * Log API request for rate limiting
     */
    private function logApiRequest(): void
    {
        $cacheKey = 'yahoo_finance_rate_limit';
        $requests = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $requests + 1, 1);
    }

    /**
     * Get historical data for a stock
     */
    public function getHistoricalData(string $symbol, string $interval = '1d', int $limit = 30): ?array
    {
        if (!$this->checkRateLimit()) {
            Log::warning("Rate limit exceeded for Yahoo Finance historical data API call");
            return null;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'x-rapidapi-host' => 'yahoo-finance15.p.rapidapi.com',
                    'x-rapidapi-key' => $this->apiKey,
                ])
                ->get('https://yahoo-finance15.p.rapidapi.com/api/v2/markets/stock/history', [
                    'symbol' => $symbol,
                    'interval' => $interval,
                    'limit' => $limit,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['body']) && is_array($data['body'])) {
                    $this->logApiRequest();
                    return $data;
                }
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
}
