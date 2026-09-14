<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CurrencyRate;

class CurrencyRatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rates = [
            ['currency' => 'USD', 'rate' => 1.0],
            ['currency' => 'EUR', 'rate' => 0.92],
            ['currency' => 'GBP', 'rate' => 0.79],
            ['currency' => 'JPY', 'rate' => 149.50],
            ['currency' => 'AUD', 'rate' => 1.53],
            ['currency' => 'CAD', 'rate' => 1.36],
            ['currency' => 'CHF', 'rate' => 0.88],
            ['currency' => 'CNY', 'rate' => 7.24],
            ['currency' => 'INR', 'rate' => 83.12],
            ['currency' => 'KRW', 'rate' => 1320.50],
            ['currency' => 'MXN', 'rate' => 17.15],
            ['currency' => 'BRL', 'rate' => 4.98],
            ['currency' => 'ZAR', 'rate' => 18.65],
            ['currency' => 'RUB', 'rate' => 92.50],
            ['currency' => 'SEK', 'rate' => 10.87],
            ['currency' => 'NOK', 'rate' => 10.92],
            ['currency' => 'DKK', 'rate' => 6.89],
            ['currency' => 'SGD', 'rate' => 1.34],
            ['currency' => 'HKD', 'rate' => 7.83],
            ['currency' => 'NZD', 'rate' => 1.67],
            ['currency' => 'TRY', 'rate' => 32.15],
            ['currency' => 'PLN', 'rate' => 4.02],
            ['currency' => 'THB', 'rate' => 35.50],
            ['currency' => 'IDR', 'rate' => 15625.0],
            ['currency' => 'MYR', 'rate' => 4.68],
            ['currency' => 'PHP', 'rate' => 56.25],
            ['currency' => 'CZK', 'rate' => 23.15],
            ['currency' => 'ILS', 'rate' => 3.75],
            ['currency' => 'CLP', 'rate' => 920.0],
            ['currency' => 'AED', 'rate' => 3.67],
        ];

        $now = now();

        foreach ($rates as $rate) {
            CurrencyRate::updateOrCreate(
                ['currency' => $rate['currency']],
                [
                    'rate' => $rate['rate'],
                    'last_updated' => $now,
                ]
            );
        }

        $this->command->info('Currency rates seeded successfully!');
    }
}
