<?php

namespace App\Observers;

use App\Models\StockQuote;
use App\Services\MarketCandleService;

class StockQuoteObserver
{
    public function __construct(
        private readonly MarketCandleService $candles
    ) {}

    public function created(StockQuote $quote): void
    {
        $price = (float) $quote->current_price;

        if ($price <= 0 || ! $quote->fetched_at) {
            return;
        }

        $this->candles->record(
            (string) $quote->symbol,
            $price,
            $quote->fetched_at
        );
    }
}
