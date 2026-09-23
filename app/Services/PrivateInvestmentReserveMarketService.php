<?php

namespace App\Services;

use App\Models\MarketInstrument;
use App\Models\StockPriceHistory;
use RuntimeException;

final class PrivateInvestmentReserveMarketService
{
    public function currentPrice(MarketInstrument $market): float
    {
        $market->loadMissing([
            'canonicalStock',
            'canonicalForexPair',
            'canonicalCryptoPair',
            'canonicalCommodityInstrument',
            'stock',
            'forexPair',
        ]);

        $price = match ($market->asset_class) {
            MarketInstrument::ASSET_STOCK =>
                $this->stockCurrentPrice($market),

            MarketInstrument::ASSET_FOREX =>
                (float) (
                    $market->canonicalForexPair?->current_rate
                    ?? $market->forexPair?->current_rate
                    ?? 0
                ),

            MarketInstrument::ASSET_CRYPTO =>
                (float) (
                    $market->canonicalCryptoPair?->current_rate
                    ?? 0
                ),

            MarketInstrument::ASSET_COMMODITY =>
                (float) (
                    $market->canonicalCommodityInstrument?->current_price
                    ?? 0
                ),

            default => 0,
        };

        if ($price <= 0) {
            throw new RuntimeException(
                'No positive public-market price is available for '
                .$market->symbol.'.'
            );
        }

        return $price;
    }

    public function unitLabel(MarketInstrument $market): string
    {
        $market->loadMissing([
            'canonicalStock',
            'canonicalForexPair',
            'canonicalCryptoPair',
            'canonicalCommodityInstrument',
        ]);

        return match ($market->asset_class) {
            MarketInstrument::ASSET_STOCK => 'shares',

            MarketInstrument::ASSET_FOREX =>
                (string) (
                    $market->canonicalForexPair?->base_currency
                    ?? $market->base_asset
                    ?? 'currency'
                ),

            MarketInstrument::ASSET_CRYPTO =>
                (string) (
                    $market->canonicalCryptoPair?->base_asset
                    ?? $market->base_asset
                    ?? 'tokens'
                ),

            MarketInstrument::ASSET_COMMODITY =>
                $this->commodityUnit($market),

            default => 'units',
        };
    }

    public function dailyHistory(
        MarketInstrument $market,
        int $limit = 220
    ): array {
        $market->loadMissing([
            'canonicalStock',
            'canonicalForexPair',
            'canonicalCryptoPair',
            'canonicalCommodityInstrument',
            'stock',
            'forexPair',
        ]);

        return match ($market->asset_class) {
            MarketInstrument::ASSET_STOCK =>
                $this->stockHistory($market, $limit),

            MarketInstrument::ASSET_FOREX =>
                $this->forexHistory($market, $limit),

            MarketInstrument::ASSET_CRYPTO =>
                $this->cryptoHistory($market, $limit),

            MarketInstrument::ASSET_COMMODITY =>
                $this->commodityHistory($market, $limit),

            default => [],
        };
    }

    private function stockCurrentPrice(
        MarketInstrument $market
    ): float {
        $stock = $market->canonicalStock ?? $market->stock;

        if (! $stock) {
            return 0;
        }

        return (float) (
            $stock->getRawOriginal('current_price')
            ?? $stock->current_price
            ?? 0
        );
    }

    private function stockHistory(
        MarketInstrument $market,
        int $limit
    ): array {
        $stock = $market->canonicalStock ?? $market->stock;

        if (! $stock) {
            return [];
        }

        return StockPriceHistory::query()
            ->where('symbol', $stock->symbol)
            ->whereIn('interval', ['1D', '1d'])
            ->latest('timestamp')
            ->limit($limit)
            ->get()
            ->sortBy('timestamp')
            ->values()
            ->map(fn ($row) => [
                'time' => $row->timestamp,
                'open' => (float) $row->open,
                'high' => (float) $row->high,
                'low' => (float) $row->low,
                'close' => (float) $row->close,
            ])
            ->all();
    }

    private function forexHistory(
        MarketInstrument $market,
        int $limit
    ): array {
        $pair = $market->canonicalForexPair
            ?? $market->forexPair;

        if (! $pair) {
            return [];
        }

        return $pair->candles()
            ->whereIn('interval', ['1D', '1d'])
            ->latest('timestamp')
            ->limit($limit)
            ->get()
            ->sortBy('timestamp')
            ->values()
            ->map(fn ($row) => [
                'time' => $row->timestamp,
                'open' => (float) $row->open,
                'high' => (float) $row->high,
                'low' => (float) $row->low,
                'close' => (float) $row->close,
            ])
            ->all();
    }

    private function cryptoHistory(
        MarketInstrument $market,
        int $limit
    ): array {
        $pair = $market->canonicalCryptoPair;

        if (! $pair) {
            return [];
        }

        return $pair->candles()
            ->whereIn('interval', ['1D', '1d'])
            ->latest('timestamp')
            ->limit($limit)
            ->get()
            ->sortBy('timestamp')
            ->values()
            ->map(fn ($row) => [
                'time' => $row->timestamp,
                'open' => (float) $row->open,
                'high' => (float) $row->high,
                'low' => (float) $row->low,
                'close' => (float) $row->close,
            ])
            ->all();
    }

    private function commodityHistory(
        MarketInstrument $market,
        int $limit
    ): array {
        $commodity = $market->canonicalCommodityInstrument;

        if (! $commodity) {
            return [];
        }

        return $commodity->pricePoints()
            ->where('interval', '1d')
            ->latest('timestamp')
            ->limit($limit)
            ->get()
            ->sortBy('timestamp')
            ->values()
            ->map(function ($row) {
                $price = (float) $row->price;

                return [
                    'time' => $row->timestamp,
                    'open' => $price,
                    'high' => $price,
                    'low' => $price,
                    'close' => $price,
                ];
            })
            ->all();
    }

    private function commodityUnit(
        MarketInstrument $market
    ): string {
        $unit = strtolower(
            (string) (
                $market->canonicalCommodityInstrument?->unit
                ?? ''
            )
        );

        if (
            str_contains($unit, 'ounce')
            || str_contains($unit, 'oz')
        ) {
            return 'oz';
        }

        return $unit !== ''
            ? $unit
            : 'units';
    }
}