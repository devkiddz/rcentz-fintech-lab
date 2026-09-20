<?php

namespace App\Console\Commands;

use App\Models\KYC;
use App\Models\PrivateInvestmentAsset;
use App\Models\PrivateInvestmentEvent;
use App\Models\PrivateInvestmentHolding;
use App\Models\PrivateInvestmentInstrument;
use App\Models\PrivateInvestmentLifecycleEvent;
use App\Models\PrivateInvestmentPrice;
use App\Models\PrivateInvestmentTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\PrivateInvestmentLifecycleService;
use App\Services\PrivateInvestmentOrderEngine;
use App\Services\PrivateInvestmentValuationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SeedRealPrivateInvestmentCases extends Command
{
    protected $signature = 'investment:seed-ms4-real-cases
        {--password=RcentzQA!2026 : Shared password for dedicated QA investment users}
        {--inspect : Print current case state without attempting mutations}';

    protected $description = 'Create and execute three persistent Private Investment QA cases through the real order, valuation and lifecycle services.';

    public function handle(
        PrivateInvestmentOrderEngine $orders,
        PrivateInvestmentValuationService $valuation,
        PrivateInvestmentLifecycleService $lifecycle
    ): int {
        $password = (string) $this->option('password');
        if (strlen($password) < 10) {
            $this->error('QA password must be at least 10 characters.');
            return self::FAILURE;
        }

        $adminId = (int) (User::query()->where('is_admin', true)->value('id') ?? 0);
        if ($adminId <= 0) {
            $this->error('An admin account is required before MS4 cases can be prepared.');
            return self::FAILURE;
        }

        $cases = [
            [
                'key' => 'income-roundtrip',
                'name' => 'QA Income Property Fund',
                'symbol' => 'QAIPF',
                'category' => 'real_estate',
                'risk' => 'medium',
                'opening_price' => 25.00,
                'supply' => 50000.0,
                'minimum' => 250.00,
                'maximum' => 10000.00,
                'management_fee' => 1.00,
                'subscription_fee' => 1.00,
                'redemption_fee' => 0.50,
                'lock_days' => 0,
                'duration_days' => 365,
                'return_interval_days' => 30,
                'projection_min' => 8.0,
                'projection_max' => 12.0,
                'user_name' => 'Ada Ekanem',
                'email' => 'qa.invest.income@rcentz.test',
                'country' => 'Nigeria',
                'funding' => 10000.00,
                'subscribe' => 1000.00,
                'mode' => 'roundtrip',
            ],
            [
                'key' => 'locked-holding',
                'name' => 'QA Locked Income Note',
                'symbol' => 'QALIN',
                'category' => 'bonds',
                'risk' => 'low',
                'opening_price' => 10.00,
                'supply' => 100000.0,
                'minimum' => 500.00,
                'maximum' => 20000.00,
                'management_fee' => 0.50,
                'subscription_fee' => 0.50,
                'redemption_fee' => 0.25,
                'lock_days' => 30,
                'duration_days' => 180,
                'return_interval_days' => 30,
                'projection_min' => 5.0,
                'projection_max' => 7.0,
                'user_name' => 'Musa Bello',
                'email' => 'qa.invest.locked@rcentz.test',
                'country' => 'Nigeria',
                'funding' => 12000.00,
                'subscribe' => 1500.00,
                'mode' => 'locked',
            ],
            [
                'key' => 'partial-lifecycle',
                'name' => 'QA Technology Participation Basket',
                'symbol' => 'QATPB',
                'category' => 'stock_market',
                'risk' => 'high',
                'opening_price' => 20.00,
                'supply' => 75000.0,
                'minimum' => 300.00,
                'maximum' => 15000.00,
                'management_fee' => 1.25,
                'subscription_fee' => 0.75,
                'redemption_fee' => 0.50,
                'lock_days' => 0,
                'duration_days' => 270,
                'return_interval_days' => 30,
                'projection_min' => 9.0,
                'projection_max' => 14.0,
                'user_name' => 'Chioma Okoro',
                'email' => 'qa.invest.partial@rcentz.test',
                'country' => 'Nigeria',
                'funding' => 15000.00,
                'subscribe' => 1200.00,
                'mode' => 'partial',
            ],
        ];

        foreach ($cases as &$case) {
            $case['instrument'] = $this->ensureInstrument($case);
            $case['user'] = $this->ensureUser($case, $password);
            $this->ensureFunding($case['user'], (float) $case['funding'], $case['key']);
        }
        unset($case);

        if (! $this->option('inspect')) {
            try {
                foreach ($cases as &$case) {
                    $this->executeCase($case, $adminId, $orders, $valuation, $lifecycle);
                }
                unset($case);
            } catch (\Throwable $e) {
                $this->error('MS4 Private Investment case execution failed: '.$e->getMessage());
                return self::FAILURE;
            }
        }

        $this->render($cases, $password);
        return self::SUCCESS;
    }

    private function executeCase(
        array &$case,
        int $adminId,
        PrivateInvestmentOrderEngine $orders,
        PrivateInvestmentValuationService $valuation,
        PrivateInvestmentLifecycleService $lifecycle
    ): void {
        /** @var User $user */
        $user = $case['user'];
        /** @var PrivateInvestmentInstrument $instrument */
        $instrument = $case['instrument'];

        $subKey = 'qa-pinv:'.$case['key'].':subscription:v1';
        $subscription = $orders->subscribe(
            $user,
            $instrument,
            (float) $case['subscribe'],
            $user->id,
            'qa_ms4',
            $subKey
        );
        $replayedSubscription = $orders->subscribe(
            $user,
            $instrument,
            (float) $case['subscribe'],
            $user->id,
            'qa_ms4',
            $subKey
        );
        if ((int) $subscription->id !== (int) $replayedSubscription->id) {
            throw new RuntimeException($case['symbol'].' subscription idempotency replay created a second transaction.');
        }
        $case['subscription_replay'] = 'PASS';

        if ($case['mode'] === 'roundtrip') {
            $event = PrivateInvestmentEvent::query()
                ->where('instrument_id', $instrument->id)
                ->where('event_type', 'qa_ms4_growth')
                ->first();
            if (! $event) {
                $valuation->apply($instrument, [
                    'event_type' => 'qa_ms4_growth',
                    'direction' => 'positive',
                    'adjustment_type' => 'percentage',
                    'adjustment_value' => 5.0,
                    'reason' => 'MS4 QA valuation: controlled five percent growth event.',
                ], $adminId);
            }

            $life = PrivateInvestmentLifecycleEvent::query()
                ->where('instrument_id', $instrument->id)
                ->where('reason', 'MS4 QA income distribution')
                ->first();
            if (! $life) {
                $lifecycle->apply($instrument, [
                    'type' => 'distribution',
                    'calculation_mode' => 'percent_current_value',
                    'value' => 1.0,
                    'reason' => 'MS4 QA income distribution',
                ], $adminId);
            }

            $holding = PrivateInvestmentHolding::query()
                ->where('user_id', $user->id)
                ->where('instrument_id', $instrument->id)
                ->firstOrFail();

            $redKey = 'qa-pinv:'.$case['key'].':redemption:v1';
            $redemption = $orders->redeem(
                $user,
                $instrument->fresh(),
                (float) $subscription->units,
                $user->id,
                'qa_ms4',
                $redKey
            );
            $replayedRedemption = $orders->redeem(
                $user,
                $instrument->fresh(),
                (float) $subscription->units,
                $user->id,
                'qa_ms4',
                $redKey
            );
            if ((int) $redemption->id !== (int) $replayedRedemption->id) {
                throw new RuntimeException($case['symbol'].' redemption idempotency replay created a second transaction.');
            }
            $case['redemption_replay'] = 'PASS';
        }

        if ($case['mode'] === 'locked') {
            $holding = PrivateInvestmentHolding::query()
                ->where('user_id', $user->id)
                ->where('instrument_id', $instrument->id)
                ->firstOrFail();

            $case['lock_rejection'] = 'FAIL';
            try {
                $orders->redeem(
                    $user,
                    $instrument,
                    (float) $holding->units,
                    $user->id,
                    'qa_ms4',
                    'qa-pinv:'.$case['key'].':early-redemption:v1'
                );
            } catch (ValidationException $e) {
                $message = implode(' ', collect($e->errors())->flatten()->all());
                if (str_contains(strtolower($message), 'locked until')) {
                    $case['lock_rejection'] = 'PASS';
                } else {
                    throw $e;
                }
            }
            if ($case['lock_rejection'] !== 'PASS') {
                throw new RuntimeException($case['symbol'].' early redemption was not rejected by the lock authority.');
            }
        }

        if ($case['mode'] === 'partial') {
            $life = PrivateInvestmentLifecycleEvent::query()
                ->where('instrument_id', $instrument->id)
                ->where('reason', 'MS4 QA administration deduction')
                ->first();
            if (! $life) {
                $lifecycle->apply($instrument, [
                    'type' => 'deduction',
                    'calculation_mode' => 'percent_current_value',
                    'value' => 0.50,
                    'reason' => 'MS4 QA administration deduction',
                ], $adminId);
            }

            $partialUnits = round((float) $subscription->units / 2, 6);
            $redKey = 'qa-pinv:'.$case['key'].':partial-redemption:v1';
            $redemption = $orders->redeem(
                $user,
                $instrument,
                $partialUnits,
                $user->id,
                'qa_ms4',
                $redKey
            );
            $replayedRedemption = $orders->redeem(
                $user,
                $instrument,
                $partialUnits,
                $user->id,
                'qa_ms4',
                $redKey
            );
            if ((int) $redemption->id !== (int) $replayedRedemption->id) {
                throw new RuntimeException($case['symbol'].' partial redemption replay created a second transaction.');
            }
            $case['redemption_replay'] = 'PASS';
        }
    }

    private function ensureInstrument(array $case): PrivateInvestmentInstrument
    {
        $instrument = PrivateInvestmentInstrument::query()->where('symbol', $case['symbol'])->first();
        if (! $instrument) {
            $instrument = PrivateInvestmentInstrument::query()->create([
                'slug' => 'qa-ms4-'.strtolower($case['symbol']),
                'symbol' => $case['symbol'],
                'name' => $case['name'],
                'category' => $case['category'],
                'description' => 'Synthetic MS4 acceptance instrument. Hidden from public catalogue; all transactions are QA evidence, not performance claims.',
                'risk_level' => $case['risk'],
                'status' => 'active',
                'currency' => 'USD',
                'opening_price' => $case['opening_price'],
                'current_price' => $case['opening_price'],
                'previous_price' => $case['opening_price'],
                'unit_supply' => $case['supply'],
                'available_units' => $case['supply'],
                'minimum_investment' => $case['minimum'],
                'maximum_investment' => $case['maximum'],
                'management_fee_percent' => $case['management_fee'],
                'lock_period_days' => $case['lock_days'],
                'duration_days' => $case['duration_days'],
                'return_interval_days' => $case['return_interval_days'],
                'projected_return_min_percent' => $case['projection_min'],
                'projected_return_max_percent' => $case['projection_max'],
                'subscription_fee_percent' => $case['subscription_fee'],
                'redemption_fee_percent' => $case['redemption_fee'],
                'is_featured' => false,
                'is_visible' => false,
                'last_valued_at' => now(),
            ]);
        }

        if (! PrivateInvestmentPrice::query()->where('instrument_id', $instrument->id)->exists()) {
            PrivateInvestmentPrice::query()->create([
                'instrument_id' => $instrument->id,
                'timeframe' => 'event',
                'open' => $instrument->opening_price,
                'high' => $instrument->opening_price,
                'low' => $instrument->opening_price,
                'close' => $instrument->opening_price,
                'change_amount' => 0,
                'change_percent' => 0,
                'source' => 'qa_ms4_opening_price',
                'recorded_at' => now(),
            ]);
        }

        PrivateInvestmentAsset::query()->updateOrCreate(
            ['instrument_id' => $instrument->id, 'name' => 'MS4 QA Underlying Asset'],
            [
                'asset_type' => $case['category'] === 'real_estate' ? 'property' : ($case['category'] === 'bonds' ? 'fixed_income' : 'equity_basket'),
                'description' => 'Synthetic underlying asset used only for MS4 acceptance.',
                'acquisition_value' => 250000,
                'current_valuation' => 260000,
                'ownership_percentage' => 100,
                'status' => 'active',
                'acquired_at' => now()->toDateString(),
                'effective_at' => now(),
                'notes' => 'QA fixture; not a customer-facing performance claim.',
            ]
        );

        return $instrument->fresh();
    }

    private function ensureUser(array $case, string $password): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => $case['email']],
            [
                'name' => $case['user_name'],
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_admin' => false,
                'country' => $case['country'],
                'currency' => 'USD',
                'account_status' => 'active',
            ]
        );

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $parts = explode(' ', $case['user_name'], 2);
        KYC::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => $parts[0],
                'last_name' => $parts[1] ?? 'QA',
                'date_of_birth' => '1990-01-01',
                'nationality' => $case['country'],
                'document_type' => 'passport',
                'document_number' => 'TEST-MS4-'.strtoupper($case['key']),
                'document_expiry_date' => '2032-01-01',
                'address_line_1' => 'MS4 synthetic QA address',
                'city' => 'Lagos',
                'state_province' => 'Lagos',
                'postal_code' => '100001',
                'country' => $case['country'],
                'phone_number' => '+2348000000400',
                'status' => 'approved',
                'rejection_reason' => null,
                'submitted_at' => now()->subDay(),
                'verified_at' => now()->subDay(),
            ]
        );

        Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'reserved_balance' => 0, 'currency' => 'USD']
        );

        return $user->fresh(['wallet', 'kyc']);
    }

    private function ensureFunding(User $user, float $amount, string $caseKey): void
    {
        $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();
        $reference = 'QA-MS4-FUND-'.strtoupper($caseKey);

        if (WalletTransaction::query()->where('reference_id', $reference)->exists()) {
            return;
        }

        DB::transaction(function () use ($wallet, $amount, $reference) {
            $locked = Wallet::query()->lockForUpdate()->findOrFail($wallet->id);
            if (WalletTransaction::query()->where('reference_id', $reference)->lockForUpdate()->exists()) {
                return;
            }

            $locked->update(['balance' => round((float) $locked->balance + $amount, 2)]);
            WalletTransaction::query()->create([
                'wallet_id' => $locked->id,
                'payment_method_id' => null,
                'type' => 'deposit',
                'direction' => 'credit',
                'amount' => $amount,
                'fee' => 0,
                'status' => 'completed',
                'reference_id' => $reference,
                'description' => 'Synthetic MS4 QA opening capital.',
            ]);
        });
    }

    private function render(array $cases, string $password): void
    {
        $rows = [];
        foreach ($cases as $case) {
            $user = User::query()->where('email', $case['email'])->first();
            $instrument = PrivateInvestmentInstrument::query()->where('symbol', $case['symbol'])->first();
            $holding = ($user && $instrument)
                ? PrivateInvestmentHolding::query()->where('user_id', $user->id)->where('instrument_id', $instrument->id)->first()
                : null;
            $transactions = ($user && $instrument)
                ? PrivateInvestmentTransaction::query()->where('user_id', $user->id)->where('instrument_id', $instrument->id)->where('status', 'completed')->count()
                : 0;

            $rows[] = [
                strtoupper($case['mode']),
                $case['symbol'],
                $case['email'],
                $holding?->status ?? '—',
                $holding ? number_format((float) $holding->units, 6) : '—',
                $transactions,
                $user?->wallet ? number_format((float) $user->wallet->balance, 2) : '—',
                $case['subscription_replay'] ?? 'N/A',
                $case['redemption_replay'] ?? ($case['lock_rejection'] ?? 'N/A'),
            ];
        }

        $this->newLine();
        $this->line('Persistent real Private Investment QA cases');
        $this->line('All @rcentz.test identities and QA instruments are synthetic acceptance fixtures, not performance claims.');
        $this->table(
            ['Case', 'Instrument', 'Customer', 'Holding', 'Units', 'Tx', 'Wallet', 'Sub replay', 'Exit/lock'],
            $rows
        );
        $this->line('Shared QA password: '.$password);
        foreach ($cases as $case) {
            $this->line('  '.$case['email'].' → '.$case['symbol'].' / '.strtoupper($case['mode']));
        }
    }
}
