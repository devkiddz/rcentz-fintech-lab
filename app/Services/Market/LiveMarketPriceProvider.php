<?php

namespace App\Services\Market;

use App\Contracts\MarketPriceProvider;
use App\Models\MarketInstrument;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class LiveMarketPriceProvider implements MarketPriceProvider
{
    public function key(): string
    {
        return 'live';
    }

    public function price(MarketInstrument $instrument): float
    {
        if ($instrument->isStock()) {
            $stock = $instrument->stock;

            if (! $stock) {
                throw new RuntimeException('Stock adapter is missing for '.$instrument->display_symbol.'.');
            }

            if (
                Schema::hasColumn('stocks', 'external_feed_enabled')
                && ! (bool) $stock->external_feed_enabled
            ) {
                throw new RuntimeException(
                    'External Feed is not available for '.$instrument->display_symbol.'. Switch to Internal Feed for this instrument.'
                );
            }

            $price = (float) $stock->getRawOriginal('current_price');

            if ($price <= 0) {
                throw new RuntimeException('External market price is unavailable for '.$instrument->display_symbol.'.');
            }

            return $price;
        }

        if ($instrument->isForex()) {
            $pair = $instrument->forexPair;

            if (! $pair) {
                throw new RuntimeException('Forex adapter is missing for '.$instrument->display_symbol.'.');
            }

            if (! (bool) $pair->external_feed_enabled) {
                throw new RuntimeException('External forex feed is disabled for '.$instrument->display_symbol.'.');
            }

            $price = (float) ($pair->current_rate ?? 0);

            if ($price <= 0) {
                throw new RuntimeException('External forex rate is unavailable for '.$instrument->display_symbol.'.');
            }

            return $price;
        }

        if ($instrument->isCrypto()) {
            $pair = $instrument->canonicalCryptoPair;

            if (! $pair) {
                throw new RuntimeException('Crypto adapter is missing for '.$instrument->display_symbol.'.');
            }

            if (! (bool) $pair->external_feed_enabled) {
                throw new RuntimeException('External crypto feed is disabled for '.$instrument->display_symbol.'.');
            }

            $price = (float) ($pair->current_rate ?? 0);

            if ($price <= 0) {
                throw new RuntimeException('External crypto price is unavailable for '.$instrument->display_symbol.'.');
            }

            return $price;
        }

        throw new RuntimeException('No Live price adapter exists for '.$instrument->asset_class.' '.$instrument->display_symbol.'.');
    }
}
