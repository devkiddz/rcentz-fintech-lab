<?php

namespace App\Console\Commands;

use App\Models\Membership;
use App\Models\MembershipEntitlement;
use App\Models\MembershipPlan;
use App\Models\MembershipTransaction;
use App\Models\MembershipType;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\MembershipAccessService;
use App\Services\MembershipPurchaseService;
use App\Services\MembershipService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SeedRealMembershipCases extends Command
{
    protected $signature = 'membership:seed-ms5-real-cases
        {--password=RcentzQA!2026 : Shared password for synthetic QA users}';

    protected $description = 'Create persistent real MS5 Membership payment/access acceptance cases.';

    private const TYPE_SLUG = 'qa-ms5-access';
    private const PLAN_SLUG = 'qa-ms5-core';
    private const PAID_EMAIL = 'qa.membership.paid@rcentz.test';
    private const DENIED_EMAIL = 'qa.membership.denied@rcentz.test';
    private const ADMIN_EMAIL = 'qa.membership.admin@rcentz.test';
    private const PAID_KEY = 'qa-ms5-membership:paid:v1';
    private const DENIED_KEY = 'qa-ms5-membership:denied:v1';
    private const ADMIN_REFERENCE = 'QA-MS5-ADMIN-COMP-V1';

    public function handle(
        MembershipPurchaseService $purchases,
        MembershipService $memberships,
        MembershipAccessService $access
    ): int {
        $password = (string) $this->option('password');

        if (strlen($password) < 10) {
            $this->error('QA password must be at least 10 characters.');
            return self::FAILURE;
        }

        $admin = User::query()->where('is_admin', true)->first();
        if (! $admin) {
            $this->error('An admin account is required for the manual membership acceptance case.');
            return self::FAILURE;
        }

        [$type, $plan] = $this->ensureCatalog();

        $paid = $this->ensureUser(self::PAID_EMAIL, 'Maya Collins', 1000.00, $password, 'QA-MS5-FUND-PAID-V1');
        $denied = $this->ensureUser(self::DENIED_EMAIL, 'Efe Johnson', 50.00, $password, 'QA-MS5-FUND-DENIED-V1');
        $manual = $this->ensureUser(self::ADMIN_EMAIL, 'Noah Williams', 500.00, $password, 'QA-MS5-FUND-ADMIN-V1');

        $paidPurchase = $purchases->purchase($paid, $plan, self::PAID_KEY, $paid);
        $paidReplay = $purchases->purchase($paid, $plan, self::PAID_KEY, $paid);

        if ((int) $paidPurchase->id !== (int) $paidReplay->id) {
            throw new RuntimeException('Paid membership idempotency replay produced a different transaction.');
        }

        $deniedBefore = (float) $denied->wallet()->firstOrFail()->balance;
        $deniedRejected = false;

        try {
            $purchases->purchase($denied, $plan, self::DENIED_KEY, $denied);
        } catch (\Throwable $e) {
            if (str_contains(strtolower($e->getMessage()), 'insufficient')) {
                $deniedRejected = true;
            } else {
                throw $e;
            }
        }

        if (! $deniedRejected) {
            throw new RuntimeException('Insufficient-funds membership purchase was not rejected.');
        }

        $denied->load('wallet');
        if (abs((float) $denied->wallet->balance - $deniedBefore) > 0.001) {
            throw new RuntimeException('Rejected membership purchase changed the customer wallet balance.');
        }

        if (MembershipTransaction::query()
            ->where('user_id', $denied->id)
            ->where('idempotency_key', self::DENIED_KEY)
            ->exists()
        ) {
            throw new RuntimeException('Rejected membership purchase created a completed commercial receipt.');
        }

        $manualMembership = Membership::query()
            ->where('user_id', $manual->id)
            ->where('membership_plan_id', $plan->id)
            ->where('reference', self::ADMIN_REFERENCE)
            ->first();

        if (! $manualMembership) {
            $manualMembership = $memberships->create($manual, $plan, [
                'price_paid' => 0,
                'currency' => 'USD',
                'source' => 'admin',
                'reference' => self::ADMIN_REFERENCE,
                'activate_now' => true,
                'metadata' => [
                    'qa_fixture' => true,
                    'grant_reason' => 'MS5 complimentary/admin authority acceptance',
                ],
            ], $admin);
        } elseif (! $manualMembership->is_active) {
            $manualMembership = $memberships->activate($manualMembership, $admin);
        }

        if (MembershipTransaction::query()->where('membership_id', $manualMembership->id)->exists()) {
            throw new RuntimeException('Manual admin membership unexpectedly owns a paid MembershipTransaction.');
        }

        $paidMembership = $paidPurchase->membership()->with('plan.type')->firstOrFail();
        $paidAccess = $access->has($paid, $type, 'signals.access')
            && $access->has($paid, $type, 'bot_trader.access')
            && $access->has($paid, $type, 'copy_trader.access')
            && $access->has($paid, $type, 'investments.access');

        $manualAccess = $access->has($manual, $type, 'signals.access')
            && $access->has($manual, $type, 'bot_trader.access')
            && $access->has($manual, $type, 'copy_trader.access')
            && $access->has($manual, $type, 'investments.access');

        if (! $paidMembership->is_active || ! $paidAccess || ! $manualMembership->is_active || ! $manualAccess) {
            throw new RuntimeException('MembershipAccessService did not resolve the expected active entitlements.');
        }

        $paid->load('wallet');
        $manual->load('wallet');

        $this->line('Persistent real Membership QA cases');
        $this->line('All @rcentz.test identities and the QA Membership type are synthetic acceptance fixtures.');

        $this->table(
            ['Case', 'Customer', 'Source', 'Membership', 'Payment', 'Wallet', 'Access', 'Replay'],
            [
                [
                    'PAID ACTIVE',
                    self::PAID_EMAIL,
                    $paidMembership->source,
                    strtoupper($paidMembership->status),
                    'USD '.number_format((float) $paidPurchase->amount, 2),
                    number_format((float) $paid->wallet->balance, 2),
                    $paidAccess ? 'PASS' : 'FAIL',
                    (int) $paidPurchase->id === (int) $paidReplay->id ? 'PASS' : 'FAIL',
                ],
                [
                    'PAYMENT DENIED',
                    self::DENIED_EMAIL,
                    'wallet_purchase',
                    'NONE',
                    'REJECTED',
                    number_format((float) $denied->wallet->balance, 2),
                    $access->isMember($denied, $type) ? 'FAIL' : 'PASS',
                    'N/A',
                ],
                [
                    'ADMIN COMP',
                    self::ADMIN_EMAIL,
                    $manualMembership->source,
                    strtoupper($manualMembership->status),
                    'MANUAL',
                    number_format((float) $manual->wallet->balance, 2),
                    $manualAccess ? 'PASS' : 'FAIL',
                    'N/A',
                ],
            ]
        );

        $this->line('Shared QA password: '.$password);
        $this->line('  '.self::PAID_EMAIL.' → paid active membership');
        $this->line('  '.self::DENIED_EMAIL.' → insufficient-funds rejection');
        $this->line('  '.self::ADMIN_EMAIL.' → complimentary admin-issued membership');

        return self::SUCCESS;
    }

    private function ensureCatalog(): array
    {
        $type = MembershipType::query()->updateOrCreate(
            ['slug' => self::TYPE_SLUG],
            [
                'name' => 'QA MS5 Access Membership',
                'description' => 'Synthetic Membership authority used only for persistent MS5 acceptance cases.',
                'icon' => 'shield-check',
                'is_active' => true,
                'sort_order' => 999,
                'metadata' => ['qa_fixture' => true, 'milestone' => 'MS5'],
            ]
        );

        $plan = MembershipPlan::query()->updateOrCreate(
            ['slug' => self::PLAN_SLUG],
            [
                'membership_type_id' => $type->id,
                'name' => 'QA Core Access',
                'description' => 'Synthetic 30-day paid plan used to prove Membership payment and entitlement authority.',
                'price' => 120.00,
                'currency' => 'USD',
                'billing_interval' => 'monthly',
                'duration_days' => 30,
                'is_active' => true,
                'sort_order' => 999,
                'metadata' => ['qa_fixture' => true, 'milestone' => 'MS5'],
            ]
        );

        $entitlements = [
            'signals.access' => ['Signals access', 'Access contract for the Signals domain.'],
            'bot_trader.access' => ['Bot Trader access', 'Access contract for the Bot Trader domain.'],
            'copy_trader.access' => ['Copy Trading access', 'Access contract for the Copy Trading domain.'],
            'investments.access' => ['Private Investments access', 'Access contract for the Private Investments domain.'],
        ];

        foreach ($entitlements as $key => [$label, $description]) {
            MembershipEntitlement::query()->updateOrCreate(
                ['membership_plan_id' => $plan->id, 'key' => $key],
                [
                    'label' => $label,
                    'description' => $description,
                    'value' => ['allowed' => true],
                    'enabled' => true,
                ]
            );
        }

        return [$type->fresh(), $plan->fresh(['type', 'entitlements'])];
    }

    private function ensureUser(
        string $email,
        string $name,
        float $funding,
        string $password,
        string $fundingReference
    ): User {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_admin' => false,
                'country' => 'Nigeria',
                'currency' => 'USD',
                'account_status' => 'active',
            ]
        );

        $user->forceFill([
            'name' => $name,
            'password' => Hash::make($password),
            'email_verified_at' => $user->email_verified_at ?? now(),
            'is_admin' => false,
            'country' => 'Nigeria',
            'currency' => 'USD',
            'account_status' => 'active',
        ])->save();

        $wallet = Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'reserved_balance' => 0, 'currency' => 'USD']
        );

        DB::transaction(function () use ($wallet, $funding, $fundingReference) {
            $locked = Wallet::query()->lockForUpdate()->findOrFail($wallet->id);

            if (WalletTransaction::query()->where('reference_id', $fundingReference)->exists()) {
                return;
            }

            $locked->update([
                'balance' => round((float) $locked->balance + $funding, 2),
                'currency' => 'USD',
            ]);

            WalletTransaction::query()->create([
                'wallet_id' => $locked->id,
                'payment_method_id' => null,
                'type' => 'deposit',
                'direction' => 'credit',
                'amount' => $funding,
                'fee' => 0,
                'status' => 'completed',
                'reference_id' => $fundingReference,
                'description' => 'Synthetic MS5 Membership QA funding.',
            ]);
        });

        return $user->fresh('wallet');
    }
}
