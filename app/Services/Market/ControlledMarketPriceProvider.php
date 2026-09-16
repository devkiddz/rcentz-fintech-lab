<?php

namespace App\Services\Market;

use App\Contracts\MarketPriceProvider;
use App\Models\ControlledMarketInstrument;
use App\Models\Stock;
use RuntimeException;

final class ControlledMarketPriceProvider implements MarketPriceProvider
{
    public function key(): string
    {
        return 'controlled';
    }

    public function price(Stock $stock): float
    {
        $instrument = ControlledMarketInstrument::query()
            ->where('stock_id', $stock->id)
            ->where('is_active', true)
            ->first();

        if (! $instrument) {
            throw new RuntimeException('Controlled market instrument is unavailable for '.$stock->symbol.'.');
        }

        $price = (float) $instrument->current_price;

        if ($price <= 0) {
            throw new RuntimeException('Controlled market price is invalid for '.$stock->symbol.'.');
        }

        return $price;
    }
}
