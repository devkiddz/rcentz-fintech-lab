<?php

namespace Database\Seeders;

use App\Models\PrivateInvestmentAsset;
use App\Models\PrivateInvestmentEvent;
use App\Models\PrivateInvestmentInstrument;
use App\Models\PrivateInvestmentPrice;
use Illuminate\Database\Seeder;

class PrivateInvestmentStockExpansionSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug'=>'tesla-equity-growth-plan','symbol'=>'TEGP','name'=>'Tesla Equity Growth Plan',
                'description'=>'A privately priced growth instrument referencing Tesla-focused equity exposure without mirroring the public share price.',
                'opening'=>32.00,'drift'=>0.0021,'wave'=>0.0090,'minimum'=>150,'risk'=>'high',
                'assets'=>[['equity_reference','Tesla-focused Equity Allocation',680000,724000,90],['cash_reserve','Risk & Rebalancing Reserve',76000,77500,10]],
            ],
            [
                'slug'=>'apple-quality-equity-plan','symbol'=>'AQEP','name'=>'Apple Quality Equity Plan',
                'description'=>'A managed private instrument built around large-cap quality equity exposure with its own authoritative unit price.',
                'opening'=>27.50,'drift'=>0.0017,'wave'=>0.0065,'minimum'=>150,'risk'=>'medium',
                'assets'=>[['equity_reference','Apple-focused Equity Allocation',720000,758000,88],['cash_reserve','Liquidity Reserve',98000,100500,12]],
            ],
            [
                'slug'=>'nvidia-ai-growth-plan','symbol'=>'NAGP','name'=>'NVIDIA AI Growth Plan',
                'description'=>'A higher-volatility private growth instrument referencing AI infrastructure equity exposure.',
                'opening'=>21.00,'drift'=>0.0025,'wave'=>0.0115,'minimum'=>100,'risk'=>'very_high',
                'assets'=>[['equity_reference','NVIDIA-focused AI Equity Allocation',610000,682000,92],['cash_reserve','Volatility Reserve',53000,54500,8]],
            ],
            [
                'slug'=>'microsoft-cloud-income-growth-plan','symbol'=>'MCGP','name'=>'Microsoft Cloud Growth Plan',
                'description'=>'A private technology instrument balancing cloud-growth equity exposure with a defensive liquidity sleeve.',
                'opening'=>36.00,'drift'=>0.0016,'wave'=>0.0055,'minimum'=>200,'risk'=>'medium',
                'assets'=>[['equity_reference','Microsoft-focused Cloud Equity Allocation',790000,836000,90],['cash_reserve','Defensive Liquidity Sleeve',88000,90000,10]],
            ],
        ];

        foreach($plans as $plan){
            $instrument=PrivateInvestmentInstrument::query()->updateOrCreate(
                ['slug'=>$plan['slug']],
                [
                    'symbol'=>$plan['symbol'],'name'=>$plan['name'],'category'=>'stock_market',
                    'description'=>$plan['description'],'risk_level'=>$plan['risk'],'status'=>'active',
                    'currency'=>'USD','opening_price'=>$plan['opening'],'current_price'=>$plan['opening'],
                    'previous_price'=>$plan['opening'],'unit_supply'=>120000,'available_units'=>120000,
                    'minimum_investment'=>$plan['minimum'],'maximum_investment'=>25000,
                    'management_fee_percent'=>1.1000,'lock_period_days'=>7,'is_featured'=>true,
                    'is_visible'=>true,
                ]
            );

            foreach($plan['assets'] as $asset){
                PrivateInvestmentAsset::query()->updateOrCreate(
                    ['instrument_id'=>$instrument->id,'name'=>$asset[1]],
                    [
                        'asset_type'=>$asset[0],'description'=>'Seeded underlying allocation for the private stock-backed investment product.',
                        'acquisition_value'=>$asset[2],'current_valuation'=>$asset[3],
                        'ownership_percentage'=>$asset[4],'status'=>'active',
                        'acquired_at'=>now()->subMonths(12)->toDateString(),'effective_at'=>now()->subMonths(12),
                    ]
                );
            }

            if(!PrivateInvestmentPrice::query()->where('instrument_id',$instrument->id)->exists()){
                $previous=(float)$plan['opening'];
                for($i=44;$i>=0;$i--){
                    $index=44-$i;
                    $open=$previous;
                    $movement=(float)$plan['drift'] + sin(($index+strlen($plan['symbol']))*0.71)*(float)$plan['wave']
                        + cos(($index+4)*0.37)*((float)$plan['wave']*0.30);
                    $close=max(0.01,$open*(1+$movement));
                    $wick=max($open,$close)*(0.0015+abs(sin($index*0.83))*0.0020);
                    PrivateInvestmentPrice::query()->create([
                        'instrument_id'=>$instrument->id,'timeframe'=>'1d',
                        'open'=>round($open,6),'high'=>round(max($open,$close)+$wick,6),
                        'low'=>round(max(0.01,min($open,$close)-$wick),6),'close'=>round($close,6),
                        'change_amount'=>round($close-$open,6),
                        'change_percent'=>round((($close-$open)/$open)*100,6),
                        'source'=>'demo_valuation_seed','recorded_at'=>now()->subDays($i)->setTime(16,0,0),
                    ]);
                    $previous=$close;
                }
            }

            PrivateInvestmentEvent::query()->firstOrCreate(
                ['instrument_id'=>$instrument->id,'event_type'=>'valuation_review'],
                [
                    'direction'=>'positive','adjustment_type'=>'percentage','adjustment_value'=>1.15,
                    'previous_price'=>$plan['opening'],'new_price'=>$previous,
                    'reason'=>'Scheduled internal valuation review of the managed stock-backed allocation.',
                    'approval_state'=>'approved','metadata'=>['seeded_demo'=>true],
                    'effective_at'=>now()->subDays(10)->setTime(12,0,0),
                ]
            );

            $latest=PrivateInvestmentPrice::query()->where('instrument_id',$instrument->id)->latest('recorded_at')->first();
            $previousRow=PrivateInvestmentPrice::query()->where('instrument_id',$instrument->id)->latest('recorded_at')->skip(1)->first();
            if($latest){
                $instrument->update([
                    'current_price'=>$latest->close,
                    'previous_price'=>$previousRow?->close ?? $latest->open,
                    'last_valued_at'=>$latest->recorded_at,
                ]);
            }
        }
    }
}
