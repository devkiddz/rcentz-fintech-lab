<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TradingIntelligenceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $required = [
            'users', 'stocks', 'copy_trader_profiles', 'copy_strategies',
            'strategy_provider_applications', 'bot_products'
        ];

        foreach ($required as $table) {
            if (! Schema::hasTable($table)) {
                return;
            }
        }

        $adminId = DB::table('users')->where('is_admin', true)->value('id');
        if (! $adminId) {
            return;
        }

        $now = now();

        // Reuse the existing QA customers as product-preview strategy providers.
        $providers = [
            [
                'email' => 'amara.okafor@tesladrives.test',
                'strategy_name' => 'Balanced Growth',
                'bio' => 'Diversified large-cap growth with measured position sizing and controlled allocation.',
                'risk_level' => 'medium',
                'strategies' => [
                    ['name'=>'Balanced Growth','description'=>'Diversified large-cap growth with controlled trade sizing.','risk'=>'medium','min'=>250,'recommended'=>1500],
                    ['name'=>'Capital Guard','description'=>'A lower-volatility accumulation strategy with conservative allocation limits.','risk'=>'low','min'=>500,'recommended'=>2500],
                ],
            ],
            [
                'email' => 'daniel.brooks@tesladrives.test',
                'strategy_name' => 'Momentum Select',
                'bio' => 'Technology-focused momentum rotation with strict per-trade limits.',
                'risk_level' => 'high',
                'strategies' => [
                    ['name'=>'Momentum Select','description'=>'Technology momentum rotation for users comfortable with higher volatility.','risk'=>'high','min'=>500,'recommended'=>3000],
                    ['name'=>'Core Tech Rotation','description'=>'Medium-risk rotation across liquid technology leaders.','risk'=>'medium','min'=>300,'recommended'=>2000],
                ],
            ],
        ];

        foreach ($providers as $provider) {
            $userId = DB::table('users')->where('email', $provider['email'])->value('id');
            if (! $userId) {
                continue;
            }

            DB::table('strategy_provider_applications')->updateOrInsert(
                ['user_id'=>$userId, 'status'=>'approved'],
                [
                    'display_name'=>$provider['strategy_name'],
                    'experience'=>'Existing approved provider fixture for admin product management and customer preview.',
                    'strategy_summary'=>$provider['bio'],
                    'risk_level'=>$provider['risk_level'],
                    'admin_notes'=>'Approved catalog fixture. Admin may edit published strategies from the management area.',
                    'reviewed_by'=>$adminId,
                    'reviewed_at'=>$now->copy()->subDays(14),
                    'created_at'=>$now->copy()->subDays(16),
                    'updated_at'=>$now,
                ]
            );

            DB::table('copy_trader_profiles')->updateOrInsert(
                ['user_id'=>$userId],
                [
                    'strategy_name'=>$provider['strategy_name'],
                    'bio'=>$provider['bio'],
                    'risk_level'=>$provider['risk_level'],
                    'is_public'=>true,
                    'is_accepting_copiers'=>true,
                    'approved_at'=>$now->copy()->subDays(14),
                    'approved_by'=>$adminId,
                    'created_at'=>$now->copy()->subDays(14),
                    'updated_at'=>$now,
                ]
            );

            $profileId = DB::table('copy_trader_profiles')->where('user_id',$userId)->value('id');

            foreach ($provider['strategies'] as $strategy) {
                DB::table('copy_strategies')->updateOrInsert(
                    ['copy_trader_profile_id'=>$profileId,'name'=>$strategy['name']],
                    [
                        'description'=>$strategy['description'],
                        'risk_level'=>$strategy['risk'],
                        'minimum_allocation'=>$strategy['min'],
                        'recommended_allocation'=>$strategy['recommended'],
                        'is_public'=>true,
                        'is_active'=>true,
                        'created_at'=>$now->copy()->subDays(10),
                        'updated_at'=>$now,
                    ]
                );
            }
        }

        $bots = [
            [
                'symbol'=>'AAPL','name'=>'AAPL Smart DCA','slug'=>'aapl-smart-dca',
                'description'=>'Admin-curated recurring AAPL accumulation bot with conservative execution limits.',
                'strategy'=>'dca','action'=>'buy','risk'=>'low','price'=>25,'billing'=>'one_time',
                'minimum_balance'=>250,'max_allocation'=>1500,'interval'=>1440,'max_daily'=>1,'trade_amount'=>50,'trigger'=>null,
                'allow_amount'=>true,'allow_trigger'=>false,
            ],
            [
                'symbol'=>'MSFT','name'=>'MSFT Core Accumulator','slug'=>'msft-core-accumulator',
                'description'=>'Long-term Microsoft accumulation bot with a fixed daily execution ceiling.',
                'strategy'=>'dca','action'=>'buy','risk'=>'low','price'=>35,'billing'=>'one_time',
                'minimum_balance'=>300,'max_allocation'=>2500,'interval'=>1440,'max_daily'=>1,'trade_amount'=>75,'trigger'=>null,
                'allow_amount'=>true,'allow_trigger'=>false,
            ],
            [
                'symbol'=>'NVDA','name'=>'NVDA Dip Entry','slug'=>'nvda-dip-entry',
                'description'=>'Trigger-based Nvidia entry bot that waits for price to move below the configured threshold.',
                'strategy'=>'price_below','action'=>'buy','risk'=>'medium','price'=>39,'billing'=>'monthly',
                'minimum_balance'=>500,'max_allocation'=>3000,'interval'=>60,'max_daily'=>2,'trade_amount'=>100,'trigger'=>825,
                'allow_amount'=>true,'allow_trigger'=>true,
            ],
            [
                'symbol'=>'TSLA','name'=>'TSLA Volatility Scout','slug'=>'tsla-volatility-scout',
                'description'=>'Higher-risk Tesla trigger bot with strict customer allocation controls.',
                'strategy'=>'price_below','action'=>'buy','risk'=>'high','price'=>59,'billing'=>'monthly',
                'minimum_balance'=>750,'max_allocation'=>3500,'interval'=>30,'max_daily'=>2,'trade_amount'=>125,'trigger'=>300,
                'allow_amount'=>true,'allow_trigger'=>true,
            ],
            [
                'symbol'=>'GOOGL','name'=>'GOOGL Breakout Watch','slug'=>'googl-breakout-watch',
                'description'=>'Price-above breakout bot for medium-risk automated execution.',
                'strategy'=>'price_above','action'=>'buy','risk'=>'medium','price'=>79,'billing'=>'quarterly',
                'minimum_balance'=>500,'max_allocation'=>4000,'interval'=>60,'max_daily'=>2,'trade_amount'=>100,'trigger'=>205,
                'allow_amount'=>true,'allow_trigger'=>true,
            ],
        ];

        foreach ($bots as $bot) {
            $stockId = DB::table('stocks')->where('symbol',$bot['symbol'])->value('id');
            if (! $stockId) {
                continue;
            }

            DB::table('bot_products')->updateOrInsert(
                ['slug'=>$bot['slug']],
                [
                    'created_by'=>$adminId,
                    'stock_id'=>$stockId,
                    'name'=>$bot['name'],
                    'description'=>$bot['description'],
                    'strategy'=>$bot['strategy'],
                    'action'=>$bot['action'],
                    'risk_level'=>$bot['risk'],
                    'price'=>$bot['price'],
                    'billing_period'=>$bot['billing'],
                    'minimum_balance'=>$bot['minimum_balance'],
                    'max_user_allocation'=>$bot['max_allocation'],
                    'default_interval_minutes'=>$bot['interval'],
                    'default_max_daily_trades'=>$bot['max_daily'],
                    'default_trade_amount'=>$bot['trade_amount'],
                    'default_trigger_price'=>$bot['trigger'],
                    'allow_user_trade_amount'=>$bot['allow_amount'],
                    'allow_user_trigger_price'=>$bot['allow_trigger'],
                    'is_active'=>true,
                    'created_at'=>$now->copy()->subDays(9),
                    'updated_at'=>$now,
                ]
            );
        }
    }
}
