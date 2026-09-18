<?php

namespace App\Services;

use App\Models\Stock;
use Illuminate\Support\Collection;
use RuntimeException;

class SignalMarketContextService
{
    public function __construct(
        private readonly MarketPriceRouter $marketPriceRouter,
        private readonly StockAnalysisService $stockAnalysis,
        private readonly MarketSessionService $marketSession
    ) {}

    public function forStock(Stock $stock, ?string $marketplace = null): array
    {
        if (! $stock->is_active) {
            throw new RuntimeException("Signal market context requires an active instrument: {$stock->symbol}.");
        }

        $marketplace = $this->marketPriceRouter->normalizeMarketplace(
            $marketplace ?: $this->marketPriceRouter->activeMarketplace()
        );

        $analysis = $this->stockAnalysis->forStockInMarketplace($stock, $marketplace);

        if (($analysis['source'] ?? null) === 'controlled_market_unavailable') {
            throw new RuntimeException("Controlled market context is unavailable for {$stock->symbol}.");
        }

        $price = (float) ($analysis['current_price'] ?? 0);

        if ($price <= 0) {
            $price = $this->marketPriceRouter->price($stock, $marketplace);
        }

        if ($price <= 0) {
            throw new RuntimeException("Signal market context has no valid price for {$stock->symbol}.");
        }

        return [
            'stock_id' => $stock->id,
            'symbol' => strtoupper($stock->symbol),
            'label' => $stock->company_name ?: $stock->symbol,
            'marketplace' => $marketplace,
            'current_price' => $price,
            'analysis_source' => $analysis['source'] ?? 'unknown',
            'has_chart' => (bool) ($analysis['has_chart'] ?? false),
            'trend' => $analysis['trend'] ?? 'Neutral',
            'momentum_percent' => (float) ($analysis['momentum_percent'] ?? 0),
            'momentum_label' => $analysis['momentum_label'] ?? 'Neutral',
            'support' => $analysis['support'] ?? null,
            'resistance' => $analysis['resistance'] ?? null,
            'sma20' => $analysis['sma20'] ?? null,
            'sma50' => $analysis['sma50'] ?? null,
            'sma200' => $analysis['sma200'] ?? null,
            'risk_reward' => $analysis['risk_reward'] ?? 'Balanced',
            'volume_current' => $analysis['volume_current'] ?? 0,
            'volume_average' => $analysis['volume_average'] ?? 0,
            'volume_vs_average' => $analysis['volume_vs_average'] ?? null,
            'timeframes' => $analysis['timeframes'] ?? [],
            'default_timeframe' => $analysis['default_timeframe'] ?? null,
            'analyst' => $analysis['analyst'] ?? [],
            'market_session' => $marketplace === 'live'
                ? $this->marketSession->status()
                : 'controlled',
            'captured_at' => now()->toIso8601String(),
        ];
    }

    public function forActiveStocks(?string $marketplace = null, int $limit = 25): Collection
    {
        $limit = max(1, min(250, $limit));

        return Stock::query()
            ->active()
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn (Stock $stock) => $this->forStock($stock, $marketplace));
    }
}
