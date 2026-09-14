<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvestmentCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Growth',
                'description' => 'High-growth investment opportunities focused on capital appreciation',
                'color' => '#10B981',
                'icon' => 'trending-up',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Income',
                'description' => 'Stable income-generating investments with regular dividends',
                'color' => '#3B82F6',
                'icon' => 'dollar-sign',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Balanced',
                'description' => 'Balanced portfolio with moderate growth and income',
                'color' => '#8B5CF6',
                'icon' => 'pie-chart',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Conservative',
                'description' => 'Low-risk investments focused on capital preservation',
                'color' => '#6B7280',
                'icon' => 'shield',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Tesla-Focused',
                'description' => 'Investment opportunities centered around Tesla and related technologies',
                'color' => '#EF4444',
                'icon' => 'zap',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'ESG',
                'description' => 'Environmental, Social, and Governance focused investments',
                'color' => '#059669',
                'icon' => 'leaf',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($categories as $category) {
            DB::table('investment_categories')->updateOrInsert(
                ['name' => $category['name']],
                $category
            );
        }
    }
} 