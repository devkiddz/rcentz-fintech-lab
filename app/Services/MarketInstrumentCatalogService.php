<?php

namespace App\Services;

use App\Models\MarketInstrument;
use Illuminate\Support\Facades\DB;

class MarketInstrumentCatalogService
{
    public function __construct(
        private MarketPriceRouter $prices
    ) {}

    public function build(?string $assetClass = null, bool $activeOnly = true): array
    {
        $query = MarketInstrument::query()->with([
            'stock',
            'forexPair',
            'canonicalStock',
            'canonicalForexPair',
            'canonicalCryptoPair',
            'controlledMarketInstrument',
        ]);
        $countQuery = MarketInstrument::query();

        if ($activeOnly) {
            $query->where('is_active', true);
            $countQuery->where('is_active', true);
        }

        if ($assetClass) {
            $query->where('asset_class', strtolower($assetClass));
        }

        $marketplace = $this->prices->activeMarketplace();

        $instruments = $query
            ->orderBy('asset_class')
            ->orderBy('symbol')
            ->get()
            ->each(function (MarketInstrument $instrument) use ($marketplace) {
                try {
                    $instrument->setAttribute('runtime_price', $this->prices->price($instrument, $marketplace));
                    $instrument->setAttribute('runtime_price_error', null);
                } catch (\Throwable $e) {
                    $instrument->setAttribute('runtime_price', null);
                    $instrument->setAttribute('runtime_price_error', $e->getMessage());
                }

                $instrument->setAttribute('runtime_previous', $this->previousPrice($instrument, $marketplace));
            });

        $rawCounts = $countQuery
            ->select('asset_class', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('asset_class')
            ->pluck('aggregate', 'asset_class');

        $counts = [
            'total' => (int) $rawCounts->sum(),
            'active' => (int) MarketInstrument::query()->where('is_active', true)->count(),
            'stock' => (int) ($rawCounts['stock'] ?? 0),
            'forex' => (int) ($rawCounts['forex'] ?? 0),
            'crypto' => (int) ($rawCounts['crypto'] ?? 0),
        ];

        return compact('instruments', 'counts', 'marketplace');
    }

    private function previousPrice(MarketInstrument $instrument, string $marketplace): ?float
    {
        if ($marketplace === 'controlled') {
            $controlled = $instrument->controlledMarketInstrument;
            return $controlled ? (float) ($controlled->previous_price ?: $controlled->current_price) : null;
        }

        if ($instrument->isStock()) {
            $stock = $instrument->canonicalStock ?: $instrument->stock;
            return $stock ? (float) ($stock->previous_close ?: $stock->getRawOriginal('current_price')) : null;
        }

        if ($instrument->isForex()) {
            $pair = $instrument->canonicalForexPair ?: $instrument->forexPair;
            return $pair ? (float) ($pair->previous_close ?: $pair->current_rate) : null;
        }

        if ($instrument->isCrypto()) {
            $pair = $instrument->canonicalCryptoPair;
            return $pair ? (float) ($pair->previous_close ?: $pair->current_rate) : null;
        }

        return null;
    }
}
