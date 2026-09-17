<?php

namespace App\Http\Controllers;

use App\Models\VipPlan;
use App\Services\VipAccessService;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    public function index(Request $request, VipAccessService $vipAccess)
    {
        $user = $request->user();
        $vipMembership = $vipAccess->statusMembership($user);
        $activeVipMembership = $vipAccess->activeMembership($user);
        $availableVipPlans = VipPlan::query()->active()->count();

        return view('memberships.index', compact(
            'vipMembership',
            'activeVipMembership',
            'availableVipPlans'
        ));
    }
}
