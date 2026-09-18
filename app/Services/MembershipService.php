<?php

namespace App\Services;

use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class MembershipService
{
    public function create(User $user, MembershipPlan $plan, array $data, User $actor): Membership
    {
        return DB::transaction(function () use ($user, $plan, $data, $actor) {
            $plan->loadMissing('type');

            $membership = Membership::query()->create([
                'user_id' => $user->id,
                'membership_plan_id' => $plan->id,
                'status' => 'pending',
                'price_paid' => $data['price_paid'] ?? $plan->price,
                'currency' => strtoupper($data['currency'] ?? $plan->currency ?? 'USD'),
                'source' => $data['source'] ?? 'admin',
                'reference' => $data['reference'] ?? $this->reference($plan),
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            if (! empty($data['activate_now'])) {
                return $this->activate($membership, $actor);
            }

            return $membership->fresh(['user', 'plan.type', 'plan.entitlements']);
        });
    }

    public function activate(Membership $membership, User $actor): Membership
    {
        return DB::transaction(function () use ($membership, $actor) {
            $membership = Membership::query()
                ->with('plan.type')
                ->lockForUpdate()
                ->findOrFail($membership->id);

            if (! $membership->plan?->is_active || ! $membership->plan?->type?->is_active) {
                throw new RuntimeException('This membership plan or membership type is inactive and cannot be activated.');
            }

            if (in_array($membership->status, ['cancelled', 'expired'], true)) {
                throw new RuntimeException('Cancelled or expired memberships cannot be activated.');
            }

            $startsAt = $membership->starts_at ?? now();
            $endsAt = $membership->ends_at;

            if (! $endsAt && $membership->plan->duration_days) {
                $endsAt = $startsAt->copy()->addDays((int) $membership->plan->duration_days);
            }

            if ($endsAt && ! $endsAt->gt($startsAt)) {
                throw new RuntimeException('Membership end must be after its start time.');
            }

            $typeId = $membership->plan->membership_type_id;

            $overlap = Membership::query()
                ->where('user_id', $membership->user_id)
                ->where('id', '!=', $membership->id)
                ->whereHas('plan', fn ($query) => $query->where('membership_type_id', $typeId))
                ->where('status', 'active')
                ->whereNull('cancelled_at')
                ->whereNull('expired_at')
                ->when($endsAt, function ($query) use ($endsAt) {
                    $query->where(function ($q) use ($endsAt) {
                        $q->whereNull('starts_at')->orWhere('starts_at', '<', $endsAt);
                    });
                })
                ->where(function ($query) use ($startsAt) {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>', $startsAt);
                })
                ->exists();

            if ($overlap) {
                throw new RuntimeException('This customer already has an overlapping active '.$membership->plan->type->name.' membership.');
            }

            $membership->update([
                'status' => 'active',
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'activated_at' => now(),
                'activated_by_user_id' => $actor->id,
                'cancelled_at' => null,
                'expired_at' => null,
            ]);

            return $membership->fresh(['user', 'plan.type', 'plan.entitlements', 'activatedBy']);
        });
    }

    public function cancel(Membership $membership, User $actor): Membership
    {
        return DB::transaction(function () use ($membership, $actor) {
            $membership = Membership::query()->lockForUpdate()->findOrFail($membership->id);

            if (in_array($membership->status, ['cancelled', 'expired'], true)) {
                throw new RuntimeException('This membership is already closed.');
            }

            $membership->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'activated_by_user_id' => $membership->activated_by_user_id ?? $actor->id,
            ]);

            return $membership->fresh(['user', 'plan.type']);
        });
    }

    public function expire(Membership $membership, User $actor): Membership
    {
        return DB::transaction(function () use ($membership, $actor) {
            $membership = Membership::query()->lockForUpdate()->findOrFail($membership->id);

            if ($membership->status === 'expired') {
                return $membership->fresh(['user', 'plan.type']);
            }

            if ($membership->status === 'cancelled') {
                throw new RuntimeException('Cancelled memberships cannot be expired.');
            }

            $now = now();

            $membership->update([
                'status' => 'expired',
                'expired_at' => $now,
                'ends_at' => ! $membership->ends_at || $membership->ends_at->isFuture()
                    ? $now
                    : $membership->ends_at,
                'activated_by_user_id' => $membership->activated_by_user_id ?? $actor->id,
            ]);

            return $membership->fresh(['user', 'plan.type']);
        });
    }

    private function reference(MembershipPlan $plan): string
    {
        $plan->loadMissing('type');
        $prefix = strtoupper(preg_replace('/[^A-Z0-9]/', '', $plan->type?->slug ?? 'MEM')) ?: 'MEM';
        $prefix = substr($prefix, 0, 10);

        do {
            $reference = $prefix . '-' . strtoupper(Str::random(12));
        } while (Membership::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
