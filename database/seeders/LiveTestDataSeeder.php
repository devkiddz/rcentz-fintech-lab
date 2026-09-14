<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LiveTestDataSeeder extends Seeder
{
    /**
     * Populate a realistic synthetic QA dataset.
     *
     * The identities and document numbers below are fictional. The .test email
     * domain is intentionally non-deliverable. Change the temporary passwords
     * immediately if this installation is ever exposed beyond a controlled test.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $adminConfig = config('bootstrap.admin');
            $liveTest = config('bootstrap.live_test');

            $adminEmail = $adminConfig['email'] ?: $liveTest['admin_email'];
            $adminPassword = $adminConfig['password'] ?: $liveTest['admin_password'];
            $adminName = $adminConfig['name'] ?: $liveTest['admin_name'];
            $userPassword = $liveTest['user_password'];

            $this->upsertUser($adminEmail, [
                'name' => $adminName,
                'password' => Hash::make($adminPassword),
                'email_verified_at' => $now,
                'is_admin' => true,
                'country' => 'United States',
                'currency' => 'USD',
            ]);

            $users = [
                'amara' => [
                    'name' => 'Amara Okafor',
                    'email' => 'amara.okafor@tesladrives.test',
                    'country' => 'Nigeria',
                    'currency' => 'USD',
                ],
                'daniel' => [
                    'name' => 'Daniel Brooks',
                    'email' => 'daniel.brooks@tesladrives.test',
                    'country' => 'United States',
                    'currency' => 'USD',
                ],
                'sofia' => [
                    'name' => 'Sofia Martinez',
                    'email' => 'sofia.martinez@tesladrives.test',
                    'country' => 'Spain',
                    'currency' => 'EUR',
                ],
            ];

            foreach ($users as $key => $user) {
                $this->upsertUser($user['email'], [
                    'name' => $user['name'],
                    'password' => Hash::make($userPassword),
                    'email_verified_at' => $now,
                    'is_admin' => false,
                    'country' => $user['country'],
                    'currency' => $user['currency'],
                ]);
                $users[$key]['id'] = $this->idBy('users', 'email', $user['email']);
            }

            $adminId = $this->idBy('users', 'email', $adminEmail);

            // KYC state coverage: approved, pending, and rejected.
            $this->upsertKyc($users['amara']['id'], [
                'first_name' => 'Amara',
                'last_name' => 'Okafor',
                'date_of_birth' => '1992-04-18',
                'nationality' => 'Nigerian',
                'document_type' => 'passport',
                'document_number' => 'TEST-NG-AO-2026-001',
                'document_expiry_date' => '2031-04-18',
                'address_line_1' => '12 Adeola Odeku Street',
                'address_line_2' => 'Victoria Island',
                'city' => 'Lagos',
                'state_province' => 'Lagos',
                'postal_code' => '101241',
                'country' => 'Nigeria',
                'phone_number' => '+2348000000101',
                'status' => 'approved',
                'rejection_reason' => null,
                'submitted_at' => '2026-07-18 09:30:00',
                'verified_at' => '2026-07-18 12:10:00',
            ]);
            $this->upsertKyc($users['daniel']['id'], [
                'first_name' => 'Daniel',
                'last_name' => 'Brooks',
                'date_of_birth' => '1988-11-03',
                'nationality' => 'American',
                'document_type' => 'drivers_license',
                'document_number' => 'TEST-US-DB-2026-002',
                'document_expiry_date' => '2030-11-03',
                'address_line_1' => '410 Congress Avenue',
                'address_line_2' => null,
                'city' => 'Austin',
                'state_province' => 'Texas',
                'postal_code' => '78701',
                'country' => 'United States',
                'phone_number' => '+15125550102',
                'status' => 'pending',
                'rejection_reason' => null,
                'submitted_at' => '2026-09-12 14:20:00',
                'verified_at' => null,
            ]);
            $this->upsertKyc($users['sofia']['id'], [
                'first_name' => 'Sofia',
                'last_name' => 'Martinez',
                'date_of_birth' => '1995-02-27',
                'nationality' => 'Spanish',
                'document_type' => 'passport',
                'document_number' => 'TEST-ES-SM-2026-003',
                'document_expiry_date' => '2032-02-27',
                'address_line_1' => '18 Calle de Alcala',
                'address_line_2' => null,
                'city' => 'Madrid',
                'state_province' => 'Community of Madrid',
                'postal_code' => '28014',
                'country' => 'Spain',
                'phone_number' => '+34910000103',
                'status' => 'rejected',
                'rejection_reason' => 'QA scenario: proof of address requires resubmission.',
                'submitted_at' => '2026-09-04 10:45:00',
                'verified_at' => null,
            ]);

            // Wallets.
            $wallets = [
                'amara' => ['balance' => 42500.25, 'currency' => 'USD'],
                'daniel' => ['balance' => 16800.00, 'currency' => 'USD'],
                'sofia' => ['balance' => 3500.00, 'currency' => 'EUR'],
            ];
            foreach ($wallets as $key => $wallet) {
                DB::table('wallets')->updateOrInsert(
                    ['user_id' => $users[$key]['id']],
                    array_merge($wallet, ['created_at' => $now, 'updated_at' => $now])
                );
                $wallets[$key]['id'] = $this->idBy('wallets', 'user_id', $users[$key]['id']);
            }

            $payment = [
                'bank' => $this->idBy('payment_methods', 'name', 'Bank Transfer'),
                'card' => $this->idBy('payment_methods', 'name', 'Credit Card'),
                'paypal' => $this->idBy('payment_methods', 'name', 'PayPal'),
                'financing' => $this->idBy('payment_methods', 'name', 'Tesla Financing'),
                'bitcoin' => $this->idBy('payment_methods', 'name', 'Bitcoin'),
            ];

            $walletTransactions = [
                ['key' => 'AMARA-DEPOSIT-001', 'wallet_id' => $wallets['amara']['id'], 'payment_method_id' => $payment['bank'], 'type' => 'deposit', 'amount' => 50000.00, 'fee' => 0.00, 'status' => 'completed', 'description' => 'QA opening balance deposit', 'user_crypto_details' => null, 'created_at' => '2026-07-20 10:00:00'],
                ['key' => 'AMARA-INVEST-001', 'wallet_id' => $wallets['amara']['id'], 'payment_method_id' => $payment['bank'], 'type' => 'investment', 'amount' => 7500.00, 'fee' => 0.00, 'status' => 'completed', 'description' => 'QA combined stock and fund investment debit', 'user_crypto_details' => null, 'created_at' => '2026-08-12 15:00:00'],
                ['key' => 'AMARA-WITHDRAW-001', 'wallet_id' => $wallets['amara']['id'], 'payment_method_id' => $payment['bank'], 'type' => 'withdrawal', 'amount' => 1000.00, 'fee' => 4.75, 'status' => 'processing', 'description' => 'QA withdrawal awaiting settlement', 'user_crypto_details' => null, 'created_at' => '2026-09-13 09:15:00'],
                ['key' => 'DANIEL-DEPOSIT-001', 'wallet_id' => $wallets['daniel']['id'], 'payment_method_id' => $payment['card'], 'type' => 'deposit', 'amount' => 20000.00, 'fee' => 0.00, 'status' => 'completed', 'description' => 'QA card-funded wallet deposit', 'user_crypto_details' => null, 'created_at' => '2026-08-01 13:30:00'],
                ['key' => 'DANIEL-INVEST-001', 'wallet_id' => $wallets['daniel']['id'], 'payment_method_id' => $payment['bank'], 'type' => 'investment', 'amount' => 3200.00, 'fee' => 0.00, 'status' => 'completed', 'description' => 'QA diversified portfolio investment debit', 'user_crypto_details' => null, 'created_at' => '2026-08-21 16:00:00'],
                ['key' => 'SOFIA-DEPOSIT-001', 'wallet_id' => $wallets['sofia']['id'], 'payment_method_id' => $payment['paypal'], 'type' => 'deposit', 'amount' => 5000.00, 'fee' => 0.00, 'status' => 'completed', 'description' => 'QA PayPal wallet funding', 'user_crypto_details' => null, 'created_at' => '2026-08-18 11:00:00'],
                ['key' => 'SOFIA-WITHDRAW-001', 'wallet_id' => $wallets['sofia']['id'], 'payment_method_id' => $payment['bank'], 'type' => 'withdrawal', 'amount' => 1500.00, 'fee' => 5.00, 'status' => 'completed', 'description' => 'QA completed withdrawal', 'user_crypto_details' => null, 'created_at' => '2026-08-29 12:00:00'],
                ['key' => 'SOFIA-CRYPTO-PENDING-001', 'wallet_id' => $wallets['sofia']['id'], 'payment_method_id' => $payment['bitcoin'], 'type' => 'deposit', 'amount' => 750.00, 'fee' => 0.00, 'status' => 'pending', 'description' => 'QA crypto deposit awaiting confirmation', 'user_crypto_details' => json_encode(['network' => 'Bitcoin', 'tx_hash' => 'TEST_TX_HASH_NOT_ON_CHAIN', 'confirmations' => 0]), 'created_at' => '2026-09-14 08:15:00'],
            ];
            foreach ($walletTransactions as $tx) {
                DB::table('wallet_transactions')->updateOrInsert(
                    ['reference_id' => $tx['key']],
                    [
                        'wallet_id' => $tx['wallet_id'],
                        'payment_method_id' => $tx['payment_method_id'],
                        'type' => $tx['type'],
                        'amount' => $tx['amount'],
                        'fee' => $tx['fee'],
                        'status' => $tx['status'],
                        'reference_id' => $tx['key'],
                        'description' => $tx['description'],
                        'user_crypto_details' => $tx['user_crypto_details'],
                        'created_at' => $tx['created_at'],
                        'updated_at' => $now,
                    ]
                );
            }

            // Stocks and holdings.
            $stockIds = [];
            foreach (['TSLA', 'AAPL', 'NVDA', 'MSFT', 'GOOGL', 'META'] as $symbol) {
                $stockIds[$symbol] = $this->idBy('stocks', 'symbol', $symbol);
            }

            $stockHoldings = [
                ['user' => 'amara', 'symbol' => 'TSLA', 'quantity' => 10.000000, 'average_buy_price' => 300.00, 'total_invested' => 3000.00, 'current_value' => 3199.10, 'gain' => 199.10, 'gain_pct' => 6.64],
                ['user' => 'amara', 'symbol' => 'AAPL', 'quantity' => 8.000000, 'average_buy_price' => 200.00, 'total_invested' => 1600.00, 'current_value' => 1706.00, 'gain' => 106.00, 'gain_pct' => 6.63],
                ['user' => 'amara', 'symbol' => 'NVDA', 'quantity' => 2.000000, 'average_buy_price' => 800.00, 'total_invested' => 1600.00, 'current_value' => 1700.00, 'gain' => 100.00, 'gain_pct' => 6.25],
                ['user' => 'daniel', 'symbol' => 'MSFT', 'quantity' => 1.500000, 'average_buy_price' => 500.00, 'total_invested' => 750.00, 'current_value' => 787.41, 'gain' => 37.41, 'gain_pct' => 4.99],
                ['user' => 'daniel', 'symbol' => 'GOOGL', 'quantity' => 5.000000, 'average_buy_price' => 190.00, 'total_invested' => 950.00, 'current_value' => 980.45, 'gain' => 30.45, 'gain_pct' => 3.21],
            ];
            foreach ($stockHoldings as $holding) {
                DB::table('stock_holdings')->updateOrInsert(
                    ['user_id' => $users[$holding['user']]['id'], 'stock_id' => $stockIds[$holding['symbol']]],
                    [
                        'quantity' => $holding['quantity'],
                        'average_buy_price' => $holding['average_buy_price'],
                        'total_invested' => $holding['total_invested'],
                        'current_value' => $holding['current_value'],
                        'unrealized_gain_loss' => $holding['gain'],
                        'unrealized_gain_loss_percentage' => $holding['gain_pct'],
                        'last_notified_change_percentage' => $holding['gain_pct'],
                        'created_at' => '2026-08-12 15:00:00',
                        'updated_at' => $now,
                    ]
                );
            }

            $amaraInvestmentWalletTx = $this->idBy('wallet_transactions', 'reference_id', 'AMARA-INVEST-001');
            $danielInvestmentWalletTx = $this->idBy('wallet_transactions', 'reference_id', 'DANIEL-INVEST-001');
            $stockTransactions = [
                ['user' => 'amara', 'symbol' => 'TSLA', 'wallet_tx' => $amaraInvestmentWalletTx, 'quantity' => 10.000000, 'price' => 300.00, 'total' => 3000.00, 'executed_at' => '2026-08-12 15:00:00'],
                ['user' => 'amara', 'symbol' => 'AAPL', 'wallet_tx' => $amaraInvestmentWalletTx, 'quantity' => 8.000000, 'price' => 200.00, 'total' => 1600.00, 'executed_at' => '2026-08-12 15:03:00'],
                ['user' => 'amara', 'symbol' => 'NVDA', 'wallet_tx' => $amaraInvestmentWalletTx, 'quantity' => 2.000000, 'price' => 800.00, 'total' => 1600.00, 'executed_at' => '2026-08-12 15:05:00'],
                ['user' => 'daniel', 'symbol' => 'MSFT', 'wallet_tx' => $danielInvestmentWalletTx, 'quantity' => 1.500000, 'price' => 500.00, 'total' => 750.00, 'executed_at' => '2026-08-21 16:00:00'],
                ['user' => 'daniel', 'symbol' => 'GOOGL', 'wallet_tx' => $danielInvestmentWalletTx, 'quantity' => 5.000000, 'price' => 190.00, 'total' => 950.00, 'executed_at' => '2026-08-21 16:04:00'],
            ];
            foreach ($stockTransactions as $tx) {
                DB::table('stock_transactions')->updateOrInsert(
                    ['user_id' => $users[$tx['user']]['id'], 'stock_id' => $stockIds[$tx['symbol']], 'type' => 'buy', 'executed_at' => $tx['executed_at']],
                    [
                        'wallet_transaction_id' => $tx['wallet_tx'],
                        'quantity' => $tx['quantity'],
                        'price_per_share' => $tx['price'],
                        'total_amount' => $tx['total'],
                        'fee' => 0.00,
                        'status' => 'completed',
                        'created_at' => $tx['executed_at'],
                        'updated_at' => $now,
                    ]
                );
            }

            $stockWatchlists = [
                ['user' => 'amara', 'symbol' => 'META', 'alert_price' => 470.00, 'alert_type' => 'above'],
                ['user' => 'daniel', 'symbol' => 'TSLA', 'alert_price' => 300.00, 'alert_type' => 'below'],
                ['user' => 'daniel', 'symbol' => 'NVDA', 'alert_price' => 880.00, 'alert_type' => 'above'],
                ['user' => 'sofia', 'symbol' => 'AAPL', 'alert_price' => 220.00, 'alert_type' => 'above'],
                ['user' => 'sofia', 'symbol' => 'GOOGL', 'alert_price' => 185.00, 'alert_type' => 'below'],
            ];
            foreach ($stockWatchlists as $watch) {
                DB::table('stock_watchlists')->updateOrInsert(
                    ['user_id' => $users[$watch['user']]['id'], 'stock_id' => $stockIds[$watch['symbol']]],
                    [
                        'alert_price' => $watch['alert_price'],
                        'alert_type' => $watch['alert_type'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            // Quote and history fixtures make stock-detail charts useful before API keys are configured.
            $stocks = DB::table('stocks')->whereIn('symbol', array_keys($stockIds))->get();
            foreach ($stocks as $stock) {
                DB::table('stock_quotes')->updateOrInsert(
                    ['symbol' => $stock->symbol],
                    [
                        'current_price' => $stock->current_price,
                        'previous_close' => $stock->previous_close,
                        'change_amount' => $stock->change_amount,
                        'change_percentage' => $stock->change_percentage,
                        'volume' => $stock->volume,
                        'high' => $stock->high,
                        'low' => $stock->low,
                        'open' => $stock->open,
                        'strong_buy' => $stock->change_percentage > 1 ? 7 : 4,
                        'buy' => $stock->change_percentage >= 0 ? 5 : 3,
                        'hold' => 4,
                        'sell' => $stock->change_percentage < 0 ? 4 : 2,
                        'strong_sell' => 1,
                        'recommendation_date' => '2026-09-12',
                        'fetched_at' => '2026-09-12 20:00:00',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                $base = (float) $stock->current_price;
                $series = [0.965, 0.978, 0.989, 0.982, 1.000];
                foreach ($series as $offset => $multiplier) {
                    $close = round($base * $multiplier, 2);
                    $date = sprintf('2026-09-%02d 20:00:00', 8 + $offset);
                    DB::table('stock_price_history')->updateOrInsert(
                        ['symbol' => $stock->symbol, 'timestamp' => $date, 'interval' => '1D'],
                        [
                            'open' => round($close * 0.995, 2),
                            'high' => round($close * 1.012, 2),
                            'low' => round($close * 0.988, 2),
                            'close' => $close,
                            'volume' => max(1000000, (int) (($stock->volume ?: 10000000) * (0.82 + ($offset * 0.04)))),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }

            // Investment holdings and transactions.
            $planIds = [];
            foreach (['Tesla Growth Fund', 'Sustainable Energy ETF', 'Conservative Income Fund', 'Tesla Retirement Fund', 'ESG Balanced Fund', 'Global Growth Index'] as $name) {
                $planIds[$name] = $this->idBy('investment_plans', 'name', $name);
            }

            $investmentHoldings = [
                ['user' => 'amara', 'plan' => 'Tesla Growth Fund', 'units' => 40.000000, 'average_cost' => 25.0000, 'total' => 1000.00, 'current' => 1020.00, 'gain' => 20.00, 'gain_pct' => 2.00, 'nav_purchase' => 25.0000],
                ['user' => 'amara', 'plan' => 'Sustainable Energy ETF', 'units' => 16.216216, 'average_cost' => 18.5000, 'total' => 300.00, 'current' => 304.05, 'gain' => 4.05, 'gain_pct' => 1.35, 'nav_purchase' => 18.5000],
                ['user' => 'daniel', 'plan' => 'Conservative Income Fund', 'units' => 80.000000, 'average_cost' => 12.5000, 'total' => 1000.00, 'current' => 1024.00, 'gain' => 24.00, 'gain_pct' => 2.40, 'nav_purchase' => 12.5000],
                ['user' => 'daniel', 'plan' => 'Tesla Retirement Fund', 'units' => 33.333333, 'average_cost' => 15.0000, 'total' => 500.00, 'current' => 506.67, 'gain' => 6.67, 'gain_pct' => 1.33, 'nav_purchase' => 15.0000],
            ];
            foreach ($investmentHoldings as $holding) {
                DB::table('investment_holdings')->updateOrInsert(
                    ['user_id' => $users[$holding['user']]['id'], 'investment_plan_id' => $planIds[$holding['plan']]],
                    [
                        'units' => $holding['units'],
                        'average_cost' => $holding['average_cost'],
                        'total_invested' => $holding['total'],
                        'current_value' => $holding['current'],
                        'unrealized_gain_loss' => $holding['gain'],
                        'unrealized_gain_loss_percentage' => $holding['gain_pct'],
                        'last_notified_change_percentage' => $holding['gain_pct'],
                        'nav_at_purchase' => $holding['nav_purchase'],
                        'created_at' => '2026-08-12 15:10:00',
                        'updated_at' => $now,
                    ]
                );
            }

            $investmentTransactions = [
                ['user' => 'amara', 'plan' => 'Tesla Growth Fund', 'wallet_tx' => $amaraInvestmentWalletTx, 'units' => 40.000000, 'nav' => 25.0000, 'total' => 1000.00, 'time' => '2026-08-12 15:10:00'],
                ['user' => 'amara', 'plan' => 'Sustainable Energy ETF', 'wallet_tx' => $amaraInvestmentWalletTx, 'units' => 16.216216, 'nav' => 18.5000, 'total' => 300.00, 'time' => '2026-08-12 15:12:00'],
                ['user' => 'daniel', 'plan' => 'Conservative Income Fund', 'wallet_tx' => $danielInvestmentWalletTx, 'units' => 80.000000, 'nav' => 12.5000, 'total' => 1000.00, 'time' => '2026-08-21 16:10:00'],
                ['user' => 'daniel', 'plan' => 'Tesla Retirement Fund', 'wallet_tx' => $danielInvestmentWalletTx, 'units' => 33.333333, 'nav' => 15.0000, 'total' => 500.00, 'time' => '2026-08-21 16:12:00'],
            ];
            foreach ($investmentTransactions as $tx) {
                DB::table('investment_transactions')->updateOrInsert(
                    ['user_id' => $users[$tx['user']]['id'], 'investment_plan_id' => $planIds[$tx['plan']], 'type' => 'buy', 'executed_at' => $tx['time']],
                    [
                        'wallet_transaction_id' => $tx['wallet_tx'],
                        'units' => $tx['units'],
                        'nav_at_transaction' => $tx['nav'],
                        'total_amount' => $tx['total'],
                        'fee' => 0.00,
                        'status' => 'completed',
                        'created_at' => $tx['time'],
                        'updated_at' => $now,
                    ]
                );
            }

            $investmentWatchlists = [
                ['user' => 'amara', 'plan' => 'Global Growth Index', 'alert_nav' => 20.50, 'alert_type' => 'above'],
                ['user' => 'daniel', 'plan' => 'ESG Balanced Fund', 'alert_nav' => 21.50, 'alert_type' => 'below'],
                ['user' => 'sofia', 'plan' => 'Sustainable Energy ETF', 'alert_nav' => 19.25, 'alert_type' => 'above'],
                ['user' => 'sofia', 'plan' => 'Tesla Growth Fund', 'alert_nav' => 24.75, 'alert_type' => 'below'],
            ];
            foreach ($investmentWatchlists as $watch) {
                DB::table('investment_watchlists')->updateOrInsert(
                    ['user_id' => $users[$watch['user']]['id'], 'investment_plan_id' => $planIds[$watch['plan']]],
                    [
                        'alert_nav' => $watch['alert_nav'],
                        'alert_type' => $watch['alert_type'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            $automaticPlans = [
                ['user' => 'amara', 'plan' => 'Tesla Growth Fund', 'amount' => 500.00, 'frequency' => 'monthly', 'day' => 15, 'active' => true, 'next' => '2026-10-15'],
                ['user' => 'daniel', 'plan' => 'Conservative Income Fund', 'amount' => 250.00, 'frequency' => 'quarterly', 'day' => 1, 'active' => true, 'next' => '2026-10-01'],
                ['user' => 'sofia', 'plan' => 'ESG Balanced Fund', 'amount' => 100.00, 'frequency' => 'monthly', 'day' => 20, 'active' => false, 'next' => null],
            ];
            foreach ($automaticPlans as $plan) {
                DB::table('automatic_investment_plans')->updateOrInsert(
                    ['user_id' => $users[$plan['user']]['id'], 'investment_plan_id' => $planIds[$plan['plan']]],
                    [
                        'amount' => $plan['amount'],
                        'frequency' => $plan['frequency'],
                        'day_of_month' => $plan['day'],
                        'is_active' => $plan['active'],
                        'next_investment_date' => $plan['next'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            // Keep NAV automation visible in admin without mutating demo values automatically.
            DB::table('automatic_nav_updates')->updateOrInsert(
                ['name' => 'QA Monthly Tesla Growth NAV Review'],
                [
                    'investment_plan_id' => $planIds['Tesla Growth Fund'],
                    'description' => 'Disabled QA fixture for testing the automatic NAV administration screen.',
                    'update_type' => 'increase',
                    'update_amount' => 0.2500,
                    'update_interval_value' => 30,
                    'update_interval_unit' => 'days',
                    'start_date' => '2026-09-01 00:00:00',
                    'end_date' => '2026-12-31 23:59:59',
                    'last_executed_at' => null,
                    'next_execution_at' => '2026-10-01 00:00:00',
                    'is_active' => false,
                    'total_executions' => 0,
                    'created_by' => $adminId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            // Vehicle purchase state coverage.
            $carIds = [
                'model3lr' => $this->idBy('cars', 'title', 'Tesla Model 3 Long Range'),
                'modelyperf' => $this->idBy('cars', 'title', 'Tesla Model Y Performance'),
                'model3std' => $this->idBy('cars', 'title', 'Tesla Model 3 Standard Range'),
            ];
            $purchases = [
                ['user' => 'amara', 'car' => 'model3lr', 'payment' => 'bank', 'amount' => 47240.00, 'status' => 'completed', 'date' => '2026-08-01 10:30:00', 'country' => 'Nigeria', 'city' => 'Lagos', 'state' => 'Lagos', 'postal' => '101241', 'phone' => '+2348000000101'],
                ['user' => 'daniel', 'car' => 'modelyperf', 'payment' => 'financing', 'amount' => 54190.00, 'status' => 'processing', 'date' => '2026-09-09 15:20:00', 'country' => 'United States', 'city' => 'Austin', 'state' => 'Texas', 'postal' => '78701', 'phone' => '+15125550102'],
                ['user' => 'sofia', 'car' => 'model3std', 'payment' => 'card', 'amount' => 38990.00, 'status' => 'cancelled', 'date' => '2026-08-25 09:00:00', 'country' => 'Spain', 'city' => 'Madrid', 'state' => 'Community of Madrid', 'postal' => '28014', 'phone' => '+34910000103'],
            ];
            foreach ($purchases as $purchase) {
                $user = $users[$purchase['user']];
                DB::table('purchases')->updateOrInsert(
                    ['user_id' => $user['id'], 'car_id' => $carIds[$purchase['car']], 'purchased_at' => $purchase['date']],
                    [
                        'payment_method_id' => $payment[$purchase['payment']],
                        'amount' => $purchase['amount'],
                        'billing_name' => $user['name'],
                        'billing_email' => $user['email'],
                        'billing_phone' => $purchase['phone'],
                        'billing_address' => 'QA billing address for live-test workflow',
                        'billing_city' => $purchase['city'],
                        'billing_state' => $purchase['state'],
                        'billing_postal_code' => $purchase['postal'],
                        'billing_country' => $purchase['country'],
                        'company_name' => null,
                        'tax_id' => null,
                        'transaction_hash' => null,
                        'crypto_amount' => null,
                        'crypto_currency' => null,
                        'exchange_rate' => null,
                        'status' => $purchase['status'],
                        'created_at' => $purchase['date'],
                        'updated_at' => $now,
                    ]
                );
            }

            // Notifications provide realistic dashboard activity states.
            $notifications = [
                ['user' => 'amara', 'type' => 'kyc_status', 'title' => 'Identity verification approved', 'message' => 'Your QA identity verification is approved. Investment features are available.', 'read' => true, 'date' => '2026-07-18 12:15:00'],
                ['user' => 'amara', 'type' => 'investment_success', 'title' => 'Portfolio purchase completed', 'message' => 'Your stock and managed-fund test purchases were completed successfully.', 'read' => true, 'date' => '2026-08-12 15:20:00'],
                ['user' => 'amara', 'type' => 'wallet_update', 'title' => 'Withdrawal processing', 'message' => 'Your $1,000 QA withdrawal request is currently processing.', 'read' => false, 'date' => '2026-09-13 09:16:00'],
                ['user' => 'daniel', 'type' => 'kyc_status', 'title' => 'Verification under review', 'message' => 'Your submitted identity documents are pending manual review.', 'read' => false, 'date' => '2026-09-12 14:21:00'],
                ['user' => 'daniel', 'type' => 'purchase_update', 'title' => 'Vehicle order processing', 'message' => 'Your Model Y Performance QA order is being processed.', 'read' => false, 'date' => '2026-09-09 15:25:00'],
                ['user' => 'sofia', 'type' => 'kyc_status', 'title' => 'Verification requires resubmission', 'message' => 'The QA KYC submission was rejected so the resubmission flow can be tested.', 'read' => false, 'date' => '2026-09-05 08:00:00'],
                ['user' => 'sofia', 'type' => 'wallet_update', 'title' => 'Crypto deposit pending', 'message' => 'Your synthetic Bitcoin deposit is waiting for network confirmation.', 'read' => false, 'date' => '2026-09-14 08:16:00'],
            ];
            foreach ($notifications as $notification) {
                DB::table('notifications')->updateOrInsert(
                    ['user_id' => $users[$notification['user']]['id'], 'type' => $notification['type'], 'title' => $notification['title']],
                    [
                        'message' => $notification['message'],
                        'data' => json_encode(['fixture' => true]),
                        'is_read' => $notification['read'],
                        'read_at' => $notification['read'] ? $notification['date'] : null,
                        'created_at' => $notification['date'],
                        'updated_at' => $now,
                    ]
                );
            }

            $this->command?->info('Live-test dataset seeded: 1 admin, 3 synthetic users, KYC, wallets, investments, stocks, purchases, and activity.');
            $this->command?->warn('Temporary admin: '.$adminEmail.' / '.$adminPassword);
            $this->command?->warn('Test-user password: '.$userPassword);
        });
    }

    private function upsertUser(string $email, array $attributes): void
    {
        DB::table('users')->updateOrInsert(
            ['email' => $email],
            array_merge($attributes, [
                'email' => $email,
                'updated_at' => now(),
                'created_at' => now(),
            ])
        );
    }

    private function upsertKyc(int $userId, array $attributes): void
    {
        DB::table('kycs')->updateOrInsert(
            ['user_id' => $userId],
            array_merge($attributes, [
                'user_id' => $userId,
                'document_front_path' => null,
                'document_back_path' => null,
                'selfie_path' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ])
        );
    }

    private function idBy(string $table, string $column, mixed $value): int
    {
        $id = DB::table($table)->where($column, $value)->value('id');

        if (! $id) {
            throw new \RuntimeException("Live-test seeder dependency missing: {$table}.{$column}={$value}");
        }

        return (int) $id;
    }
}
