<?php

namespace Database\Seeders;

use App\Models\PrivateInvestmentAsset;
use App\Models\PrivateInvestmentEvent;
use App\Models\PrivateInvestmentInstrument;
use App\Models\PrivateInvestmentPrice;
use Illuminate\Database\Seeder;

class PrivateInvestmentTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $instrument = PrivateInvestmentInstrument::query()->updateOrCreate(
            ['slug'=>'private-infrastructure-income-note'],
            [
                'symbol'=>'PIIN',
                'name'=>'Private Infrastructure Income Note',
                'category'=>'bonds',
                'description'=>'A demonstration fixed-income instrument backed by a diversified private infrastructure receivables pool and reserve account.',
                'risk_level'=>'low',
                'status'=>'active',
                'currency'=>'USD',
                'opening_price'=>100.000000,
                'current_price'=>100.000000,
                'previous_price'=>100.000000,
                'unit_supply'=>50000,
                'available_units'=>50000,
                'minimum_investment'=>500,
                'maximum_investment'=>50000,
                'management_fee_percent'=>0.6500,
                'lock_period_days'=>90,
                'is_featured'=>true,
                'is_visible'=>true,
            ]
        );

        PrivateInvestmentAsset::query()->updateOrCreate(
            ['instrument_id'=>$instrument->id,'name'=>'Infrastructure Receivables Pool'],
            ['asset_type'=>'fixed_income_pool','description'=>'Diversified contracted infrastructure receivables used for the demo fixed-income instrument.','acquisition_value'=>3200000,'current_valuation'=>3275000,'ownership_percentage'=>85,'status'=>'active','acquired_at'=>now()->subMonths(16)->toDateString(),'effective_at'=>now()->subMonths(16)]
        );
        PrivateInvestmentAsset::query()->updateOrCreate(
            ['instrument_id'=>$instrument->id,'name'=>'Liquidity & Payment Reserve'],
            ['asset_type'=>'reserve','description'=>'Liquidity reserve supporting scheduled income and maturity obligations.','acquisition_value'=>560000,'current_valuation'=>568000,'ownership_percentage'=>15,'status'=>'active','acquired_at'=>now()->subMonths(16)->toDateString(),'effective_at'=>now()->subMonths(16)]
        );

        if (! PrivateInvestmentPrice::query()->where('instrument_id',$instrument->id)->exists()) {
            $previous = 100.0;
            for($i=44;$i>=0;$i--){
                $index=44-$i;
                $open=$previous;
                $movement=0.00022 + sin(($index+2)*0.55)*0.00035;
                $close=max(0.01,$open*(1+$movement));
                $wick=max($open,$close)*0.00025;
                PrivateInvestmentPrice::query()->create([
                    'instrument_id'=>$instrument->id,'timeframe'=>'1d',
                    'open'=>round($open,6),'high'=>round(max($open,$close)+$wick,6),
                    'low'=>round(min($open,$close)-$wick,6),'close'=>round($close,6),
                    'change_amount'=>round($close-$open,6),
                    'change_percent'=>round((($close-$open)/$open)*100,6),
                    'source'=>'demo_valuation_seed','recorded_at'=>now()->subDays($i)->setTime(16,0,0),
                ]);
                $previous=$close;
            }
        }

        PrivateInvestmentEvent::query()->firstOrCreate(
            ['instrument_id'=>$instrument->id,'event_type'=>'scheduled_income_accrual'],
            ['direction'=>'positive','adjustment_type'=>'percentage','adjustment_value'=>0.45,'previous_price'=>100,'new_price'=>$previous,'reason'=>'Scheduled income accrual recognized by the private fixed-income valuation model.','approval_state'=>'approved','metadata'=>['seeded_demo'=>true],'effective_at'=>now()->subDays(12)->setTime(12,0,0)]
        );

        $latest=PrivateInvestmentPrice::query()->where('instrument_id',$instrument->id)->latest('recorded_at')->first();
        $previousRow=PrivateInvestmentPrice::query()->where('instrument_id',$instrument->id)->latest('recorded_at')->skip(1)->first();
        if($latest){
            $instrument->update(['current_price'=>$latest->close,'previous_price'=>$previousRow?->close ?? $latest->open,'last_valued_at'=>$latest->recorded_at]);
        }
    }
}
