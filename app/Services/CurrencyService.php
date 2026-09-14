<?php

namespace App\Services;

use App\Models\CurrencyRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    /**
     * All supported currencies
     */
    const SUPPORTED_CURRENCIES = [
        'USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 
        'CHF', 'CNY', 'INR', 'KRW', 'MXN', 'BRL',
        'ZAR', 'RUB', 'SEK', 'NOK', 'DKK', 'SGD',
        'HKD', 'NZD', 'TRY', 'PLN', 'THB', 'IDR',
        'MYR', 'PHP', 'CZK', 'ILS', 'CLP', 'AED',
    ];

    /**
     * Update currency rates from API
     * Using exchangerate-api.com (free tier: 1,500 requests/month)
     */
    public function updateRates()
    {
        try {
            // Using exchangerate-api.com - Free tier available
            $response = Http::timeout(10)->get('https://api.exchangerate-api.com/v4/latest/USD');
            
            if ($response->successful()) {
                $data = $response->json();
                $rates = $data['rates'] ?? [];
                
                $now = now();
                
                foreach (self::SUPPORTED_CURRENCIES as $currency) {
                    if (isset($rates[$currency])) {
                        CurrencyRate::updateOrCreate(
                            ['currency' => $currency],
                            [
                                'rate' => $rates[$currency],
                                'last_updated' => $now,
                            ]
                        );
                    }
                }
                
                // Cache the last update time
                Cache::put('currency_rates_last_update', $now, now()->addDay());
                
                Log::info('Currency rates updated successfully');
                return true;
            }
            
            Log::error('Failed to fetch currency rates', ['status' => $response->status()]);
            return false;
            
        } catch (\Exception $e) {
            Log::error('Error updating currency rates', ['error' => $e->getMessage()]);
            
            // Fallback: Set default rates if no rates exist
            $this->setDefaultRates();
            return false;
        }
    }

    /**
     * Set default/fallback currency rates
     * These are approximate rates and should be updated by API
     */
    private function setDefaultRates()
    {
        $defaultRates = [
            'USD' => 1.0,
            'EUR' => 0.92,
            'GBP' => 0.79,
            'JPY' => 149.50,
            'AUD' => 1.53,
            'CAD' => 1.36,
            'CHF' => 0.88,
            'CNY' => 7.24,
            'INR' => 83.12,
            'KRW' => 1320.50,
            'MXN' => 17.15,
            'BRL' => 4.98,
            'ZAR' => 18.65,
            'RUB' => 92.50,
            'SEK' => 10.87,
            'NOK' => 10.92,
            'DKK' => 6.89,
            'SGD' => 1.34,
            'HKD' => 7.83,
            'NZD' => 1.67,
            'TRY' => 32.15,
            'PLN' => 4.02,
            'THB' => 35.50,
            'IDR' => 15625.0,
            'MYR' => 4.68,
            'PHP' => 56.25,
            'CZK' => 23.15,
            'ILS' => 3.75,
            'CLP' => 920.0,
            'AED' => 3.67,
        ];

        $now = now();
        
        foreach ($defaultRates as $currency => $rate) {
            CurrencyRate::firstOrCreate(
                ['currency' => $currency],
                [
                    'rate' => $rate,
                    'last_updated' => $now,
                ]
            );
        }
    }

    /**
     * Convert amount from one currency to another
     */
    public function convert($amount, $fromCurrency = 'USD', $toCurrency = 'USD')
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        // Update rates if needed
        if (CurrencyRate::needsUpdate()) {
            $this->updateRates();
        }

        // Get rates (all rates are relative to USD)
        $fromRate = CurrencyRate::getRate($fromCurrency);
        $toRate = CurrencyRate::getRate($toCurrency);

        // Convert: amount in FROM currency -> USD -> TO currency
        $amountInUSD = $amount / $fromRate;
        $convertedAmount = $amountInUSD * $toRate;

        return $convertedAmount;
    }

    /**
     * Get currency symbol
     */
    public function getSymbol($currency)
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'AUD' => 'A$',
            'CAD' => 'C$',
            'CHF' => 'CHF',
            'CNY' => '¥',
            'INR' => '₹',
            'KRW' => '₩',
            'MXN' => '$',
            'BRL' => 'R$',
            'ZAR' => 'R',
            'RUB' => '₽',
            'SEK' => 'kr',
            'NOK' => 'kr',
            'DKK' => 'kr',
            'SGD' => 'S$',
            'HKD' => 'HK$',
            'NZD' => 'NZ$',
            'TRY' => '₺',
            'PLN' => 'zł',
            'THB' => '฿',
            'IDR' => 'Rp',
            'MYR' => 'RM',
            'PHP' => '₱',
            'CZK' => 'Kč',
            'ILS' => '₪',
            'CLP' => '$',
            'AED' => 'د.إ',
        ];

        return $symbols[strtoupper($currency)] ?? '$';
    }

    /**
     * Format amount with currency symbol
     */
    public function format($amount, $currency = 'USD')
    {
        $symbol = $this->getSymbol($currency);
        
        // For currencies that typically don't use decimals (like JPY, KRW)
        $noDecimalCurrencies = ['JPY', 'KRW', 'IDR', 'CLP'];
        $decimals = in_array(strtoupper($currency), $noDecimalCurrencies) ? 0 : 2;
        
        return $symbol . number_format($amount, $decimals);
    }
}
