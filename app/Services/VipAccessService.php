<?php

namespace App\Services;

use App\Models\User;
use App\Models\VipEntitlement;
use App\Models\VipMembership;
use Illuminate\Support\Collection;

class VipAccessService
{
    public function activeMembership(User $user, $at = null): ?VipMembership
    {
        return VipMembership::query()
            ->with(['plan.entitlements'])
            ->where('user_id', $user->id)
            ->whereHas('plan', fn ($query) => $query->where('is_active', true))
            ->activeAt($at)
            ->latest('starts_at')
            ->latest('id')
            ->first();
    }

    public function isVip(User $user, $at = null): bool
    {
        return $this->activeMembership($user, $at) !== null;
    }

    public function entitlement(User $user, string $key, $at = null): ?VipEntitlement
    {
        $membership = $this->activeMembership($user, $at);

        if (! $membership?->plan) return null;

        return $membership->plan->entitlements
            ->first(fn (VipEntitlement $entitlement) =>
                $entitlement->enabled && $entitlement->key === $key
            );
    }

    public function has(User $user, string $key, $at = null): bool
    {
        return $this->entitlement($user, $key, $at) !== null;
    }

    public function value(User $user, string $key, $default = null, $at = null)
    {
        return $this->entitlement($user, $key, $at)?->value ?? $default;
    }

    public function entitlements(User $user, $at = null): Collection
    {
        $membership = $this->activeMembership($user, $at);

        if (! $membership?->plan) return collect();

        return $membership->plan->entitlements
            ->where('enabled', true)
            ->values();
    }
}
