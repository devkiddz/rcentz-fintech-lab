<?php

namespace App\Console\Commands;

use Database\Seeders\TradingIntelligenceDemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedTradingIntelligencePreview extends Command
{
    protected $signature = 'trading-intelligence:seed-preview';
    protected $description = 'Seed and verify Copy Trading and AI Trading Bot preview data';

    public function handle(TradingIntelligenceDemoSeeder $seeder): int
    {
        $this->components->info('Seeding Trading Intelligence preview data...');
        $seeder->run();

        $stats = [
            ['Approved providers', DB::table('copy_trader_profiles')->whereNotNull('approved_at')->count()],
            ['Published strategies', DB::table('copy_strategies')->where('is_public', true)->count()],
            ['Provider applications', DB::table('strategy_provider_applications')->count()],
            ['Bot products', DB::table('bot_products')->where('is_active', true)->count()],
            ['Bot subscriptions', DB::table('bot_subscriptions')->count()],
        ];

        $this->table(['Preview area', 'Rows'], $stats);

        if ((int) $stats[1][1] === 0 || (int) $stats[3][1] === 0) {
            $this->components->error('Preview rows are still empty. Send this terminal output before continuing.');
            return self::FAILURE;
        }

        $this->components->info('Preview data is ready.');
        return self::SUCCESS;
    }
}
