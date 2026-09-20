<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RewardAuditLog;
use App\Models\RewardCampaign;
use App\Models\RewardGrant;
use App\Models\User;
use App\Services\RewardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RewardController extends Controller
{
    public function index()
    {
        $campaigns = RewardCampaign::query()->withCount('grants')->latest('id')->get();
        $grants = RewardGrant::query()->with(['campaign', 'user', 'grantedBy'])->latest('granted_at')->limit(50)->get();
        $users = User::query()->where('is_admin', false)->orderBy('name')->get(['id', 'name', 'email']);

        $stats = [
            'campaigns' => RewardCampaign::count(),
            'active_campaigns' => RewardCampaign::where('status', 'active')->count(),
            'fulfilled_grants' => RewardGrant::where('status', 'fulfilled')->count(),
            'cash_distributed' => round((float) RewardGrant::where('status', 'fulfilled')->where('reward_kind', 'cash')->sum('amount'), 2),
        ];

        return view('admin.rewards.index', compact('campaigns', 'grants', 'users', 'stats'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'slug' => Str::slug($request->input('slug') ?: $request->input('name', '')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:120', Rule::unique('reward_campaigns', 'slug')],
            'campaign_type' => ['required', Rule::in(['bonus', 'referral', 'giveaway', 'promotion'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'reward_kind' => ['required', Rule::in(['cash', 'non_cash'])],
            'cash_amount' => ['nullable', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'size:3'],
            'non_cash_label' => ['nullable', 'string', 'max:255'],
            'eligibility_key' => ['nullable', 'string', 'max:120'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'max_grants' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'is_visible' => ['nullable', 'boolean'],
        ]);

        if ($data['reward_kind'] === 'cash' && empty($data['cash_amount'])) {
            return back()->withInput()->withErrors(['cash_amount' => 'Cash campaigns require a reward amount.']);
        }
        if ($data['reward_kind'] === 'non_cash' && blank($data['non_cash_label'] ?? null)) {
            return back()->withInput()->withErrors(['non_cash_label' => 'Non-cash campaigns require a fulfillment label.']);
        }

        $campaign = RewardCampaign::query()->create([
            ...$data,
            'slug' => $data['slug'],
            'currency' => strtoupper($data['currency'] ?? 'USD'),
            'status' => 'active',
            'per_user_limit' => $data['per_user_limit'] ?? 1,
            'is_visible' => $request->boolean('is_visible'),
        ]);

        RewardAuditLog::query()->create([
            'reward_campaign_id' => $campaign->id,
            'actor_user_id' => Auth::id(),
            'action' => 'campaign.created',
            'reference' => 'CAMPAIGN-'.$campaign->id,
            'reason' => 'Reward campaign created by administrator.',
            'occurred_at' => now(),
        ]);

        return back()->with('success', 'Reward campaign created.');
    }

    public function grant(Request $request, RewardCampaign $campaign, RewardService $rewards)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'source_type' => ['required', 'string', 'max:60'],
            'source_reference' => ['required', 'string', 'max:120'],
        ]);

        $user = User::query()->where('is_admin', false)->findOrFail($data['user_id']);
        $eligibility = $campaign->eligibility_key ? [$campaign->eligibility_key => true] : [];

        try {
            $rewards->grant(
                $user,
                $campaign,
                $data['source_type'],
                $data['source_reference'],
                Auth::user(),
                ['eligibility' => $eligibility, 'manual_admin_grant' => true]
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['reward' => $e->getMessage()]);
        }

        return back()->with('success', 'Reward granted to '.$user->name.'.');
    }

    public function toggle(RewardCampaign $campaign)
    {
        if ($campaign->status === 'closed') {
            return back()->withErrors(['campaign' => 'Closed reward campaigns cannot be reopened from the quick toggle.']);
        }

        $campaign->update(['status' => $campaign->status === 'active' ? 'paused' : 'active']);

        RewardAuditLog::query()->create([
            'reward_campaign_id' => $campaign->id,
            'actor_user_id' => Auth::id(),
            'action' => 'campaign.status_changed',
            'reference' => 'CAMPAIGN-'.$campaign->id,
            'reason' => 'Reward campaign status changed to '.$campaign->status.'.',
            'occurred_at' => now(),
        ]);

        return back()->with('success', 'Reward campaign status updated.');
    }
}
