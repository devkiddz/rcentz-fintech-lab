<?php

namespace Database\Seeders;

use App\Models\MarketInstrument;
use App\Models\PrivateInvestmentAsset;
use App\Models\PrivateInvestmentInstrument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GoldHedgeInvestmentSeeder extends Seeder
{
    public function run(): void
    {
        $gold = MarketInstrument::query()
            ->where('asset_class', MarketInstrument::ASSET_COMMODITY)
            ->where('symbol', 'XAUUSD')
            ->firstOrFail();

        DB::transaction(function () use ($gold) {
            $investment = PrivateInvestmentInstrument::query()->updateOrCreate(
                ['slug' => 'gold-hedge-allocation'],
                [
                    'symbol' => 'GHAU',
                    'name' => 'Gold Hedge Allocation',
                    'category' => 'hedge_assets',
                    'description' => 'A market-linked private hedge allocation referencing the canonical Gold Spot / US Dollar (XAU/USD) instrument. Investment units and wallet accounting remain owned by the Private Investment Engine rather than the spot market.',
                    'risk_level' => 'medium',
                    'status' => 'paused',
                    'currency' => 'USD',
                    'opening_price' => 100.000000,
                    'current_price' => 100.000000,
                    'previous_price' => 100.000000,
                    'unit_supply' => 100000,
                    'available_units' => 100000,
                    'minimum_investment' => 100,
                    'maximum_investment' => 50000,
                    'management_fee_percent' => 0,
                    'lock_period_days' => 0,
                    'duration_days' => 365,
                    'return_interval_days' => 365,
                    'projected_return_min_percent' => 0,
                    'projected_return_max_percent' => 0,
                    'subscription_fee_percent' => 0,
                    'redemption_fee_percent' => 0,
                    'maturity_date' => null,
                    'is_featured' => true,
                    'is_visible' => true,
                    'last_valued_at' => now(),
                ]
            );

            PrivateInvestmentAsset::query()->updateOrCreate(
                [
                    'instrument_id' => $investment->id,
                    'name' => 'Gold Spot Reference (XAU/USD)',
                ],
                [
                    'market_instrument_id' => $gold->id,
                    'asset_type' => 'commodity_hedge_reference',
                    'description' => 'Canonical Gold Spot / US Dollar market reference used as the underlying hedge identity. This does not make private investment units identical to spot gold ounces.',
                    'acquisition_value' => 0,
                    'current_valuation' => 0,
                    'ownership_percentage' => 100,
                    'status' => 'active',
                    'acquired_at' => null,
                    'effective_at' => now(),
                    'notes' => 'Linked to MarketInstrument XAUUSD. Subscription remains paused until commercial hedge terms are explicitly activated.',
                ]
            );
        });

        $this->command?->info('Gold Hedge Allocation listed under Hedge Assets and linked to canonical XAU/USD.');
    }
}
