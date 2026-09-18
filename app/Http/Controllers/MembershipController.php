<?php

namespace App\Http\Controllers;

use App\Services\MembershipAccessService;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    public function index(Request $request, MembershipAccessService $membershipAccess)
    {
        $user = $request->user();

        $activeMemberships = $membershipAccess->activeMemberships($user);
        $membershipStatuses = $membershipAccess->statusMemberships($user);
        $currentMembership = $activeMemberships->first() ?? $membershipStatuses->first();

        if ($currentMembership) {
            $currentMembership->loadMissing(['plan.type', 'plan.entitlements']);
        }

        $privileges = collect();

        if ($currentMembership?->is_active && $currentMembership->plan) {
            $privileges = $currentMembership->plan->entitlements
                ->where('enabled', true)
                ->values();
        }

        return view('memberships.index', compact(
            'currentMembership',
            'privileges'
        ));
    }
}
