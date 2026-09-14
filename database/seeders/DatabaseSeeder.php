<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's reference/catalogue data and live-test fixtures.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            SettingsSeeder::class,
            PaymentMethodSeeder::class,
            CarSeeder::class,
            EmailTemplateSeeder::class,
            InvestmentCategorySeeder::class,
            InvestmentPlanSeeder::class,
            StockSeeder::class,
            CurrencyRatesSeeder::class,
            LiveTestDataSeeder::class,
        ]);
    }
}
