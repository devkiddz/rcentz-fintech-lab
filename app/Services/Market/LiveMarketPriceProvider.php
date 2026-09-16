<?php

namespace App\Services\Market;

use App\Contracts\MarketPriceProvider;
use App\Models\Stock;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class LiveMarketPriceProvider implements MarketPriceProvider
{
    public function key(): string
    {
        return 'live';
    }

    public function price(Stock $stock): float
    {
        // V5.22: instrument existence does not imply External Feed eligibility.
        // Internal/private symbols can share the stocks table, but External Feed
        // must never execute them against a seeded or stale raw database price.
        if (
            Schema::hasColumn('stocks', 'external_feed_enabled')
            && ! (bool) $stock->external_feed_enabled
        ) {
            throw new RuntimeException(
                'External Feed is not available for '.$stock->symbol.'. Switch to Internal Feed for this instrument.'
            );
        }

        $price = (float) $stock->getRawOriginal('current_price');

        if ($price <= 0) {
            throw new RuntimeException('External market price is unavailable for '.$stock->symbol.'.');
        }

        return $price;
    }
}
