<?php

namespace App\Http\Controllers;

use App\Models\VipPlan;
use App\Services\VipAccessService;
use Illuminate\Http\Request;

class VipController extends Controller
{
    public function index(Request $request, VipAccessService $vipAccess)
    {
        $user = $request->user();
        $activeMembership = $vipAccess->activeMembership($user);
        $vipMembership = $activeMembership ?? $vipAccess->latestMembership($user);
        $vipEntitlements = $activeMembership ? $vipAccess->entitlements($user) : collect();

        $plans = VipPlan::query()
            ->active()
            ->with(['entitlements' => fn ($query) => $query->where('enabled', true)->orderBy('id')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('memberships.vip.index', compact(
            'activeMembership',
            'vipMembership',
            'vipEntitlements',
            'plans'
        ));
    }
}
