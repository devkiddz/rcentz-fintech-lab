<?php

namespace Database\Seeders;

use App\Models\PrivateInvestmentInstrument;
use Illuminate\Database\Seeder;

class PrivateInvestmentProjectionTermsSeeder extends Seeder
{
    public function run(): void
    {
        $terms = [
            'WRGF' => [360,30,0.85,1.25,0.75,0.50],
            'LIPF' => [180,30,0.75,1.10,0.60,0.40],
            'RTGB' => [180,7,0.28,0.48,0.90,0.65],
            'DASB' => [90,3,0.12,0.24,1.20,0.85],
            'PIIN' => [360,30,0.65,0.95,0.40,0.30],
            'TEGP' => [90,3,0.10,0.20,1.00,0.75],
            'AQEP' => [180,14,0.38,0.62,0.75,0.50],
            'NAGP' => [120,7,0.24,0.44,0.95,0.70],
            'MCGP' => [180,14,0.34,0.56,0.80,0.55],
        ];

        foreach ($terms as $symbol => [$duration,$interval,$min,$max,$subscriptionFee,$redemptionFee]) {
            PrivateInvestmentInstrument::query()->where('symbol',$symbol)->update([
                'duration_days' => $duration,
                'return_interval_days' => $interval,
                'projected_return_min_percent' => $min,
                'projected_return_max_percent' => $max,
                'subscription_fee_percent' => $subscriptionFee,
                'redemption_fee_percent' => $redemptionFee,
            ]);
        }
    }
}
