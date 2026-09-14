<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FinnhubApiService;
use App\Services\YahooFinanceApiService;
use App\Services\StockDataService;

class TestStockApi extends Command
{
    protected $signature = 'test:stock-api {symbol?}';
    protected $description = 'Test stock API integration';

    public function handle()
    {
        $symbol = $this->argument('symbol') ?? 'AAPL';
        
        $this->info("Testing Stock API Integration for {$symbol}");
        $this->newLine();

        // Test Finnhub API
        $this->testFinnhubApi($symbol);
        
        // Test Yahoo Finance API
        $this->testYahooFinanceApi($symbol);
        
        // Test StockDataService
        $this->testStockDataService($symbol);
    }

    private function testFinnhubApi(string $symbol)
    {
        $this->info("=== Testing Finnhub API ===");
        
        $finnhub = new FinnhubApiService();
        
        if (!$finnhub->isAvailable()) {
            $this->error("Finnhub API is not available");
            return;
        }

        $this->info("✓ Finnhub API is available");
        
        // Test quote
        $quote = $finnhub->getQuote($symbol);
        if ($quote) {
            $this->info("✓ Quote received:");
            $this->line("  Current Price: $" . ($quote['c'] ?? 'N/A'));
            $this->line("  Previous Close: $" . ($quote['pc'] ?? 'N/A'));
            $this->line("  High: $" . ($quote['h'] ?? 'N/A'));
            $this->line("  Low: $" . ($quote['l'] ?? 'N/A'));
            $this->line("  Open: $" . ($quote['o'] ?? 'N/A'));
            $this->line("  Volume: " . ($quote['v'] ?? 'N/A'));
        } else {
            $this->error("✗ Failed to get quote");
        }

        // Test company profile
        $profile = $finnhub->getCompanyProfile($symbol);
        if ($profile) {
            $this->info("✓ Company profile received:");
            $this->line("  Name: " . ($profile['name'] ?? 'N/A'));
            $this->line("  Industry: " . ($profile['finnhubIndustry'] ?? 'N/A'));
            $this->line("  Market Cap: " . ($profile['marketCapitalization'] ?? 'N/A'));
        } else {
            $this->error("✗ Failed to get company profile");
        }

        // Test rate limits
        $rateLimit = $finnhub->getRateLimitInfo();
        $this->info("Rate Limit Info:");
        foreach ($rateLimit as $type => $info) {
            $this->line("  {$type}: {$info['calls_used']}/{$info['calls_limit']} calls used");
        }
        
        $this->newLine();
    }

    private function testYahooFinanceApi(string $symbol)
    {
        $this->info("=== Testing Yahoo Finance API ===");
        
        $yahoo = new YahooFinanceApiService();
        
        if (!$yahoo->isAvailable()) {
            $this->error("Yahoo Finance API is not available");
            return;
        }

        $this->info("✓ Yahoo Finance API is available");
        
        // Test quote (volume only)
        $quote = $yahoo->getQuote($symbol);
        if ($quote) {
            $this->info("✓ Quote received:");
            $this->line("  Volume: " . ($quote['volume'] ?? 'N/A'));
        } else {
            $this->error("✗ Failed to get quote");
        }

        // Test rate limits
        $rateLimit = $yahoo->getRateLimitInfo();
        $this->info("Rate Limit Info:");
        $this->line("  Requests per second: " . $rateLimit['requests_per_second']);
        $this->line("  Remaining per second: " . $rateLimit['remaining_per_second']);
        
        $this->newLine();
    }

    private function testStockDataService(string $symbol)
    {
        $this->info("=== Testing StockDataService ===");
        
        $stockDataService = new StockDataService(
            new FinnhubApiService(),
            new YahooFinanceApiService()
        );
        
        // Test API status
        $status = $stockDataService->getApiStatus();
        $this->info("API Status:");
        $this->line("  Finnhub: " . ($status['finnhub']['available'] ? 'Available' : 'Not Available'));
        $this->line("  Yahoo Finance: " . ($status['yahoo_finance']['available'] ? 'Available' : 'Not Available'));
        
        $this->newLine();
    }
}
