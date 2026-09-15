<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class TradingIntelligenceDemoSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'strategy_provider_applications',
            'copy_trader_profiles',
            'copy_strategies',
            'copy_relationships',
            'bot_products',
            'bot_subscriptions',
            'trading_bots',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException(
                    "Trading Intelligence tables are missing. Run artisan migrate before this seeder."
                );
            }
        }

        DB::transaction(function (): void {
            $now = now();
            $adminId = DB::table('users')->where('is_admin', true)->value('id');

            if (! $adminId) {
                throw new RuntimeException('An admin user is required before seeding Trading Intelligence demo data.');
            }

            $password = config('bootstrap.live_test.user_password') ?: 'TestUser@2026!';

            $providers = [
                [
                    'name' => 'Ethan Cole',
                    'email' => 'ethan.cole@tesladrives.test',
                    'country' => 'United States',
                    'currency' => 'USD',
                    'display_name' => 'Steady Compound',
                    'bio' => 'A diversified large-cap strategy focused on controlled position sizing and measured accumulation.',
                    'risk_level' => 'low',
                ],
                [
                    'name' => 'Maya Chen',
                    'email' => 'maya.chen@tesladrives.test',
                    'country' => 'Singapore',
                    'currency' => 'USD',
                    'display_name' => 'Growth Rotation',
                    'bio' => 'A growth-oriented strategy that rotates exposure among liquid technology leaders while keeping strict allocation limits.',
                    'risk_level' => 'medium',
                ],
            ];

            foreach ($providers as &$provider) {
                DB::table('users')->updateOrInsert(
                    ['email' => $provider['email']],
                    [
                        'name' => $provider['name'],
                        'password' => Hash::make($password),
                        'email_verified_at' => $now,
                        'is_admin' => false,
                        'country' => $provider['country'],
                        'currency' => $provider['currency'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                $provider['id'] = DB::table('users')->where('email', $provider['email'])->value('id');

                DB::table('wallets')->updateOrInsert(
                    ['user_id' => $provider['id']],
                    [
                        'balance' => 25000.00,
                        'reserved_balance' => 0.00,
                        'currency' => 'USD',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                // Keep provider applications visible in admin as an approved learning case.
                $application = DB::table('strategy_provider_applications')
                    ->where('user_id', $provider['id'])
                    ->where('status', 'approved')
                    ->first();

                if (! $application) {
                    DB::table('strategy_provider_applications')->insert([
                        'user_id' => $provider['id'],
                        'display_name' => $provider['display_name'],
                        'experience' => 'Synthetic preview provider used to demonstrate the provider-review workflow.',
                        'strategy_summary' => $provider['bio'],
                        'risk_level' => $provider['risk_level'],
                        'status' => 'approved',
                        'admin_notes' => 'Approved fixture for Trading Intelligence product preview.',
                        'reviewed_by' => $adminId,
                        'reviewed_at' => $now->copy()->subDays(10),
                        'created_at' => $now->copy()->subDays(12),
                        'updated_at' => $now,
                    ]);
                }

                DB::table('copy_trader_profiles')->updateOrInsert(
                    ['user_id' => $provider['id']],
                    [
                        'strategy_name' => $provider['display_name'],
                        'bio' => $provider['bio'],
                        'risk_level' => $provider['risk_level'],
                        'is_public' => true,
                        'is_accepting_copiers' => true,
                        'approved_at' => $now->copy()->subDays(10),
                        'approved_by' => $adminId,
                        'created_at' => $now->copy()->subDays(10),
                        'updated_at' => $now,
                    ]
                );

                $provider['profile_id'] = DB::table('copy_trader_profiles')
                    ->where('user_id', $provider['id'])
                    ->value('id');
            }
            unset($provider);

            $strategyFixtures = [
                [
                    'profile_id' => $providers[0]['profile_id'],
                    'name' => 'Steady Compound',
                    'description' => 'Large-cap accumulation with low turnover and capped position exposure.',
                    'risk_level' => 'low',
                    'minimum_allocation' => 250,
                    'recommended_allocation' => 1500,
                ],
                [
                    'profile_id' => $providers[0]['profile_id'],
                    'name' => 'Dividend & Quality',
                    'description' => 'Conservative quality-bias strategy designed as a slower allocation learning case.',
                    'risk_level' => 'low',
                    'minimum_allocation' => 500,
                    'recommended_allocation' => 2500,
                ],
                [
                    'profile_id' => $providers[1]['profile_id'],
                    'name' => 'Growth Rotation',
                    'description' => 'Medium-risk allocation across actively traded technology leaders.',
                    'risk_level' => 'medium',
                    'minimum_allocation' => 300,
                    'recommended_allocation' => 2000,
                ],
                [
                    'profile_id' => $providers[1]['profile_id'],
                    'name' => 'Momentum Edge',
                    'description' => 'Higher-volatility strategy used to demonstrate stricter copier limits and risk labelling.',
                    'risk_level' => 'high',
                    'minimum_allocation' => 500,
                    'recommended_allocation' => 3000,
                ],
            ];

            foreach ($strategyFixtures as $fixture) {
                DB::table('copy_strategies')->updateOrInsert(
                    ['copy_trader_profile_id' => $fixture['profile_id'], 'name' => $fixture['name']],
                    array_merge($fixture, [
                        'copy_trader_profile_id' => $fixture['profile_id'],
                        'is_public' => true,
                        'is_active' => true,
                        'created_at' => $now->copy()->subDays(8),
                        'updated_at' => $now,
                    ])
                );
            }

            // Give Amara one existing copied strategy so My Copied Strategies is not empty.
            $amaraId = DB::table('users')->where('email', 'amara.okafor@tesladrives.test')->value('id');
            $steadyId = DB::table('copy_strategies')
                ->where('copy_trader_profile_id', $providers[0]['profile_id'])
                ->where('name', 'Steady Compound')
                ->value('id');

            if ($amaraId && $steadyId) {
                DB::table('copy_relationships')->updateOrInsert(
                    ['follower_id' => $amaraId, 'provider_id' => $providers[0]['id']],
                    [
                        'copy_strategy_id' => $steadyId,
                        'allocation_limit' => 1500.00,
                        'used_amount' => 0.00,
                        'max_trade_amount' => 200.00,
                        'copy_ratio_percent' => 75.00,
                        'status' => 'active',
                        'started_at' => $now->copy()->subDays(3),
                        'stopped_at' => null,
                        'created_at' => $now->copy()->subDays(3),
                        'updated_at' => $now,
                    ]
                );
            }

            // Add one pending application to demonstrate the admin decision queue.
            $sofiaId = DB::table('users')->where('email', 'sofia.martinez@tesladrives.test')->value('id');
            if ($sofiaId && ! DB::table('strategy_provider_applications')->where('user_id', $sofiaId)->where('status', 'pending')->exists()) {
                DB::table('strategy_provider_applications')->insert([
                    'user_id' => $sofiaId,
                    'display_name' => 'European Value Watch',
                    'experience' => 'Synthetic pending application for the admin-review learning case.',
                    'strategy_summary' => 'Value-oriented stock selection with conservative trade sizing.',
                    'risk_level' => 'medium',
                    'status' => 'pending',
                    'admin_notes' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'created_at' => $now->copy()->subDay(),
                    'updated_at' => $now,
                ]);
            }

            $botFixtures = [
                [
                    'symbol' => 'AAPL',
                    'name' => 'AAPL Smart DCA',
                    'slug' => 'aapl-smart-dca',
                    'description' => 'Rules-based AAPL accumulation bot for learning recurring automated execution with conservative limits.',
                    'strategy' => 'dca',
                    'action' => 'buy',
                    'risk_level' => 'low',
                    'price' => 0.00,
                    'billing_period' => 'one_time',
                    'minimum_balance' => 100.00,
                    'max_user_allocation' => 1000.00,
                    'default_interval_minutes' => 1440,
                    'default_max_daily_trades' => 1,
                    'default_trade_amount' => 50.00,
                    'default_trigger_price' => null,
                    'allow_user_trade_amount' => true,
                    'allow_user_trigger_price' => false,
                ],
                [
                    'symbol' => 'MSFT',
                    'name' => 'MSFT Core Accumulator',
                    'slug' => 'msft-core-accumulator',
                    'description' => 'Low-risk recurring accumulation template with a tighter daily execution cap.',
                    'strategy' => 'dca',
                    'action' => 'buy',
                    'risk_level' => 'low',
                    'price' => 35.00,
                    'billing_period' => 'one_time',
                    'minimum_balance' => 250.00,
                    'max_user_allocation' => 2500.00,
                    'default_interval_minutes' => 1440,
                    'default_max_daily_trades' => 1,
                    'default_trade_amount' => 75.00,
                    'default_trigger_price' => null,
                    'allow_user_trade_amount' => true,
                    'allow_user_trigger_price' => false,
                ],
                [
                    'symbol' => 'NVDA',
                    'name' => 'NVDA Dip Entry',
                    'slug' => 'nvda-dip-entry',
                    'description' => 'Medium-risk price-below automation for studying trigger-based bot execution.',
                    'strategy' => 'price_below',
                    'action' => 'buy',
                    'risk_level' => 'medium',
                    'price' => 39.00,
                    'billing_period' => 'monthly',
                    'minimum_balance' => 500.00,
                    'max_user_allocation' => 3000.00,
                    'default_interval_minutes' => 60,
                    'default_max_daily_trades' => 2,
                    'default_trade_amount' => 100.00,
                    'default_trigger_price' => 825.00,
                    'allow_user_trade_amount' => true,
                    'allow_user_trigger_price' => true,
                ],
                [
                    'symbol' => 'TSLA',
                    'name' => 'TSLA Volatility Scout',
                    'slug' => 'tsla-volatility-scout',
                    'description' => 'Higher-risk trigger bot with strict allocation and daily-trade controls.',
                    'strategy' => 'price_below',
                    'action' => 'buy',
                    'risk_level' => 'high',
                    'price' => 59.00,
                    'billing_period' => 'monthly',
                    'minimum_balance' => 750.00,
                    'max_user_allocation' => 3500.00,
                    'default_interval_minutes' => 30,
                    'default_max_daily_trades' => 2,
                    'default_trade_amount' => 125.00,
                    'default_trigger_price' => 300.00,
                    'allow_user_trade_amount' => true,
                    'allow_user_trigger_price' => true,
                ],
                [
                    'symbol' => 'GOOGL',
                    'name' => 'GOOGL Breakout Watch',
                    'slug' => 'googl-breakout-watch',
                    'description' => 'Medium-risk price-above automation for demonstrating breakout-style rules.',
                    'strategy' => 'price_above',
                    'action' => 'buy',
                    'risk_level' => 'medium',
                    'price' => 79.00,
                    'billing_period' => 'quarterly',
                    'minimum_balance' => 500.00,
                    'max_user_allocation' => 4000.00,
                    'default_interval_minutes' => 60,
                    'default_max_daily_trades' => 2,
                    'default_trade_amount' => 100.00,
                    'default_trigger_price' => 205.00,
                    'allow_user_trade_amount' => true,
                    'allow_user_trigger_price' => true,
                ],
            ];

            foreach ($botFixtures as $bot) {
                $stockId = DB::table('stocks')->where('symbol', $bot['symbol'])->value('id');
                if (! $stockId) {
                    continue;
                }

                DB::table('bot_products')->updateOrInsert(
                    ['slug' => $bot['slug']],
                    [
                        'created_by' => $adminId,
                        'stock_id' => $stockId,
                        'name' => $bot['name'],
                        'description' => $bot['description'],
                        'strategy' => $bot['strategy'],
                        'action' => $bot['action'],
                        'risk_level' => $bot['risk_level'],
                        'price' => $bot['price'],
                        'billing_period' => $bot['billing_period'],
                        'minimum_balance' => $bot['minimum_balance'],
                        'max_user_allocation' => $bot['max_user_allocation'],
                        'default_interval_minutes' => $bot['default_interval_minutes'],
                        'default_max_daily_trades' => $bot['default_max_daily_trades'],
                        'default_trade_amount' => $bot['default_trade_amount'],
                        'default_trigger_price' => $bot['default_trigger_price'],
                        'allow_user_trade_amount' => $bot['allow_user_trade_amount'],
                        'allow_user_trigger_price' => $bot['allow_user_trigger_price'],
                        'is_active' => true,
                        'created_at' => $now->copy()->subDays(5),
                        'updated_at' => $now,
                    ]
                );
            }

            // Give Amara free admin-curated starter access so My Bots has a safe preview.
            if ($amaraId) {
                $starter = DB::table('bot_products')->where('slug', 'aapl-smart-dca')->first();
                if ($starter) {
                    $botId = DB::table('trading_bots')
                        ->where('user_id', $amaraId)
                        ->where('name', 'AAPL Smart DCA')
                        ->value('id');

                    if (! $botId) {
                        $botId = DB::table('trading_bots')->insertGetId([
                            'user_id' => $amaraId,
                            'stock_id' => $starter->stock_id,
                            'name' => 'AAPL Smart DCA',
                            'strategy' => 'dca',
                            'action' => 'buy',
                            'amount_per_trade' => 50.00,
                            'quantity_per_trade' => null,
                            'trigger_price' => null,
                            'interval_minutes' => 1440,
                            'max_daily_trades' => 1,
                            'max_total_spend' => 1000.00,
                            'spent_total' => 0.00,
                            'status' => 'paused',
                            'last_run_at' => null,
                            'next_run_at' => $now,
                            'created_at' => $now->copy()->subDays(2),
                            'updated_at' => $now,
                        ]);
                    }

                    DB::table('bot_subscriptions')->updateOrInsert(
                        ['user_id' => $amaraId, 'bot_product_id' => $starter->id],
                        [
                            'trading_bot_id' => $botId,
                            'price_paid' => 0.00,
                            'status' => 'paused',
                            'starts_at' => $now->copy()->subDays(2),
                            'ends_at' => null,
                            'cancelled_at' => null,
                            'created_at' => $now->copy()->subDays(2),
                            'updated_at' => $now,
                        ]
                    );
                }
            }
        });
    }
}
