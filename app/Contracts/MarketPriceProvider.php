<?php

namespace App\Contracts;

use App\Models\Stock;

interface MarketPriceProvider
{
    public function price(Stock $stock): float;

    public function key(): string;
}
