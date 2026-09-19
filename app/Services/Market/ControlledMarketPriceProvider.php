<?php

namespace App\Services\Market;

use App\Contracts\MarketPriceProvider;
use App\Models\ControlledMarketInstrument;
use App\Models\MarketInstrument;
use RuntimeException;

final class ControlledMarketPriceProvider implements MarketPriceProvider
{
    public function key(): string
    {
        return 'controlled';
    }

    public function price(MarketInstrument $instrument): float
    {
        $controlled = ControlledMarketInstrument::query()
            ->where('market_instrument_id', $instrument->id)
            ->where('is_active', true)
            ->first();

        if (! $controlled) {
            throw new RuntimeException('Controlled market instrument is unavailable for '.$instrument->display_symbol.'.');
        }

        $price = (float) $controlled->current_price;

        if ($price <= 0) {
            throw new RuntimeException('Controlled market price is invalid for '.$instrument->display_symbol.'.');
        }

        return $price;
    }
}
