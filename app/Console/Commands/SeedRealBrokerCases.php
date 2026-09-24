<?php

namespace App\Console\Commands;

use App\Models\BrokerOrder;
use App\Models\KYC;
use App\Models\MarketHolding;
use App\Models\MarketInstrument;
use App\Models\PaymentMethod;
use App\Models\StockHolding;
use App\Models\TradePosition;
use App\Models\User;
use App\Models\Wallet;
use App\Services\BrokerOrderService;
use App\Services\MarketPriceRouter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SeedRealBrokerCases extends Command
{
    protected $signature = 'broker:seed-real-cases
        {--password= : Shared password for dedicated brokerage QA users}
        {--inspect : Inspect existing cases without attempting execution}';

    protected $description = 'Create three persistent real manual brokerage QA cases across Stock, Forex and Crypto.';

    public function handle(BrokerOrderService $orders, MarketPriceRouter $prices): int
    {
        $password = (string) ($this->option('password') ?: config('bootstrap.live_test.user_password'));
        if (strlen($password) < 10) {
            $this->error('QA password must be at least 10 characters.');
            return self::FAILURE;
        }

        $adminId = (int) (User::query()->where('is_admin', true)->value('id') ?? 0);
        if ($adminId <= 0) {
            $this->error('An admin account is required before brokerage QA cases can be prepared.');
            return self::FAILURE;
        }

        $cases = [
            [
                'key' => 'stock-aapl',
                'asset_class' => 'stock',
                'symbol' => 'AAPL',
                'name' => 'Maya Cole',
                'email' => 'qa.broker.stock@rcentz.test',
                'country' => 'United States',
                'funding' => 10000.00,
                'quantity' => 0.25,
                'quantity_mode' => 'units',
                'close_after_entry' => true,
                'risk' => [],
            ],
            [
                'key' => 'forex-eurusd',
                'asset_class' => 'forex',
                'symbol' => 'EURUSD',
                'name' => 'Kojo Mensah',
                'email' => 'qa.broker.fx@rcentz.test',
                'country' => 'Ghana',
                'funding' => 12000.00,
                'quantity' => 100.0,
                'quantity_mode' => 'units',
                'close_after_entry' => false,
                'risk' => [
                    'stop_loss_percent' => 2.0,
                    'take_profit_percent' => 4.0,
                ],
            ],
            [
                'key' => 'crypto-btcusd',
                'asset_class' => 'crypto',
                'symbol' => 'BTCUSD',
                'name' => 'Zara Okoye',
                'email' => 'qa.broker.crypto@rcentz.test',
                'country' => 'Nigeria',
                'funding' => 15000.00,
                'quantity' => 125.0,
                'quantity_mode' => 'settlement_amount',
                'close_after_entry' => true,
                'risk' => [],
            ],
        ];

        foreach ($cases as &$case) {
            $case['user'] = $this->ensureUser(
                $case['email'],
                $case['name'],
                $case['country'],
                (float) $case['funding'],
                $password
            );
            [$firstName, $lastName] = $this->nameParts($case['name']);
            $this->ensureApprovedKyc(
                $case['user'],
                $firstName,
                $lastName,
                $case['country'],
                'TEST-BROKER-'.strtoupper($case['key'])
            );
            $case['instrument'] = MarketInstrument::query()
                ->where('asset_class', $case['asset_class'])
                ->where('symbol', $case['symbol'])
                ->where('is_active', true)
                ->firstOrFail();
        }
        unset($case);

        if ($this->option('inspect')) {
            $this->renderSummary($cases, $password, $prices->activeMarketplace());
            return self::SUCCESS;
        }

        $originalMarketplace = $prices->activeMarketplace();

        try {
            $prices->setActiveMarketplace('controlled', $adminId);
            $prices->forget();

            foreach ($cases as &$case) {
                $this->executeCase($case, $orders);
            }
            unset($case);
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('Broker QA case execution failed: '.$e->getMessage());
            return self::FAILURE;
        } finally {
            $prices->setActiveMarketplace($originalMarketplace, $adminId);
            $prices->forget();
        }

        $this->renderSummary($cases, $password, $prices->activeMarketplace());
        return self::SUCCESS;
    }

    private function executeCase(array &$case, BrokerOrderService $orders): void
    {
        /** @var User $user */
        $user = $case['user']->fresh(['wallet', 'kyc']);
        /** @var MarketInstrument $instrument */
        $instrument = $case['instrument'];

        $buyKey = 'qa-broker-case:'.$case['key'].':buy:v1';
        $buy = $orders->placeMarketOrder(
            $user,
            $instrument,
            'buy',
            (float) $case['quantity'],
            $case['quantity_mode'],
            $buyKey,
            $case['risk']
        );

        $replay = $orders->placeMarketOrder(
            $user,
            $instrument,
            'buy',
            (float) $case['quantity'],
            $case['quantity_mode'],
            $buyKey,
            $case['risk']
        );

        if ((int) $buy->id !== (int) $replay->id) {
            throw new RuntimeException($case['key'].' duplicate BUY did not return the original BrokerOrder.');
        }

        $conflictRejected = false;
        try {
            $orders->placeMarketOrder(
                $user,
                $instrument,
                'buy',
                (float) $case['quantity'] + ($case['quantity_mode'] === 'settlement_amount' ? 1.0 : 0.01),
                $case['quantity_mode'],
                $buyKey,
                $case['risk']
            );
        } catch (\Throwable) {
            $conflictRejected = true;
        }

        if (! $conflictRejected) {
            throw new RuntimeException($case['key'].' reused idempotency key accepted a different BUY request.');
        }

        $buy->loadMissing('execution');
        if ($buy->status !== BrokerOrder::STATUS_FILLED || ! $buy->execution) {
            throw new RuntimeException($case['key'].' BUY did not fill through the unified execution ledger.');
        }

        $positionId = (int) ($buy->execution->trade_position_id ?? 0);
        if ($positionId <= 0) {
            throw new RuntimeException($case['key'].' BUY did not create a managed TradePosition.');
        }

        $position = TradePosition::query()->findOrFail($positionId);
        if ($position->context_type !== 'broker_order' || (int) $position->context_id !== (int) $buy->id) {
            throw new RuntimeException($case['key'].' position lost manual BrokerOrder attribution.');
        }

        $case['buy_order_id'] = $buy->id;
        $case['buy_execution_id'] = $buy->execution->id;
        $case['position_id'] = $position->id;
        $case['idempotency_replay_ok'] = true;
        $case['idempotency_conflict_rejected'] = true;

        if (! $case['close_after_entry']) {
            return;
        }

        $closeKey = 'qa-broker-case:'.$case['key'].':close:v1';
        $close = $orders->placePositionClose($user, $position, null, $closeKey);
        $closeReplay = $orders->placePositionClose($user, $position->fresh(), null, $closeKey);

        if ((int) $close->id !== (int) $closeReplay->id) {
            throw new RuntimeException($case['key'].' duplicate CLOSE did not return the original BrokerOrder.');
        }

        $close->loadMissing('execution');
        if ($close->status !== BrokerOrder::STATUS_FILLED || ! $close->execution) {
            throw new RuntimeException($case['key'].' CLOSE did not fill through the unified execution ledger.');
        }

        $position->refresh();
        if ($position->status !== 'closed' || (float) $position->open_quantity > 0.00000001) {
            throw new RuntimeException($case['key'].' position remained open after full close.');
        }

        $case['close_order_id'] = $close->id;
        $case['close_execution_id'] = $close->execution->id;
        $case['close_replay_ok'] = true;
    }

    private function ensureUser(
        string $email,
        string $name,
        string $country,
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
                'currency' => 'USD',
            ]
        );

        $user->forceFill([
            'name' => $name,
            'password' => Hash::make($password),
            'email_verified_at' => $user->email_verified_at ?: now(),
            'is_admin' => false,
            'country' => $country,
            'currency' => 'USD',
        ])->save();

        $wallet = Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'reserved_balance' => 0, 'currency' => 'USD']
        );

        $fundingReference = 'QA-BROKER-FUND-'.strtoupper(substr(hash('sha256', $email), 0, 12));
        if (! $wallet->transactions()->where('reference_id', $fundingReference)->exists()) {
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
                'description' => 'Persistent Liquid Brokerage QA case funding.',
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

    private function renderSummary(array $cases, string $password, string $marketplace): void
    {
        $rows = [];

        foreach ($cases as $case) {
            /** @var User $user */
            $user = $case['user']->fresh(['wallet']);
            /** @var MarketInstrument $instrument */
            $instrument = $case['instrument'];

            $buy = BrokerOrder::query()
                ->where('user_id', $user->id)
                ->where('idempotency_key', 'qa-broker-case:'.$case['key'].':buy:v1')
                ->first();
            $close = BrokerOrder::query()
                ->where('user_id', $user->id)
                ->where('idempotency_key', 'qa-broker-case:'.$case['key'].':close:v1')
                ->first();

            $position = $buy?->market_execution_transaction_id
                ? TradePosition::query()
                    ->where('entry_market_execution_transaction_id', $buy->market_execution_transaction_id)
                    ->first()
                : null;

            $holdingQty = $instrument->isStock()
                ? (float) (StockHolding::query()
                    ->where('user_id', $user->id)
                    ->where('market_instrument_id', $instrument->id)
                    ->where('marketplace', 'controlled')
                    ->value('quantity') ?? 0)
                : (float) (MarketHolding::query()
                    ->where('user_id', $user->id)
                    ->where('market_instrument_id', $instrument->id)
                    ->where('marketplace', 'controlled')
                    ->value('quantity') ?? 0);

            $rows[] = [
                strtoupper($case['asset_class']),
                $instrument->display_symbol,
                $case['email'],
                $buy?->status ?: 'MISSING',
                $close?->status ?: '—',
                $position?->status ?: '—',
                number_format($holdingQty, 8),
                number_format((float) ($user->wallet?->balance ?? 0), 2),
            ];
        }

        $this->newLine();
        $this->line('Persistent real Liquid Brokerage QA cases');
        $this->line('Marketplace restored/current: '.strtoupper($marketplace));
        $this->line('Shared QA password: '.$password);
        $this->line('All @rcentz.test identities are synthetic QA users. Trades are real application-path controlled-market executions, not performance claims.');
        $this->newLine();
        $this->table(
            ['Asset', 'Instrument', 'Customer', 'BUY', 'CLOSE', 'Position', 'Holding Qty', 'Wallet'],
            $rows
        );

        $this->line('QA logins:');
        foreach ($cases as $case) {
            $this->line('  '.$case['email'].'  → '.strtoupper($case['asset_class']).' '.$case['instrument']->display_symbol);
        }
    }

    private function nameParts(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $first = array_shift($parts) ?: 'QA';
        $last = implode(' ', $parts) ?: 'Customer';
        return [$first, $last];
    }
}
