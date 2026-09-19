<?php

namespace App\Services;

use App\Models\CurrencyRate;
use App\Models\MarketInstrument;
use RuntimeException;

final class MarketSettlementService
{
    public function amountForBaseUnits(
        MarketInstrument $instrument,
        float $baseUnits,
        float $pairPrice,
        string $settlementCurrency,
        bool $requireFreshRates = true
    ): float {
        if ($baseUnits <= 0 || $pairPrice <= 0) {
            throw new RuntimeException('Settlement quantity and price must be greater than zero.');
        }

        $base = strtoupper((string) $instrument->base_asset);
        $quote = strtoupper((string) $instrument->quote_asset);
        $settlement = strtoupper(trim($settlementCurrency));

        if ($base === '' || $quote === '' || $settlement === '') {
            throw new RuntimeException('Instrument or wallet settlement currency is incomplete.');
        }

        if ($settlement === $base) {
            return $baseUnits;
        }

        $quoteAmount = $baseUnits * $pairPrice;
        if ($settlement === $quote) {
            return $quoteAmount;
        }

        return $this->convertFiat($quoteAmount, $quote, $settlement, $requireFreshRates);
    }


    public function profitLossToSettlement(
        MarketInstrument $instrument,
        float $quoteProfitLoss,
        float $pairPrice,
        string $settlementCurrency,
        bool $requireFreshRates = true
    ): float {
        if ($pairPrice <= 0) {
            throw new RuntimeException('Forex P/L conversion price must be greater than zero.');
        }

        $base = strtoupper((string) $instrument->base_asset);
        $quote = strtoupper((string) $instrument->quote_asset);
        $settlement = strtoupper(trim($settlementCurrency));

        if ($settlement === $quote) {
            return $quoteProfitLoss;
        }

        if ($settlement === $base) {
            return $quoteProfitLoss / $pairPrice;
        }

        return $this->convertFiat($quoteProfitLoss, $quote, $settlement, $requireFreshRates);
    }

    public function convertFiat(
        float $amount,
        string $fromCurrency,
        string $toCurrency,
        bool $requireFreshRates = true
    ): float {
        $from = strtoupper(trim($fromCurrency));
        $to = strtoupper(trim($toCurrency));

        if ($from === $to) {
            return $amount;
        }

        $fromRate = $this->strictRate($from, $requireFreshRates);
        $toRate = $this->strictRate($to, $requireFreshRates);

        return ($amount / $fromRate) * $toRate;
    }

    private function strictRate(string $currency, bool $requireFresh): float
    {
        if ($currency === 'USD') {
            return 1.0;
        }

        $row = CurrencyRate::query()->where('currency', $currency)->first();
        if (! $row || (float) $row->rate <= 0) {
            throw new RuntimeException("Settlement currency rate is unavailable for {$currency}.");
        }

        if ($requireFresh) {
            $maxAge = max(300, (int) config('services.exchange_rates.execution_max_age_seconds', 7200));
            if (! $row->last_updated || $row->last_updated->lt(now()->subSeconds($maxAge))) {
                throw new RuntimeException("Settlement currency rate is stale for {$currency}.");
            }
        }

        return (float) $row->rate;
    }
}
