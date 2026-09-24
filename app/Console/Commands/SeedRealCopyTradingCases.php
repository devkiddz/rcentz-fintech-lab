<?php

namespace App\Console\Commands;

use App\Models\BrokerOrder;
use App\Models\CopyRelationship;
use App\Models\CopyStrategy;
use App\Models\CopyTraderProfile;
use App\Models\KYC;
use App\Models\MarketInstrument;
use App\Models\PaymentMethod;
use App\Models\StrategyProviderApplication;
use App\Models\TradePosition;
use App\Models\User;
use App\Models\Wallet;
use App\Services\BrokerOrderService;
use App\Services\CopyTradingService;
use App\Services\MarketPriceRouter;
use App\Services\MarketSettlementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class SeedRealCopyTradingCases extends Command
{
    protected $signature = 'copy-trading:seed-real-cases
        {--password= : Shared password for the dedicated QA users}
        {--inspect : Inspect existing cases without attempting execution}';

    protected $description = 'Create and execute three persistent real Copy Trading QA cases across Stock, Forex and Crypto.';

    private const PROVIDER_EMAIL = 'qa.copy.provider@rcentz.test';

    public function handle(
        BrokerOrderService $orders,
        CopyTradingService $copyTrading,
        MarketPriceRouter $prices,
        MarketSettlementService $settlement
    ): int {
        $password = (string) ($this->option('password') ?: config('bootstrap.live_test.user_password'));
        if (strlen($password) < 10) {
            $this->error('QA password must be at least 10 characters.');
            return self::FAILURE;
        }

        $adminId = (int) (User::query()->where('is_admin', true)->value('id') ?? 0);
        if ($adminId <= 0) {
            $this->error('An admin account is required before Copy Trading QA cases can be prepared.');
            return self::FAILURE;
        }

        $provider = $this->ensureUser(
            self::PROVIDER_EMAIL,
            'Avery Morgan',
            'United States',
            'USD',
            25000,
            $password
        );
        $this->ensureApprovedKyc($provider, 'Avery', 'Morgan', 'United States', 'TEST-US-COPY-PROVIDER-001');
        $profile = $this->ensureProviderProfile($provider, $adminId);

        $cases = [
            [
                'key' => 'stock-aapl',
                'label' => 'US Equity Discipline',
                'description' => 'Measured large-cap equity entries with explicit provider exits and conservative follower sizing.',
                'risk' => 'medium',
                'asset_class' => 'stock',
                'symbol' => 'AAPL',
                'provider_capital' => 200.00,
                'follower_name' => 'Nora Adeyemi',
                'follower_email' => 'qa.copy.stock@rcentz.test',
                'follower_country' => 'Nigeria',
                'follower_balance' => 10000.00,
                'allocation_limit' => 1000.00,
                'max_trade_amount' => 150.00,
                'copy_ratio_percent' => 50.00,
                'close_after_entry' => true,
            ],
            [
                'key' => 'forex-eurusd',
                'label' => 'FX Swing Framework',
                'description' => 'Structured EUR/USD swing exposure with capped follower allocation and explicit strategy attribution.',
                'risk' => 'medium',
                'asset_class' => 'forex',
                'symbol' => 'EURUSD',
                'provider_capital' => 300.00,
                'follower_name' => 'Tunde Mensah',
                'follower_email' => 'qa.copy.fx@rcentz.test',
                'follower_country' => 'Ghana',
                'follower_balance' => 12000.00,
                'allocation_limit' => 1500.00,
                'max_trade_amount' => 200.00,
                'copy_ratio_percent' => 40.00,
                'close_after_entry' => false,
            ],
            [
                'key' => 'crypto-btcusd',
                'label' => 'Digital Asset Rotation',
                'description' => 'Spot-only Bitcoin allocation with bounded follower exposure and no leverage or shorting.',
                'risk' => 'high',
                'asset_class' => 'crypto',
                'symbol' => 'BTCUSD',
                'provider_capital' => 400.00,
                'follower_name' => 'Lena Okoro',
                'follower_email' => 'qa.copy.crypto@rcentz.test',
                'follower_country' => 'Nigeria',
                'follower_balance' => 15000.00,
                'allocation_limit' => 1200.00,
                'max_trade_amount' => 150.00,
                'copy_ratio_percent' => 25.00,
                'close_after_entry' => true,
            ],
        ];

        foreach ($cases as &$case) {
            $case['strategy'] = $this->ensureStrategy($profile, $case);
            $case['follower'] = $this->ensureUser(
                $case['follower_email'],
                $case['follower_name'],
                $case['follower_country'],
                'USD',
                $case['follower_balance'],
                $password
            );
            [$firstName, $lastName] = $this->nameParts($case['follower_name']);
            $this->ensureApprovedKyc(
                $case['follower'],
                $firstName,
                $lastName,
                $case['follower_country'],
                'TEST-COPY-'.strtoupper(str_replace('-', '-', $case['key']))
            );
            $case['relationship'] = $this->ensureRelationship($provider, $case['follower'], $case['strategy'], $case);
            $case['instrument'] = MarketInstrument::query()
                ->where('asset_class', $case['asset_class'])
                ->where('symbol', $case['symbol'])
                ->where('is_active', true)
                ->firstOrFail();
        }
        unset($case);

        if ($this->option('inspect')) {
            $this->renderSummary($provider, $cases, $password, $prices->activeMarketplace());
            return self::SUCCESS;
        }

        $originalMarketplace = $prices->activeMarketplace();

        try {
            $prices->setActiveMarketplace('controlled', $adminId);
            $prices->forget();

            foreach ($cases as &$case) {
                $this->executeCase($provider, $case, $orders, $copyTrading, $prices, $settlement);
            }
            unset($case);
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('Copy Trading QA case execution failed: '.$e->getMessage());
            return self::FAILURE;
        } finally {
            $prices->setActiveMarketplace($originalMarketplace, $adminId);
            $prices->forget();
        }

        $this->renderSummary($provider, $cases, $password, $prices->activeMarketplace());
        return self::SUCCESS;
    }

    private function executeCase(
        User $provider,
        array &$case,
        BrokerOrderService $orders,
        CopyTradingService $copyTrading,
        MarketPriceRouter $prices,
        MarketSettlementService $settlement
    ): void {
        /** @var MarketInstrument $instrument */
        $instrument = $case['instrument'];
        /** @var CopyStrategy $strategy */
        $strategy = $case['strategy'];
        /** @var CopyRelationship $relationship */
        $relationship = $case['relationship'];

        $context = [
            'execution_source' => 'copy_strategy',
            'execution_source_id' => $strategy->id,
            'context_type' => 'copy_strategy',
            'context_id' => $strategy->id,
            'actor_type' => 'user',
            'actor_id' => $provider->id,
            'exit_reason' => 'provider_exit',
            'metadata' => [
                'copy_strategy_id' => $strategy->id,
                'qa_case' => $case['key'],
            ],
        ];

        $risk = [
            'metadata' => [
                'copy_strategy_id' => $strategy->id,
                'qa_case' => $case['key'],
            ],
        ];

        $buyKey = 'qa-copy-case:'.$case['key'].':provider-buy:v1';
        $buyOrder = BrokerOrder::query()
            ->where('user_id', $provider->id)
            ->where('idempotency_key', $buyKey)
            ->first();

        if (! $buyOrder) {
            $price = (float) $prices->price($instrument, 'controlled');
            if ($price <= 0) {
                throw new RuntimeException($case['label'].' controlled price is unavailable.');
            }

            [$quantity, $mode] = $this->providerBuySize(
                $provider,
                $instrument,
                (float) $case['provider_capital'],
                $price,
                $settlement
            );

            $buyOrder = $orders->placeMarketOrder(
                $provider,
                $instrument,
                'buy',
                $quantity,
                $mode,
                $buyKey,
                $risk,
                $context
            );
        }

        $buyOrder->loadMissing('execution');
        if ($buyOrder->status !== BrokerOrder::STATUS_FILLED || ! $buyOrder->execution) {
            throw new RuntimeException($case['label'].' provider BUY did not fill.');
        }

        $copyTrading->mirrorCompletedExecution($buyOrder->execution, $strategy->id);

        $entryCopy = $relationship->executions()
            ->where('provider_market_execution_transaction_id', $buyOrder->execution->id)
            ->first();

        if (! $entryCopy || $entryCopy->status !== 'completed' || ! $entryCopy->follower_broker_order_id) {
            throw new RuntimeException($case['label'].' follower BUY did not complete through BrokerOrder.');
        }

        $case['provider_buy_order_id'] = $buyOrder->id;
        $case['provider_buy_execution_id'] = $buyOrder->execution->id;
        $case['follower_buy_copy_execution_id'] = $entryCopy->id;
        $case['follower_buy_order_id'] = $entryCopy->follower_broker_order_id;
        $case['follower_buy_market_execution_id'] = $entryCopy->follower_market_execution_transaction_id;

        if (! $case['close_after_entry']) {
            return;
        }

        $sellKey = 'qa-copy-case:'.$case['key'].':provider-close:v1';
        $sellOrder = BrokerOrder::query()
            ->where('user_id', $provider->id)
            ->where('idempotency_key', $sellKey)
            ->first();

        if (! $sellOrder) {
            $providerPosition = TradePosition::query()
                ->where('user_id', $provider->id)
                ->where('market_instrument_id', $instrument->id)
                ->where('marketplace', 'controlled')
                ->where('context_type', 'copy_strategy')
                ->where('context_id', $strategy->id)
                ->whereIn('status', ['open', 'exit_queued'])
                ->where('open_quantity', '>', 0)
                ->oldest('opened_at')
                ->first();

            if (! $providerPosition) {
                throw new RuntimeException($case['label'].' provider position is unavailable for exit.');
            }

            $sellOrder = $orders->placePositionClose(
                $provider,
                $providerPosition,
                null,
                $sellKey,
                $context
            );
        }

        $sellOrder->loadMissing('execution');
        if ($sellOrder->status !== BrokerOrder::STATUS_FILLED || ! $sellOrder->execution) {
            throw new RuntimeException($case['label'].' provider exit did not fill.');
        }

        $copyTrading->mirrorCompletedExecution($sellOrder->execution, $strategy->id);

        $exitCopy = $relationship->executions()
            ->where('provider_market_execution_transaction_id', $sellOrder->execution->id)
            ->first();

        if (! $exitCopy || $exitCopy->status !== 'completed' || ! $exitCopy->follower_broker_order_id) {
            throw new RuntimeException($case['label'].' follower exit did not complete through BrokerOrder.');
        }

        $case['provider_sell_order_id'] = $sellOrder->id;
        $case['provider_sell_execution_id'] = $sellOrder->execution->id;
        $case['follower_sell_copy_execution_id'] = $exitCopy->id;
        $case['follower_sell_order_id'] = $exitCopy->follower_broker_order_id;
        $case['follower_sell_market_execution_id'] = $exitCopy->follower_market_execution_transaction_id;
    }

    private function providerBuySize(
        User $provider,
        MarketInstrument $instrument,
        float $capital,
        float $price,
        MarketSettlementService $settlement
    ): array {
        if ($instrument->isCrypto()) {
            return [round($capital, 2), 'settlement_amount'];
        }

        if ($instrument->isStock()) {
            return [round($capital / $price, 8), 'units'];
        }

        $currency = strtoupper((string) ($provider->wallet?->currency ?: 'USD'));
        $oneUnit = $settlement->amountForBaseUnits($instrument, 1.0, $price, $currency, false);
        if ($oneUnit <= 0) {
            throw new RuntimeException('Forex settlement conversion is unavailable.');
        }

        $units = round($capital / $oneUnit, 8);
        if ($units < 1) {
            throw new RuntimeException('Forex provider case amount resolves below one executable base unit.');
        }

        return [$units, 'units'];
    }

    private function ensureUser(
        string $email,
        string $name,
        string $country,
        string $currency,
        float $funding,
        string $password
    ): User {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_admin' => false,
                'country' => $country,
                'currency' => $currency,
            ]
        );

        $user->forceFill([
            'name' => $name,
            'password' => Hash::make($password),
            'email_verified_at' => $user->email_verified_at ?: now(),
            'is_admin' => false,
            'country' => $country,
            'currency' => $currency,
        ])->save();

        $wallet = Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'reserved_balance' => 0, 'currency' => $currency]
        );

        $fundingReference = 'QA-COPY-FUND-'.strtoupper(substr(hash('sha256', $email), 0, 12));
        $alreadyFunded = $wallet->transactions()->where('reference_id', $fundingReference)->exists();
        if (! $alreadyFunded) {
            $method = PaymentMethod::query()->firstOrCreate(
                ['name' => 'QA Case Funding'],
                [
                    'type' => 'traditional',
                    'details' => 'Internal non-production funding rail for persistent QA case users.',
                    'is_active' => true,
                    'allow_deposit' => false,
                    'allow_withdraw' => false,
                ]
            );

            $wallet->transactions()->create([
                'payment_method_id' => $method->id,
                'type' => 'deposit',
                'direction' => 'credit',
                'amount' => $funding,
                'fee' => 0,
                'status' => 'completed',
                'reference_id' => $fundingReference,
                'description' => 'Persistent Copy Trading QA case funding.',
            ]);
            $wallet->addFunds($funding);
        }

        return $user->fresh(['wallet', 'kyc']);
    }

    private function ensureApprovedKyc(
        User $user,
        string $firstName,
        string $lastName,
        string $country,
        string $documentNumber
    ): void {
        KYC::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'date_of_birth' => '1990-01-15',
                'nationality' => $country,
                'document_type' => 'passport',
                'document_number' => $documentNumber,
                'document_expiry_date' => '2032-01-15',
                'address_line_1' => 'QA controlled test address',
                'city' => 'QA City',
                'state_province' => 'QA State',
                'postal_code' => '000001',
                'country' => $country,
                'phone_number' => '+10000000000',
                'status' => 'approved',
                'rejection_reason' => null,
                'submitted_at' => now()->subDay(),
                'verified_at' => now()->subDay(),
            ]
        );
    }

    private function ensureProviderProfile(User $provider, int $adminId): CopyTraderProfile
    {
        StrategyProviderApplication::query()->updateOrCreate(
            ['user_id' => $provider->id, 'status' => 'approved'],
            [
                'display_name' => 'Avery Morgan',
                'experience' => 'Dedicated multi-asset Copy Trading QA provider used to validate real provider/follower execution flows.',
                'strategy_summary' => 'Three explicit strategies covering equity, Forex and spot Crypto execution.',
                'risk_level' => 'medium',
                'admin_notes' => 'Persistent QA fixture. Not a real customer or performance claim.',
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
            ]
        );

        return CopyTraderProfile::query()->updateOrCreate(
            ['user_id' => $provider->id],
            [
                'strategy_name' => 'Multi-Asset QA Desk',
                'bio' => 'Persistent synthetic provider used for end-to-end Copy Trading acceptance across supported liquid asset classes.',
                'risk_level' => 'medium',
                'is_public' => true,
                'is_accepting_copiers' => true,
                'approved_at' => now(),
                'approved_by' => $adminId,
            ]
        );
    }

    private function ensureStrategy(CopyTraderProfile $profile, array $case): CopyStrategy
    {
        return CopyStrategy::query()->updateOrCreate(
            [
                'copy_trader_profile_id' => $profile->id,
                'name' => $case['label'],
            ],
            [
                'description' => $case['description'].' QA CASE: synthetic users, real application execution path, no performance promise.',
                'risk_level' => $case['risk'],
                'minimum_allocation' => min(250, (float) $case['allocation_limit']),
                'recommended_allocation' => (float) $case['allocation_limit'],
                'is_public' => true,
                'is_active' => true,
            ]
        );
    }

    private function ensureRelationship(
        User $provider,
        User $follower,
        CopyStrategy $strategy,
        array $case
    ): CopyRelationship {
        $relationship = CopyRelationship::query()
            ->where('provider_id', $provider->id)
            ->where('follower_id', $follower->id)
            ->where('copy_strategy_id', $strategy->id)
            ->latest('id')
            ->first();

        if ($relationship) {
            if (in_array($relationship->status, ['stopped', 'completed'], true)) {
                throw new RuntimeException(
                    'Existing QA relationship '.$relationship->id.' is terminal. Do not rewrite financial history; use a new case version.'
                );
            }

            $relationship->update([
                'allocation_limit' => $case['allocation_limit'],
                'max_trade_amount' => $case['max_trade_amount'],
                'copy_ratio_percent' => $case['copy_ratio_percent'],
                'status' => 'active',
                'duration_minutes' => 43200,
                'ends_at' => $relationship->ends_at && $relationship->ends_at->isFuture()
                    ? $relationship->ends_at
                    : now()->addDays(30),
            ]);

            return $relationship->refresh();
        }

        return CopyRelationship::query()->create([
            'follower_id' => $follower->id,
            'provider_id' => $provider->id,
            'copy_strategy_id' => $strategy->id,
            'allocation_limit' => $case['allocation_limit'],
            'used_amount' => 0,
            'max_trade_amount' => $case['max_trade_amount'],
            'copy_ratio_percent' => $case['copy_ratio_percent'],
            'status' => 'active',
            'duration_minutes' => 43200,
            'started_at' => now(),
            'ends_at' => now()->addDays(30),
        ]);
    }

    private function renderSummary(User $provider, array $cases, string $password, string $marketplace): void
    {
        $this->newLine();
        $this->info('Persistent real Copy Trading QA cases');
        $this->line('Marketplace restored/current: '.strtoupper($marketplace));
        $this->line('Provider login: '.self::PROVIDER_EMAIL);
        $this->line('Shared QA password: '.$password);
        $this->warn('All @rcentz.test identities are synthetic QA users. They are not real customers and these trades are not performance claims.');
        $this->newLine();

        $rows = [];

        foreach ($cases as $case) {
            /** @var CopyRelationship $relationship */
            $relationship = $case['relationship']->fresh();
            /** @var User $follower */
            $follower = $case['follower']->fresh(['wallet']);
            /** @var MarketInstrument $instrument */
            $instrument = $case['instrument'];

            $copyExecutions = $relationship->executions()->orderBy('id')->get();
            $completed = $copyExecutions->where('status', 'completed');
            $followerOpen = TradePosition::query()
                ->where('user_id', $follower->id)
                ->where('market_instrument_id', $instrument->id)
                ->where('context_type', 'copy_relationship')
                ->where('context_id', $relationship->id)
                ->whereIn('status', ['open', 'exit_queued'])
                ->where('open_quantity', '>', 0)
                ->count();

            $rows[] = [
                strtoupper($case['asset_class']),
                $instrument->display_symbol ?: $instrument->symbol,
                $case['label'],
                $follower->email,
                $relationship->id,
                $completed->count(),
                $followerOpen,
                number_format((float) $relationship->used_amount, 2),
                number_format((float) $follower->wallet?->balance, 2),
                $case['close_after_entry'] ? 'ROUND TRIP' : 'OPEN',
            ];
        }

        $this->table([
            'Asset', 'Instrument', 'Strategy', 'Follower', 'Rel', 'Completed Copies', 'Open Pos', 'Used', 'Wallet', 'Case State'
        ], $rows);

        $this->newLine();
        $this->line('Browser QA logins:');
        foreach ($cases as $case) {
            $this->line('  '.$case['follower_email'].'  → '.$case['label']);
        }
    }

    private function nameParts(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        return [
            $parts[0] ?? 'QA',
            implode(' ', array_slice($parts, 1)) ?: 'Customer',
        ];
    }
}
