<?php

namespace App\Console\Commands;

use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\MembershipTransaction;
use App\Models\MembershipType;
use App\Models\User;
use App\Services\MembershipAccessService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InspectMembershipAcceptance extends Command
{
    protected $signature = 'membership:inspect-ms5-acceptance';
    protected $description = 'Inspect MS5 Membership payment, activation and entitlement authority.';

    private const TYPE_SLUG = 'qa-ms5-access';
    private const PLAN_SLUG = 'qa-ms5-core';
    private const PAID_EMAIL = 'qa.membership.paid@rcentz.test';
    private const DENIED_EMAIL = 'qa.membership.denied@rcentz.test';
    private const ADMIN_EMAIL = 'qa.membership.admin@rcentz.test';
    private const PAID_KEY = 'qa-ms5-membership:paid:v1';

    public function handle(MembershipAccessService $access): int
    {
        foreach (['membership_types', 'membership_plans', 'membership_entitlements', 'memberships', 'membership_transactions'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->error("Missing {$table}. Run migrations first.");
                return self::FAILURE;
            }
        }

        $type = MembershipType::query()->where('slug', self::TYPE_SLUG)->first();
        $plan = MembershipPlan::query()->where('slug', self::PLAN_SLUG)->first();

        if (! $type || ! $plan) {
            $this->error('MS5 QA Membership catalog is missing. Run membership:seed-ms5-real-cases first.');
            return self::FAILURE;
        }

        $failed = false;
        $rows = [];

        foreach ([
            ['PAID ACTIVE', self::PAID_EMAIL],
            ['PAYMENT DENIED', self::DENIED_EMAIL],
            ['ADMIN COMP', self::ADMIN_EMAIL],
        ] as [$case, $email]) {
            $user = User::query()->where('email', $email)->with('wallet')->first();

            if (! $user) {
                $rows[] = [$case, $email, 'MISSING', '—', '—', 'FAIL'];
                $failed = true;
                continue;
            }

            $membership = Membership::query()
                ->with('plan.type')
                ->where('user_id', $user->id)
                ->whereHas('plan', fn ($query) => $query->where('membership_type_id', $type->id))
                ->latest('id')
                ->first();

            $transaction = MembershipTransaction::query()
                ->with('walletTransaction')
                ->where('user_id', $user->id)
                ->latest('id')
                ->first();

            $hasAllAccess = collect([
                'signals.access',
                'bot_trader.access',
                'copy_trader.access',
                'investments.access',
            ])->every(fn ($key) => $access->has($user, $type, $key));

            $casePass = match ($case) {
                'PAID ACTIVE' =>
                    $membership?->is_active
                    && $membership->source === 'wallet_purchase'
                    && $transaction?->status === 'completed'
                    && $transaction->idempotency_key === self::PAID_KEY
                    && $transaction->walletTransaction?->type === 'membership'
                    && $transaction->walletTransaction?->direction === 'debit'
                    && $hasAllAccess,
                'PAYMENT DENIED' =>
                    $membership === null
                    && $transaction === null
                    && ! $access->isMember($user, $type)
                    && abs((float) $user->wallet?->balance - 50.00) < 0.01,
                'ADMIN COMP' =>
                    $membership?->is_active
                    && $membership->source === 'admin'
                    && $transaction === null
                    && $hasAllAccess
                    && abs((float) $user->wallet?->balance - 500.00) < 0.01,
                default => false,
            };

            if (! $casePass) {
                $failed = true;
            }

            $rows[] = [
                $case,
                $email,
                $membership?->status ?? 'NONE',
                $membership?->source ?? '—',
                $transaction?->status ?? '—',
                $casePass ? 'PASS' : 'FAIL',
            ];
        }

        $this->line('Persistent Membership acceptance cases:');
        $this->table(
            ['Case', 'Customer', 'Membership', 'Source', 'Payment', 'Result'],
            $rows
        );

        $checks = [
            'Active paid memberships missing completed commercial receipt' => DB::table('memberships as m')
                ->leftJoin('membership_transactions as mt', function ($join) {
                    $join->on('mt.membership_id', '=', 'm.id')
                        ->where('mt.status', '=', 'completed');
                })
                ->where('m.source', 'wallet_purchase')
                ->where('m.status', 'active')
                ->whereNull('mt.id')
                ->count(),

            'Paid receipts missing valid membership wallet debit' => DB::table('membership_transactions as mt')
                ->leftJoin('wallet_transactions as wt', 'wt.id', '=', 'mt.wallet_transaction_id')
                ->where('mt.status', 'completed')
                ->where('mt.amount', '>', 0)
                ->where(function ($query) {
                    $query->whereNull('wt.id')
                        ->orWhere('wt.type', '!=', 'membership')
                        ->orWhere('wt.direction', '!=', 'debit')
                        ->orWhere('wt.status', '!=', 'completed')
                        ->orWhereColumn('wt.reference_id', '!=', 'mt.reference')
                        ->orWhereRaw('ABS(wt.amount - mt.amount) > 0.01');
                })
                ->count(),

            'Paid receipt / membership authority mismatch' => DB::table('membership_transactions as mt')
                ->join('memberships as m', 'm.id', '=', 'mt.membership_id')
                ->where('mt.status', 'completed')
                ->where(function ($query) {
                    $query->whereColumn('mt.user_id', '!=', 'm.user_id')
                        ->orWhereColumn('mt.membership_plan_id', '!=', 'm.membership_plan_id')
                        ->orWhereColumn('mt.reference', '!=', 'm.reference')
                        ->orWhereRaw('ABS(mt.amount - m.price_paid) > 0.01');
                })
                ->count(),

            'Duplicate user/idempotency keys' => DB::table('membership_transactions')
                ->selectRaw('user_id, idempotency_key, COUNT(*) as total')
                ->groupBy('user_id', 'idempotency_key')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count(),

            'QA catalog exposed as non-QA data' => (
                data_get($type->metadata, 'qa_fixture', false)
                && data_get($plan->metadata, 'qa_fixture', false)
            ) ? 0 : 1,
        ];

        $this->newLine();
        $this->table(
            ['MS5 Membership authority check', 'Count'],
            collect($checks)->map(fn ($count, $label) => [$label, $count])->values()->all()
        );

        if ($failed || collect($checks)->contains(fn ($count) => (int) $count > 0)) {
            $this->error('MS5_MEMBERSHIP_ACCEPTANCE_FAILED');
            return self::FAILURE;
        }

        $this->info(
            'MS5 Membership is READY with wallet-backed paid activation, explicit admin grants, entitlement authority and replay-safe purchase evidence.'
        );
        return self::SUCCESS;
    }
}
