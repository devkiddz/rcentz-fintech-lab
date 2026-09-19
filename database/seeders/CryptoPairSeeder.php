<?php

namespace Database\Seeders;

use App\Models\CryptoPair;
use App\Models\MarketInstrument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CryptoPairSeeder extends Seeder
{
    public function run(): void
    {
        $pairs = [
            ['BTCUSD', 'BTC/USD', 'Bitcoin / US Dollar', 'BTC', 'USD', 2, 0.01, true],
            ['ETHUSD', 'ETH/USD', 'Ethereum / US Dollar', 'ETH', 'USD', 2, 0.01, true],
            ['SOLUSD', 'SOL/USD', 'Solana / US Dollar', 'SOL', 'USD', 2, 0.01, true],
            ['XRPUSD', 'XRP/USD', 'XRP / US Dollar', 'XRP', 'USD', 4, 0.0001, true],
            ['BNBUSD', 'BNB/USD', 'BNB / US Dollar', 'BNB', 'USD', 2, 0.01, true],
            ['ADAUSD', 'ADA/USD', 'Cardano / US Dollar', 'ADA', 'USD', 4, 0.0001, true],
            ['DOGEUSD', 'DOGE/USD', 'Dogecoin / US Dollar', 'DOGE', 'USD', 6, 0.000001, true],
            ['LTCUSD', 'LTC/USD', 'Litecoin / US Dollar', 'LTC', 'USD', 2, 0.01, false],
            ['LINKUSD', 'LINK/USD', 'Chainlink / US Dollar', 'LINK', 'USD', 4, 0.0001, false],
            ['AVAXUSD', 'AVAX/USD', 'Avalanche / US Dollar', 'AVAX', 'USD', 4, 0.0001, false],
        ];

        DB::transaction(function () use ($pairs) {
            foreach ($pairs as [$symbol, $display, $name, $base, $quote, $precision, $tick, $featured]) {
                $parent = MarketInstrument::query()->updateOrCreate(
                    ['asset_class' => 'crypto', 'symbol' => $symbol],
                    [
                        'display_symbol' => $display,
                        'name' => $name,
                        'market' => 'global_crypto',
                        'base_asset' => $base,
                        'quote_asset' => $quote,
                        'price_precision' => $precision,
                        'pip_size' => $tick,
                        'stock_id' => null,
                        'forex_pair_id' => null,
                        // C1 establishes identity/data only. C2 activates the
                        // parent after Live/Controlled/analysis adapters exist.
                        'is_active' => false,
                        'is_featured' => $featured,
                        'metadata' => [
                            'source' => 'crypto_c1',
                            'market_hours' => '24_7',
                            'runtime_state' => 'foundation_only',
                        ],
                    ]
                );

                CryptoPair::query()->updateOrCreate(
                    ['symbol' => $symbol],
                    [
                        'market_instrument_id' => $parent->id,
                        'display_symbol' => $display,
                        'name' => $name,
                        'base_asset' => $base,
                        'quote_asset' => $quote,
                        'price_precision' => $precision,
                        'minimum_tick' => $tick,
                        'is_active' => true,
                        'is_featured' => $featured,
                        'external_feed_enabled' => true,
                        'metadata' => ['market_hours' => '24_7'],
                    ]
                );
            }
        });

        $this->command?->info('Crypto C1 market foundation seeded: 10 parent-first crypto pairs (runtime parents intentionally inactive until C2).');
    }
}
