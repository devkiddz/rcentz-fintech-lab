<?php

namespace Database\Seeders;

use App\Models\CurrencyRate;
use App\Models\ForexPair;
use App\Models\MarketInstrument;
use App\Models\Stock;
use Illuminate\Database\Seeder;

class ForexPairSeeder extends Seeder
{
    public function run(): void
    {
        $pairs = [
            ['EURUSD', 'EUR/USD', 'Euro / US Dollar', 'EUR', 'USD', 0.0001, 5, true, ['london', 'new_york', 'london_new_york_overlap']],
            ['GBPUSD', 'GBP/USD', 'British Pound / US Dollar', 'GBP', 'USD', 0.0001, 5, true, ['london', 'new_york', 'london_new_york_overlap']],
            ['USDJPY', 'USD/JPY', 'US Dollar / Japanese Yen', 'USD', 'JPY', 0.01, 3, true, ['tokyo', 'new_york']],
            ['USDCHF', 'USD/CHF', 'US Dollar / Swiss Franc', 'USD', 'CHF', 0.0001, 5, true, ['london', 'new_york', 'london_new_york_overlap']],
            ['AUDUSD', 'AUD/USD', 'Australian Dollar / US Dollar', 'AUD', 'USD', 0.0001, 5, true, ['sydney', 'tokyo', 'new_york']],
            ['USDCAD', 'USD/CAD', 'US Dollar / Canadian Dollar', 'USD', 'CAD', 0.0001, 5, true, ['new_york', 'london_new_york_overlap']],
            ['NZDUSD', 'NZD/USD', 'New Zealand Dollar / US Dollar', 'NZD', 'USD', 0.0001, 5, true, ['sydney', 'tokyo', 'new_york']],
            ['EURGBP', 'EUR/GBP', 'Euro / British Pound', 'EUR', 'GBP', 0.0001, 5, false, ['london']],
            ['EURJPY', 'EUR/JPY', 'Euro / Japanese Yen', 'EUR', 'JPY', 0.01, 3, false, ['tokyo', 'london']],
            ['GBPJPY', 'GBP/JPY', 'British Pound / Japanese Yen', 'GBP', 'JPY', 0.01, 3, false, ['tokyo', 'london']],
        ];

        $currencies = collect($pairs)
            ->flatMap(fn ($pair) => [$pair[3], $pair[4]])
            ->unique()
            ->values()
            ->all();

        $rates = CurrencyRate::query()
            ->whereIn('currency', $currencies)
            ->pluck('rate', 'currency');

        foreach ($pairs as [$symbol, $display, $name, $base, $quote, $pip, $precision, $featured, $sessions]) {
            $baseRate = (float) ($rates[$base] ?? 0);
            $quoteRate = (float) ($rates[$quote] ?? 0);
            $derivedRate = ($baseRate > 0 && $quoteRate > 0) ? ($quoteRate / $baseRate) : null;

            $pair = ForexPair::updateOrCreate(
                ['symbol' => $symbol],
                [
                    'display_symbol' => $display,
                    'name' => $name,
                    'base_currency' => $base,
                    'quote_currency' => $quote,
                    'pip_size' => $pip,
                    'price_precision' => $precision,
                    'current_rate' => $derivedRate,
                    'is_active' => true,
                    'is_featured' => $featured,
                    'external_feed_enabled' => true,
                    'preferred_sessions' => $sessions,
                    'last_updated' => $derivedRate ? now() : null,
                ]
            );

            $parent = MarketInstrument::updateOrCreate(
                ['asset_class' => MarketInstrument::ASSET_FOREX, 'symbol' => $symbol],
                [
                    'display_symbol' => $display,
                    'name' => $name,
                    'market' => 'global_fx',
                    'base_asset' => $base,
                    'quote_asset' => $quote,
                    'price_precision' => $precision,
                    'pip_size' => $pip,
                    'stock_id' => null,
                    'forex_pair_id' => $pair->id,
                    'is_active' => true,
                    'is_featured' => $featured,
                    'metadata' => ['preferred_sessions' => $sessions],
                ]
            );

            if ((int) ($pair->market_instrument_id ?? 0) !== (int) $parent->id) {
                $pair->update(['market_instrument_id' => $parent->id]);
            }
        }

        Stock::query()->orderBy('id')->each(function (Stock $stock) {
            $parent = MarketInstrument::updateOrCreate(
                ['asset_class' => MarketInstrument::ASSET_STOCK, 'symbol' => strtoupper($stock->symbol)],
                [
                    'display_symbol' => strtoupper($stock->symbol),
                    'name' => $stock->company_name,
                    'market' => 'us_equity',
                    'base_asset' => strtoupper($stock->symbol),
                    'quote_asset' => 'USD',
                    'price_precision' => 2,
                    'pip_size' => null,
                    'stock_id' => $stock->id,
                    'forex_pair_id' => null,
                    'is_active' => (bool) $stock->is_active,
                    'is_featured' => (bool) $stock->is_featured,
                    'metadata' => ['source' => 'stocks'],
                ]
            );

            if ((int) ($stock->market_instrument_id ?? 0) !== (int) $parent->id) {
                $stock->update(['market_instrument_id' => $parent->id]);
            }
        });

        $this->command?->info('Forex market foundation seeded: 10 pairs plus parent-first stock/forex MarketInstrument registry.');
    }
}
