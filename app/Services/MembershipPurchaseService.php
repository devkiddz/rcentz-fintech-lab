<?php

namespace App\Services;

use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\MembershipTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class MembershipPurchaseService
{
    public function __construct(
        private MembershipService $memberships,
        private FinancialActivityService $activity
    ) {}

    public function purchase(
        User $user,
        MembershipPlan $plan,
        string $idempotencyKey,
        ?User $actor = null
    ): MembershipTransaction {
        $idempotencyKey = trim($idempotencyKey);
        $actor ??= $user;

        if ($idempotencyKey === '' || strlen($idempotencyKey) > 120) {
            throw new InvalidArgumentException('A valid membership purchase idempotency key is required.');
        }

        $existing = MembershipTransaction::query()
            ->with(['membership.plan.type', 'walletTransaction'])
            ->where('user_id', $user->id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            $this->assertSameRequest($existing, $plan);
            return $existing;
        }

        try {
            return DB::transaction(function () use ($user, $plan, $idempotencyKey, $actor) {
                $plan = MembershipPlan::query()
                    ->with('type')
                    ->lockForUpdate()
                    ->findOrFail($plan->id);

                $existing = MembershipTransaction::query()
                    ->with(['membership.plan.type', 'walletTransaction'])
                    ->where('user_id', $user->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    $this->assertSameRequest($existing, $plan);
                    return $existing;
                }

                if (! $plan->is_active || ! $plan->type?->is_active) {
                    throw new RuntimeException('This membership plan is not currently available.');
                }

                $activeSameType = Membership::query()
                    ->where('user_id', $user->id)
                    ->whereHas('plan', fn ($query) => $query->where('membership_type_id', $plan->membership_type_id))
                    ->activeAt()
                    ->exists();

                if ($activeSameType) {
                    throw new RuntimeException(
                        'You already have an active '.$plan->type->name.' membership.'
                    );
                }

                $wallet = Wallet::query()
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if (! $wallet) {
                    throw new RuntimeException('A customer wallet is required to purchase membership.');
                }

                $currency = strtoupper((string) ($plan->currency ?: 'USD'));
                $walletCurrency = strtoupper((string) ($wallet->currency ?: 'USD'));

                if ($currency !== $walletCurrency) {
                    throw new RuntimeException(
                        "Membership is priced in {$currency}, but this wallet settles in {$walletCurrency}."
                    );
                }

                $amount = round((float) $plan->price, 2);

                if ($amount < 0) {
                    throw new RuntimeException('Membership price cannot be negative.');
                }

                if ($wallet->available_balance + 0.000001 < $amount) {
                    throw new RuntimeException('Insufficient available wallet balance for this membership.');
                }

                $reference = $this->reference($plan);
                $before = $this->activity->snapshot($wallet);

                $membership = $this->memberships->create($user, $plan, [
                    'price_paid' => $amount,
                    'currency' => $currency,
                    'source' => 'wallet_purchase',
                    'reference' => $reference,
                    'activate_now' => false,
                    'metadata' => [
                        'purchase_idempotency_key' => $idempotencyKey,
                        'commercial_authority' => 'membership_transaction',
                    ],
                ], $actor);

                $walletTransaction = null;

                if ($amount > 0) {
                    $walletTransaction = WalletTransaction::query()->create([
                        'wallet_id' => $wallet->id,
                        'payment_method_id' => null,
                        'type' => 'membership',
                        'direction' => 'debit',
                        'amount' => $amount,
                        'fee' => 0,
                        'status' => 'completed',
                        'reference_id' => $reference,
                        'description' => 'Membership purchase: '.$plan->type->name.' / '.$plan->name,
                    ]);

                    $wallet->deductFunds($amount);
                }

                $transaction = MembershipTransaction::query()->create([
                    'user_id' => $user->id,
                    'membership_plan_id' => $plan->id,
                    'membership_id' => $membership->id,
                    'wallet_transaction_id' => $walletTransaction?->id,
                    'type' => 'purchase',
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => 'completed',
                    'reference' => $reference,
                    'idempotency_key' => $idempotencyKey,
                    'source' => 'wallet_purchase',
                    'metadata' => [
                        'actor_user_id' => $actor->id,
                        'membership_type_id' => $plan->membership_type_id,
                    ],
                    'processed_at' => now(),
                ]);

                $membership->update([
                    'metadata' => array_merge((array) ($membership->metadata ?? []), [
                        'membership_transaction_id' => $transaction->id,
                        'wallet_transaction_id' => $walletTransaction?->id,
                    ]),
                ]);

                $this->memberships->activate($membership, $actor);

                $this->activity->record(
                    $user,
                    'membership.purchase',
                    'Membership purchased',
                    'Purchased '.$plan->type->name.' / '.$plan->name.'.',
                    $reference,
                    'completed',
                    $amount > 0 ? 'debit' : null,
                    $amount,
                    $wallet,
                    $walletTransaction,
                    null,
                    $before,
                    [
                        'membership_id' => $membership->id,
                        'membership_plan_id' => $plan->id,
                        'membership_transaction_id' => $transaction->id,
                        'membership_type_id' => $plan->membership_type_id,
                    ],
                    'user',
                    $actor->id
                );

                return $transaction->fresh([
                    'membership.plan.type',
                    'membership.plan.entitlements',
                    'walletTransaction',
                ]);
            });
        } catch (QueryException $e) {
            $existing = MembershipTransaction::query()
                ->with(['membership.plan.type', 'walletTransaction'])
                ->where('user_id', $user->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if (! $existing) {
                throw $e;
            }

            $this->assertSameRequest($existing, $plan);
            return $existing;
        }
    }

    private function assertSameRequest(MembershipTransaction $transaction, MembershipPlan $plan): void
    {
        if (
            (int) $transaction->membership_plan_id !== (int) $plan->id
            || $transaction->type !== 'purchase'
        ) {
            throw new RuntimeException(
                'This idempotency key was already used for a different membership purchase.'
            );
        }
    }

    private function reference(MembershipPlan $plan): string
    {
        $plan->loadMissing('type');

        $prefix = strtoupper(preg_replace('/[^A-Z0-9]/', '', $plan->type?->slug ?? 'MEM')) ?: 'MEM';
        $prefix = substr($prefix, 0, 8);

        do {
            $reference = 'MEM-'.$prefix.'-'.strtoupper(Str::random(12));
        } while (
            MembershipTransaction::query()->where('reference', $reference)->exists()
            || Membership::query()->where('reference', $reference)->exists()
        );

        return $reference;
    }
}
