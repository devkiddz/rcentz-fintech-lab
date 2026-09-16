<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockQuote;

final class LiveMarketHealthService
{
    public function snapshot(StockDataService $stockData): array
    {
        $latestQuote = StockQuote::query()->latest('fetched_at')->first();
        $activeStocks = Stock::query()->where('is_active', true)->count();
        $freshStocks = Stock::query()
            ->where('is_active', true)
            ->whereNotNull('last_updated')
            ->where('last_updated', '>=', now()->subMinutes(15))
            ->count();

        $api = $stockData->getApiStatus();

        return [
            'finnhub_available' => (bool) ($api['finnhub']['available'] ?? false),
            'yahoo_available' => (bool) ($api['yahoo_finance']['available'] ?? false),
            'active_stocks' => $activeStocks,
            'fresh_stocks' => $freshStocks,
            'latest_quote_at' => $latestQuote?->fetched_at,
            'latest_quote_symbol' => $latestQuote?->symbol,
            'healthy' => (bool) ($api['finnhub']['available'] ?? false) && $latestQuote !== null,
        ];
    }
}
