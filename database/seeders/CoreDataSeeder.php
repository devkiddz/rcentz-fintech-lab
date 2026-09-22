<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CoreDataSeeder extends Seeder
{
    /**
     * Seed the catalogue/reference data required for a usable installation.
     * Demo users and demo activity are intentionally excluded here.
     */
    public function run(): void
    {
        $this->call([
            SettingsSeeder::class,
            PaymentMethodSeeder::class,
            CarSeeder::class,
            EmailTemplateSeeder::class,
            InvestmentCategorySeeder::class,
            InvestmentPlanSeeder::class,
            StockSeeder::class,
            CurrencyRatesSeeder::class,
            CommodityInstrumentSeeder::class,
            GoldHedgeInvestmentSeeder::class,
        ]);
    }
}
