<?php

namespace App\Contracts;

use App\Models\MarketInstrument;

interface MarketPriceProvider
{
    public function price(MarketInstrument $instrument): float;

    public function key(): string;
}
