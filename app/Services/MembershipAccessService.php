<?php

namespace App\Services;

use App\Models\Membership;
use App\Models\MembershipEntitlement;
use App\Models\MembershipType;
use App\Models\User;
use Illuminate\Support\Collection;

class MembershipAccessService
{
    public function activeMembership(User $user, string|MembershipType $type, $at = null): ?Membership
    {
        $slug = $this->typeSlug($type);

        if ($at === null && $user->relationLoaded('memberships')) {
            return $user->memberships
                ->filter(fn (Membership $membership) =>
                    $membership->plan?->type?->slug === $slug
                    && $membership->plan?->is_active
                    && $membership->plan?->type?->is_active
                    && $membership->is_active
                )
                ->sortByDesc('id')
                ->first();
        }

        return Membership::query()
            ->with(['plan.type', 'plan.entitlements'])
            ->where('user_id', $user->id)
            ->whereHas('plan', function ($query) use ($slug) {
                $query->where('is_active', true)
                    ->whereHas('type', fn ($typeQuery) => $typeQuery
                        ->where('slug', $slug)
                        ->where('is_active', true));
            })
            ->activeAt($at)
            ->latest('starts_at')
            ->latest('id')
            ->first();
    }

    public function latestMembership(User $user, string|MembershipType $type): ?Membership
    {
        $slug = $this->typeSlug($type);

        if ($user->relationLoaded('memberships')) {
            return $user->memberships
                ->filter(fn (Membership $membership) => $membership->plan?->type?->slug === $slug)
                ->sortByDesc('id')
                ->first();
        }

        return Membership::query()
            ->with(['plan.type', 'plan.entitlements'])
            ->where('user_id', $user->id)
            ->whereHas('plan.type', fn ($query) => $query->where('slug', $slug))
            ->latest('id')
            ->first();
    }

    public function statusMembership(User $user, string|MembershipType $type): ?Membership
    {
        return $this->activeMembership($user, $type) ?? $this->latestMembership($user, $type);
    }

    public function activeMemberships(User $user, $at = null): Collection
    {
        $memberships = $at === null && $user->relationLoaded('memberships')
            ? $user->memberships
            : Membership::query()
                ->with(['plan.type', 'plan.entitlements'])
                ->where('user_id', $user->id)
                ->activeAt($at)
                ->get();

        return $memberships
            ->filter(fn (Membership $membership) =>
                $membership->plan?->type
                && $membership->plan->is_active
                && $membership->plan->type->is_active
                && ($at !== null || $membership->is_active)
            )
            ->sortByDesc('id')
            ->unique(fn (Membership $membership) => $membership->plan->type->slug)
            ->keyBy(fn (Membership $membership) => $membership->plan->type->slug);
    }

    public function statusMemberships(User $user): Collection
    {
        $memberships = $user->relationLoaded('memberships')
            ? $user->memberships
            : Membership::query()
                ->with(['plan.type', 'plan.entitlements'])
                ->where('user_id', $user->id)
                ->get();

        return $memberships
            ->filter(fn (Membership $membership) => $membership->plan?->type)
            ->groupBy(fn (Membership $membership) => $membership->plan->type->slug)
            ->map(function (Collection $group) {
                $active = $group
                    ->filter(fn (Membership $membership) =>
                        $membership->plan?->is_active
                        && $membership->plan?->type?->is_active
                        && $membership->is_active
                    )
                    ->sortByDesc('id')
                    ->first();

                return $active ?? $group->sortByDesc('id')->first();
            })
            ->filter();
    }

    public function isMember(User $user, string|MembershipType $type, $at = null): bool
    {
        return $this->activeMembership($user, $type, $at) !== null;
    }

    public function entitlement(User $user, string|MembershipType $type, string $key, $at = null): ?MembershipEntitlement
    {
        $membership = $this->activeMembership($user, $type, $at);

        if (! $membership?->plan) return null;

        return $membership->plan->entitlements
            ->first(fn (MembershipEntitlement $entitlement) =>
                $entitlement->enabled && $entitlement->key === $key
            );
    }

    public function has(User $user, string|MembershipType $type, string $key, $at = null): bool
    {
        return $this->entitlement($user, $type, $key, $at) !== null;
    }

    public function value(User $user, string|MembershipType $type, string $key, $default = null, $at = null)
    {
        return $this->entitlement($user, $type, $key, $at)?->value ?? $default;
    }

    public function entitlements(User $user, string|MembershipType $type, $at = null): Collection
    {
        $membership = $this->activeMembership($user, $type, $at);

        if (! $membership?->plan) return collect();

        return $membership->plan->entitlements
            ->where('enabled', true)
            ->values();
    }

    private function typeSlug(string|MembershipType $type): string
    {
        return $type instanceof MembershipType ? $type->slug : $type;
    }
}
