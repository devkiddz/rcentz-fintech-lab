<?php

namespace Database\Seeders;

use App\Models\CommodityInstrument;
use App\Models\MarketInstrument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommodityInstrumentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $parent = MarketInstrument::query()->updateOrCreate(
                [
                    'asset_class' => MarketInstrument::ASSET_COMMODITY,
                    'symbol' => 'XAUUSD',
                ],
                [
                    'display_symbol' => 'XAU/USD',
                    'name' => 'Gold Spot / US Dollar',
                    'market' => 'global_spot_metals',
                    'base_asset' => 'XAU',
                    'quote_asset' => 'USD',
                    'price_precision' => 2,
                    'pip_size' => 0.01,
                    'stock_id' => null,
                    'forex_pair_id' => null,
                    'is_active' => true,
                    'is_featured' => true,
                    'metadata' => [
                        'source' => 'commodity_foundation',
                        'commodity' => 'gold',
                        'unit' => 'troy_ounce',
                        'signal_runtime_state' => 'foundation_only',
                        'execution_runtime_state' => 'adapter_pending',
                    ],
                ]
            );

            CommodityInstrument::query()->updateOrCreate(
                ['symbol' => 'XAUUSD'],
                [
                    'market_instrument_id' => $parent->id,
                    'display_symbol' => 'XAU/USD',
                    'name' => 'Gold Spot / US Dollar',
                    'commodity_code' => 'XAU',
                    'quote_asset' => 'USD',
                    'provider_symbol' => 'XAU',
                    'provider_family' => 'precious_metal',
                    'unit' => 'troy_ounce',
                    'price_precision' => 2,
                    'minimum_tick' => 0.01,
                    'is_active' => true,
                    'is_featured' => true,
                    'external_feed_enabled' => true,
                    'metadata' => [
                        'provider' => 'alpha_vantage',
                        'history_type' => 'daily_price_points',
                        'market_hours' => 'global_spot',
                    ],
                ]
            );
        });

        $this->command?->info('Commodity foundation seeded: XAU/USD registered as a first-class MarketInstrument.');
    }
}
