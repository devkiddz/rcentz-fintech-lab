<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VipMembership;
use App\Models\VipPlan;

class MembershipController extends Controller
{
    public function index()
    {
        $stats = [
            'vip_plans' => VipPlan::query()->count(),
            'vip_active_plans' => VipPlan::query()->active()->count(),
            'vip_active_memberships' => VipMembership::query()->activeAt()->count(),
            'vip_pending_memberships' => VipMembership::query()->where('status', 'pending')->count(),
        ];

        return view('admin.memberships.index', compact('stats'));
    }
}
