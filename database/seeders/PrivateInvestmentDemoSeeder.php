<?php

namespace Database\Seeders;

use App\Models\PrivateInvestmentAsset;
use App\Models\PrivateInvestmentEvent;
use App\Models\PrivateInvestmentInstrument;
use App\Models\PrivateInvestmentPrice;
use Illuminate\Database\Seeder;

class PrivateInvestmentDemoSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            [
                'slug'=>'warri-residential-growth-fund','symbol'=>'WRGF','name'=>'Warri Residential Growth Fund',
                'category'=>'real_estate','description'=>'A private residential property pool focused on income-producing and appreciating assets in Warri.',
                'risk_level'=>'medium','opening'=>25.00,'drift'=>0.0018,'wave'=>0.0060,'supply'=>100000,'minimum'=>250,'maximum'=>25000,'fee'=>1.25,'featured'=>true,
                'assets'=>[
                    ['property','Effurun Four-Unit Apartment Block',420000,468000,60,'Core residential rental asset.'],
                    ['property','Warri Commercial Annex',175000,193500,25,'Small commercial annex supporting mixed-income cash flow.'],
                    ['infrastructure','Backup Power & Water Infrastructure',62000,69000,15,'Generator, inverter and water infrastructure supporting occupancy.'],
                ],
                'events'=>[
                    [31,'rental_income','positive','percentage',1.30,'Monthly rental collections exceeded baseline occupancy assumptions.'],
                    [20,'maintenance_expense','negative','percentage',-0.85,'Roof and plumbing maintenance completed across the residential block.'],
                    [9,'capital_improvement','positive','percentage',1.75,'Power and water infrastructure upgrade improved asset quality and occupancy resilience.'],
                ],
            ],
            [
                'slug'=>'lekki-income-property-fund','symbol'=>'LIPF','name'=>'Lekki Income Property Fund',
                'category'=>'real_estate','description'=>'A private income-oriented Lagos property instrument backed by serviced residential units.',
                'risk_level'=>'medium','opening'=>42.50,'drift'=>0.0014,'wave'=>0.0050,'supply'=>80000,'minimum'=>500,'maximum'=>50000,'fee'=>1.40,'featured'=>true,
                'assets'=>[
                    ['property','Lekki Serviced Apartments',980000,1075000,78,'Primary serviced-apartment portfolio.'],
                    ['amenity','Solar & Smart Access Upgrade',145000,158000,12,'Solar backup, metering and access-control upgrade.'],
                    ['reserve','Property Operations Reserve',120000,124500,10,'Maintenance and operating liquidity reserve.'],
                ],
                'events'=>[
                    [34,'occupancy_improvement','positive','percentage',1.10,'Improved serviced-apartment occupancy increased forward income expectations.'],
                    [17,'operating_expense','negative','percentage',-0.65,'Quarterly facility and service-charge expense recognized.'],
                    [6,'independent_valuation_revision','positive','percentage',1.45,'Independent valuation reflected neighborhood rental and asset-price improvement.'],
                ],
            ],
            [
                'slug'=>'rcentz-technology-growth-basket','symbol'=>'RTGB','name'=>'Rcentz Technology Growth Basket',
                'category'=>'stock_market','description'=>'An internally managed technology growth basket used to demonstrate basket valuation and private investment mechanics.',
                'risk_level'=>'high','opening'=>18.75,'drift'=>0.0022,'wave'=>0.0100,'supply'=>150000,'minimum'=>100,'maximum'=>15000,'fee'=>0.95,'featured'=>true,
                'assets'=>[
                    ['equity_basket','Large-Cap Technology Allocation',620000,684000,65,'Diversified large-cap technology allocation.'],
                    ['equity_basket','Semiconductor Growth Allocation',245000,273000,25,'Semiconductor and infrastructure allocation.'],
                    ['cash_reserve','Rebalancing Reserve',95000,96000,10,'Liquidity reserve for rebalancing.'],
                ],
                'events'=>[
                    [28,'capital_appreciation','positive','percentage',2.40,'Underlying technology basket appreciated following a stronger valuation period.'],
                    [15,'rebalancing','neutral','percentage',0.20,'Portfolio rebalancing modestly improved basket efficiency.'],
                    [4,'valuation_adjustment','negative','percentage',-1.15,'Risk adjustment applied after short-term volatility expansion.'],
                ],
            ],
            [
                'slug'=>'digital-asset-stability-basket','symbol'=>'DASB','name'=>'Digital Asset Stability Basket',
                'category'=>'cryptocurrency','description'=>'A higher-risk internally governed digital-asset basket with a diversified reserve component.',
                'risk_level'=>'very_high','opening'=>12.00,'drift'=>0.0010,'wave'=>0.0160,'supply'=>200000,'minimum'=>50,'maximum'=>10000,'fee'=>1.60,'featured'=>false,
                'assets'=>[
                    ['crypto_basket','Core Digital Asset Basket',510000,548000,70,'Diversified major digital-asset allocation.'],
                    ['stable_reserve','Stable Reserve',165000,165000,22,'Stable-value reserve used to control basket volatility.'],
                    ['cash_reserve','Operations Reserve',60000,60500,8,'Liquidity for fees and rebalancing.'],
                ],
                'events'=>[
                    [33,'capital_appreciation','positive','percentage',3.10,'Broad digital-asset appreciation increased basket valuation.'],
                    [18,'risk_adjustment','negative','percentage',-2.20,'Risk reserve adjustment during a volatility spike.'],
                    [7,'rebalancing','positive','percentage',0.90,'Reserve rebalance improved downside protection.'],
                ],
            ],
        ];

        foreach ($definitions as $definition) {
            $instrument = PrivateInvestmentInstrument::query()->updateOrCreate(
                ['slug'=>$definition['slug']],
                [
                    'symbol'=>$definition['symbol'],
                    'name'=>$definition['name'],
                    'category'=>$definition['category'],
                    'description'=>$definition['description'],
                    'risk_level'=>$definition['risk_level'],
                    'status'=>'active',
                    'currency'=>'USD',
                    'opening_price'=>$definition['opening'],
                    'current_price'=>$definition['opening'],
                    'previous_price'=>$definition['opening'],
                    'unit_supply'=>$definition['supply'],
                    'available_units'=>$definition['supply'],
                    'minimum_investment'=>$definition['minimum'],
                    'maximum_investment'=>$definition['maximum'],
                    'management_fee_percent'=>$definition['fee'],
                    'lock_period_days'=>$definition['category']==='real_estate' ? 30 : 7,
                    'is_featured'=>$definition['featured'],
                    'is_visible'=>true,
                ]
            );

            foreach ($definition['assets'] as $asset) {
                PrivateInvestmentAsset::query()->updateOrCreate(
                    ['instrument_id'=>$instrument->id,'name'=>$asset[1]],
                    [
                        'asset_type'=>$asset[0],
                        'description'=>$asset[5],
                        'acquisition_value'=>$asset[2],
                        'current_valuation'=>$asset[3],
                        'ownership_percentage'=>$asset[4],
                        'status'=>'active',
                        'acquired_at'=>now()->subMonths(14)->toDateString(),
                        'effective_at'=>now()->subMonths(14),
                    ]
                );
            }

            // Deterministic 45-day OHLC history so charts are useful immediately.
            PrivateInvestmentPrice::query()->where('instrument_id',$instrument->id)->delete();

            $price = (float) $definition['opening'];
            $previousClose = $price;
            $days = 45;

            for ($i=$days-1; $i>=0; $i--) {
                $recordedAt = now()->subDays($i)->setTime(16,0,0);
                $index = ($days-1)-$i;
                $eventBump = 0.0;

                foreach ($definition['events'] as $eventDef) {
                    if ($i === $eventDef[0]) {
                        $eventBump += ((float)$eventDef[4]) / 100;
                    }
                }

                $wave = sin(($index + strlen($definition['symbol'])) * 0.73) * (float)$definition['wave'];
                $micro = cos(($index + 3) * 0.41) * ((float)$definition['wave'] * 0.35);
                $movement = (float)$definition['drift'] + $wave + $micro + $eventBump;

                $open = $previousClose;
                $close = max(0.01, $open * (1 + $movement));
                $wick = max($open,$close) * (0.0015 + (abs(sin($index*0.91))*0.0025));
                $high = max($open,$close) + $wick;
                $low = max(0.01, min($open,$close) - $wick);
                $change = $close - $open;
                $percent = $open > 0 ? ($change/$open)*100 : 0;

                PrivateInvestmentPrice::query()->create([
                    'instrument_id'=>$instrument->id,
                    'timeframe'=>'1d',
                    'open'=>round($open,6),
                    'high'=>round($high,6),
                    'low'=>round($low,6),
                    'close'=>round($close,6),
                    'change_amount'=>round($change,6),
                    'change_percent'=>round($percent,6),
                    'source'=>'demo_valuation_seed',
                    'recorded_at'=>$recordedAt,
                ]);

                $previousClose = $close;
            }

            PrivateInvestmentEvent::query()->where('instrument_id',$instrument->id)->delete();

            foreach ($definition['events'] as $eventDef) {
                $effectiveAt = now()->subDays($eventDef[0])->setTime(12,0,0);
                $nearest = PrivateInvestmentPrice::query()
                    ->where('instrument_id',$instrument->id)
                    ->whereDate('recorded_at',$effectiveAt->toDateString())
                    ->first();

                PrivateInvestmentEvent::query()->create([
                    'instrument_id'=>$instrument->id,
                    'event_type'=>$eventDef[1],
                    'direction'=>$eventDef[2],
                    'adjustment_type'=>$eventDef[3],
                    'adjustment_value'=>$eventDef[4],
                    'previous_price'=>$nearest?->open,
                    'new_price'=>$nearest?->close,
                    'reason'=>$eventDef[5],
                    'approval_state'=>'approved',
                    'metadata'=>['seeded_demo'=>true],
                    'effective_at'=>$effectiveAt,
                ]);
            }

            $latest = PrivateInvestmentPrice::query()
                ->where('instrument_id',$instrument->id)
                ->latest('recorded_at')
                ->first();
            $previous = PrivateInvestmentPrice::query()
                ->where('instrument_id',$instrument->id)
                ->latest('recorded_at')
                ->skip(1)
                ->first();

            if ($latest) {
                $instrument->update([
                    'current_price'=>$latest->close,
                    'previous_price'=>$previous?->close ?? $latest->open,
                    'last_valued_at'=>$latest->recorded_at,
                ]);
            }
        }
    }
}
