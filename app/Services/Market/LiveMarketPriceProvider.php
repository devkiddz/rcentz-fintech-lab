<?php

namespace App\Services\Market;

use App\Contracts\MarketPriceProvider;
use App\Models\Stock;
use RuntimeException;

final class LiveMarketPriceProvider implements MarketPriceProvider
{
    public function key(): string
    {
        return 'live';
    }

    public function price(Stock $stock): float
    {
        $price = (float) $stock->getRawOriginal('current_price');

        if ($price <= 0) {
            throw new RuntimeException('Live market price is unavailable for '.$stock->symbol.'.');
        }

        return $price;
    }
}
