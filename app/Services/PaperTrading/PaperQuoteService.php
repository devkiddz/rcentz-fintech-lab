<?php
declare(strict_types=1);

namespace App\Services\PaperTrading;

use App\Models\MarketInstrument;
use App\Services\CryptoExecutionQuoteService;
use App\Services\ForexExecutionQuoteService;
use App\Services\MarketPriceRouter;
use App\Services\MarketSessionService;
use Carbon\Carbon;
use RuntimeException;

final class PaperQuoteService
{
    public function __construct(
        private MarketPriceRouter $prices,
        private MarketSessionService $stocks,
        private ForexExecutionQuoteService $forex,
        private CryptoExecutionQuoteService $crypto
    ) {}

    public function execution(MarketInstrument $instrument, string $side, string $marketplace): float
    {
        if (! $instrument->is_active || !in_array($instrument->asset_class, ['stock', 'forex', 'crypto', 'commodity'], true)) {
            throw new RuntimeException('Trading instrument is unavailable.');
        }
        if (!in_array($side, ['buy', 'sell'], true)) {
            throw new RuntimeException('Invalid execution side.');
        }
        $marketplace = $this->prices->normalizeMarketplace($marketplace);
        if ($instrument->isCommodity()) {
            return app(\App\Services\CommodityExecutionQuoteService::class)->quote($instrument, $side, $marketplace);
        }
        if ($marketplace === 'controlled') {
            return $this->valid($this->prices->price($instrument, 'controlled'));
        }
        if ($instrument->isForex()) {
            return $this->valid((float) $this->forex->quote($instrument, $side, 'live')['price']);
        }
        if ($instrument->isCrypto()) {
            return $this->valid((float) $this->crypto->quote($instrument, $side, 'live')['price']);
        }
        if (!$this->stocks->isOpen()) {
            throw new RuntimeException('Stock market is currently closed.');
        }
        $stock = $instrument->canonicalStock()->first() ?? $instrument->stock;
        $updated = $stock?->getRawOriginal('last_updated');
        if (!$updated || Carbon::parse($updated)->lt(now()->subSeconds((int) config('paper_trading.stock_max_quote_age_seconds', 300)))) {
            throw new RuntimeException('Live stock quote is stale or missing. Execution is unavailable.');
        }
        return $this->valid($this->prices->price($instrument, 'live'));
    }

    /** Display marks never call an external quote provider or mutate prices. */
    public function mark(MarketInstrument $instrument, string $marketplace): float
    {
        return $this->valid($this->prices->price($instrument, $marketplace));
    }

    private function valid(float $price): float
    {
        if (!is_finite($price) || $price <= 0) {
            throw new RuntimeException('Execution price is unavailable.');
        }
        return $price;
    }
}
