<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Stock;
use App\Services\StockDataService;
use App\Services\AlphaVantageService;
use Illuminate\Support\Facades\Log;

class StockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Stock Seeder...');

        $stocks = [
            // Technology Sector
            [
                'symbol' => 'AAPL',
                'company_name' => 'Apple Inc.',
                'sector' => 'Technology',
                'industry' => 'Consumer Electronics',
                'current_price' => 213.25,
                'previous_close' => 202.92,
                'change_amount' => 10.33,
                'change_percentage' => 5.09,
                'volume' => 106464389,
                'high' => 215.38,
                'low' => 205.59,
                'open' => 205.63,
                'market_cap' => 3109506.81,
                'pe_ratio' => 28.5,
                'dividend_yield' => 0.5,
                'fifty_two_week_high' => 220.00,
                'fifty_two_week_low' => 150.00,
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'symbol' => 'MSFT',
                'company_name' => 'Microsoft Corporation',
                'sector' => 'Technology',
                'industry' => 'Software',
                'current_price' => 524.94,
                'previous_close' => 528.00,
                'change_amount' => -3.06,
                'change_percentage' => -0.58,
                'volume' => 45678912,
                'high' => 530.00,
                'low' => 522.00,
                'open' => 525.00,
                'market_cap' => 3900000.00,
                'pe_ratio' => 35.2,
                'dividend_yield' => 0.8,
                'fifty_two_week_high' => 540.00,
                'fifty_two_week_low' => 400.00,
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'symbol' => 'GOOGL',
                'company_name' => 'Alphabet Inc.',
                'sector' => 'Technology',
                'industry' => 'Internet Services',
                'current_price' => 196.09,
                'previous_close' => 194.67,
                'change_amount' => 1.42,
                'change_percentage' => 0.73,
                'volume' => 23456789,
                'high' => 197.00,
                'low' => 193.67,
                'open' => 194.50,
                'market_cap' => 2500000.00,
                'pe_ratio' => 25.8,
                'dividend_yield' => 0.0,
                'fifty_two_week_high' => 200.00,
                'fifty_two_week_low' => 150.00,
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'symbol' => 'TSLA',
                'company_name' => 'Tesla Inc.',
                'sector' => 'Automobiles',
                'industry' => 'Electric Vehicles',
                'current_price' => 319.91,
                'previous_close' => 308.72,
                'change_amount' => 11.19,
                'change_percentage' => 3.62,
                'volume' => 78193559,
                'high' => 320.47,
                'low' => 306.93,
                'open' => 307.89,
                'market_cap' => 1010124.18,
                'pe_ratio' => 85.3,
                'dividend_yield' => 0.0,
                'fifty_two_week_high' => 350.00,
                'fifty_two_week_low' => 250.00,
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'symbol' => 'NVDA',
                'company_name' => 'NVIDIA Corporation',
                'sector' => 'Technology',
                'industry' => 'Semiconductors',
                'current_price' => 850.00,
                'previous_close' => 840.00,
                'change_amount' => 10.00,
                'change_percentage' => 1.19,
                'volume' => 34567890,
                'high' => 855.00,
                'low' => 835.00,
                'open' => 842.00,
                'market_cap' => 2100000.00,
                'pe_ratio' => 45.2,
                'dividend_yield' => 0.1,
                'fifty_two_week_high' => 900.00,
                'fifty_two_week_low' => 600.00,
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'symbol' => 'AMZN',
                'company_name' => 'Amazon.com Inc.',
                'sector' => 'Consumer Discretionary',
                'industry' => 'E-commerce',
                'current_price' => 180.00,
                'previous_close' => 178.00,
                'change_amount' => 2.00,
                'change_percentage' => 1.12,
                'volume' => 45678901,
                'high' => 182.00,
                'low' => 177.00,
                'open' => 179.00,
                'market_cap' => 1800000.00,
                'pe_ratio' => 60.5,
                'dividend_yield' => 0.0,
                'fifty_two_week_high' => 190.00,
                'fifty_two_week_low' => 140.00,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'symbol' => 'META',
                'company_name' => 'Meta Platforms Inc.',
                'sector' => 'Technology',
                'industry' => 'Social Media',
                'current_price' => 450.00,
                'previous_close' => 445.00,
                'change_amount' => 5.00,
                'change_percentage' => 1.12,
                'volume' => 23456789,
                'high' => 452.00,
                'low' => 443.00,
                'open' => 446.00,
                'market_cap' => 1150000.00,
                'pe_ratio' => 22.5,
                'dividend_yield' => 0.0,
                'fifty_two_week_high' => 480.00,
                'fifty_two_week_low' => 350.00,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'symbol' => 'NFLX',
                'company_name' => 'Netflix Inc.',
                'sector' => 'Communication Services',
                'industry' => 'Streaming',
                'current_price' => 650.00,
                'previous_close' => 645.00,
                'change_amount' => 5.00,
                'change_percentage' => 0.78,
                'volume' => 12345678,
                'high' => 652.00,
                'low' => 643.00,
                'open' => 646.00,
                'market_cap' => 280000.00,
                'pe_ratio' => 35.8,
                'dividend_yield' => 0.0,
                'fifty_two_week_high' => 680.00,
                'fifty_two_week_low' => 500.00,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'symbol' => 'JPM',
                'company_name' => 'JPMorgan Chase & Co.',
                'sector' => 'Financial Services',
                'industry' => 'Banking',
                'current_price' => 180.00,
                'previous_close' => 178.00,
                'change_amount' => 2.00,
                'change_percentage' => 1.12,
                'volume' => 9876543,
                'high' => 181.00,
                'low' => 177.00,
                'open' => 179.00,
                'market_cap' => 520000.00,
                'pe_ratio' => 12.5,
                'dividend_yield' => 2.8,
                'fifty_two_week_high' => 185.00,
                'fifty_two_week_low' => 150.00,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'symbol' => 'JNJ',
                'company_name' => 'Johnson & Johnson',
                'sector' => 'Healthcare',
                'industry' => 'Pharmaceuticals',
                'current_price' => 165.00,
                'previous_close' => 164.00,
                'change_amount' => 1.00,
                'change_percentage' => 0.61,
                'volume' => 8765432,
                'high' => 166.00,
                'low' => 163.00,
                'open' => 164.50,
                'market_cap' => 400000.00,
                'pe_ratio' => 15.2,
                'dividend_yield' => 3.2,
                'fifty_two_week_high' => 170.00,
                'fifty_two_week_low' => 140.00,
                'is_active' => true,
                'is_featured' => false,
            ],
        ];

        $createdCount = 0;
        $updatedCount = 0;

        foreach ($stocks as $stockData) {
            $stockData['last_updated'] = now();
            $stockData['volume_updated_at'] = now();

            $stock = Stock::updateOrCreate(
                ['symbol' => $stockData['symbol']],
                $stockData
            );

            if ($stock->wasRecentlyCreated) {
                $createdCount++;
                $this->command->info("Created stock: {$stock->symbol} - {$stock->company_name}");
            } else {
                $updatedCount++;
                $this->command->info("Updated stock: {$stock->symbol} - {$stock->company_name}");
            }
        }

        $this->command->info("Stock seeding completed!");
        $this->command->info("Created: {$createdCount} stocks");
        $this->command->info("Updated: {$updatedCount} stocks");

        // External API refresh is deliberately opt-in so unattended cPanel
        // seeding never pauses for terminal input or consumes API quota.
        if ((bool) env('SEED_FETCH_LIVE_STOCK_DATA', false)) {
            $this->fetchRealTimeData();
        }
    }

    /**
     * Fetch real-time data for featured stocks
     */
    private function fetchRealTimeData(): void
    {
        $this->command->info('Fetching real-time data for featured stocks...');

        try {
            $stockDataService = app(StockDataService::class);
            $alphaVantageService = app(AlphaVantageService::class);

            $featuredStocks = Stock::where('is_featured', true)->get();

            foreach ($featuredStocks as $stock) {
                try {
                    $this->command->info("Fetching data for {$stock->symbol}...");
                    
                    // Update stock data
                    $stockDataService->updateStockData($stock);
                    
                    $this->command->info("✅ Updated {$stock->symbol}");
                    
                    // Small delay to respect rate limits
                    usleep(200000); // 0.2 seconds
                    
                } catch (\Exception $e) {
                    $this->command->error("❌ Failed to update {$stock->symbol}: {$e->getMessage()}");
                }
            }

            $this->command->info('Real-time data fetching completed!');

        } catch (\Exception $e) {
            $this->command->error("Failed to fetch real-time data: {$e->getMessage()}");
        }
    }
}
