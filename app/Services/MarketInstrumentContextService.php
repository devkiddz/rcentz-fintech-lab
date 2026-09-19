<?php

namespace App\Services;

use App\Models\MarketInstrument;
use App\Models\Signal;
use RuntimeException;

class MarketInstrumentContextService
{
    public function __construct(
        private readonly StockAnalysisService $stocks,
        private readonly ForexMarketDataService $forex,
        private readonly CryptoMarketDataService $crypto,
        private readonly MarketPriceRouter $marketPriceRouter
    ) {}

    public function forInstrument(MarketInstrument $instrument, ?string $marketplace = null): array
    {
        $instrument->loadMissing(['stock', 'forexPair', 'canonicalCryptoPair']);

        if (! $instrument->is_active) {
            throw new RuntimeException("Market instrument {$instrument->display_symbol} is inactive.");
        }

        if ($instrument->isStock()) {
            if (! $instrument->stock) {
                throw new RuntimeException("Stock backing record is missing for {$instrument->display_symbol}.");
            }

            $marketplace = $this->marketPriceRouter->normalizeMarketplace(
                $marketplace ?: $this->marketPriceRouter->activeMarketplace()
            );
            $analysis = $this->stocks->forStockInMarketplace($instrument->stock, $marketplace);

            return $this->normalize($analysis, $instrument, $marketplace, [
                'previous_close' => (float) ($instrument->stock->previous_close ?? 0),
                'market_session' => $marketplace === 'live'
                    ? app(MarketSessionService::class)->status()
                    : 'controlled',
            ]);
        }

        if ($instrument->isForex()) {
            if ($marketplace !== null && strtolower($marketplace) !== 'live') {
                throw new RuntimeException('Forex Signal analysis currently supports the live market authority only.');
            }
            if (! $instrument->forexPair) {
                throw new RuntimeException("Forex backing record is missing for {$instrument->display_symbol}.");
            }

            $context = $this->forex->context($instrument->forexPair);
            if (count((array) data_get($context, 'timeframes.1d', [])) < 2) {
                throw new RuntimeException("Forex history is not ready for {$instrument->display_symbol}; refresh real OHLC data first.");
            }

            return $this->normalize($context, $instrument, 'live', [
                'previous_close' => (float) ($instrument->forexPair->previous_close ?? 0),
            ]);
        }

        if ($instrument->isCrypto()) {
            if ($marketplace !== null && strtolower($marketplace) !== 'live') {
                throw new RuntimeException('Crypto Signal analysis currently supports the live market authority only.');
            }

            $pair = $instrument->canonicalCryptoPair;
            if (! $pair) {
                throw new RuntimeException("Crypto backing record is missing for {$instrument->display_symbol}.");
            }

            $context = $this->crypto->context($pair);
            if (count((array) data_get($context, 'timeframes.1d', [])) < 2) {
                throw new RuntimeException("Crypto history is not ready for {$instrument->display_symbol}; refresh real OHLC data first.");
            }

            return $this->normalize($context, $instrument, 'live', [
                'previous_close' => (float) ($pair->previous_close ?? 0),
                'market_session' => '24/7',
                'active_sessions' => ['24_7'],
                'preferred_sessions' => ['24_7'],
                'preferred_session_active' => true,
            ]);
        }

        throw new RuntimeException("Unsupported market instrument class: {$instrument->asset_class}.");
    }

    public function forSignal(Signal $signal): array
    {
        $signal->loadMissing(['marketInstrument.stock', 'marketInstrument.forexPair', 'marketInstrument.canonicalCryptoPair', 'stock']);
        $instrument = $signal->marketInstrument;

        if (! $instrument && $signal->stock_id) {
            $instrument = MarketInstrument::query()->where('stock_id', $signal->stock_id)->first();
        }

        if (! $instrument) {
            throw new RuntimeException("Signal #{$signal->id} has no market-instrument authority.");
        }

        return $this->forInstrument($instrument, $signal->marketplace);
    }

    private function normalize(array $context, MarketInstrument $instrument, string $marketplace, array $extra = []): array
    {
        $context['asset_class'] = $instrument->asset_class;
        $context['market_instrument_id'] = $instrument->id;
        $context['symbol'] = $instrument->symbol;
        $context['display_symbol'] = $instrument->display_symbol;
        $context['label'] = $instrument->name;
        $context['marketplace'] = $marketplace;
        $context['price_precision'] = (int) $instrument->price_precision;
        $context['pip_size'] = $instrument->pip_size !== null ? (float) $instrument->pip_size : null;
        $context['source'] = $context['source'] ?? $context['analysis_source'] ?? 'unknown';
        $context['analysis_source'] = $context['analysis_source'] ?? $context['source'];

        foreach ($extra as $key => $value) {
            $context[$key] = $value;
        }

        return $context;
    }
}
