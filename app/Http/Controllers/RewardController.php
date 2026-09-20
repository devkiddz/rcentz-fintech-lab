<?php

namespace App\Http\Controllers;

use App\Models\RewardCampaign;
use App\Models\RewardGrant;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $campaigns = RewardCampaign::query()
            ->where('is_visible', true)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->orderByDesc('id')
            ->get();

        $grants = RewardGrant::query()
            ->with('campaign')
            ->where('user_id', $user->id)
            ->latest('granted_at')
            ->latest('id')
            ->paginate(20);

        $summary = [
            'cash_total' => round((float) RewardGrant::query()
                ->where('user_id', $user->id)
                ->where('reward_kind', 'cash')
                ->where('status', 'fulfilled')
                ->sum('amount'), 2),
            'cash_grants' => RewardGrant::query()
                ->where('user_id', $user->id)
                ->where('reward_kind', 'cash')
                ->where('status', 'fulfilled')
                ->count(),
            'non_cash_grants' => RewardGrant::query()
                ->where('user_id', $user->id)
                ->where('reward_kind', 'non_cash')
                ->where('status', 'fulfilled')
                ->count(),
        ];

        return view('rewards.index', compact('campaigns', 'grants', 'summary'));
    }
}
