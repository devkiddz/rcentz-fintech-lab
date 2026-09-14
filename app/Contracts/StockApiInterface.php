<?php

namespace App\Contracts;

interface StockApiInterface
{
    /**
     * Get real-time quote for a stock symbol
     */
    public function getQuote(string $symbol): ?array;

    /**
     * Get company profile for a stock symbol
     */
    public function getCompanyProfile(string $symbol): ?array;

    /**
     * Get historical price data for a stock symbol
     */
    public function getHistoricalData(string $symbol, string $resolution = '1', int $from = null, int $to = null): ?array;

    /**
     * Get news for a stock symbol
     */
    public function getNews(string $symbol, int $limit = 10): ?array;

    /**
     * Get analyst recommendations for a stock symbol
     */
    public function getRecommendations(string $symbol): ?array;

    /**
     * Get market overview/summary
     */
    public function getMarketOverview(): ?array;

    /**
     * Check if API is available and working
     */
    public function isAvailable(): bool;

    /**
     * Get remaining API calls for current period
     */
    public function getRemainingCalls(): int;

    /**
     * Get API rate limit information
     */
    public function getRateLimitInfo(): array;
} 