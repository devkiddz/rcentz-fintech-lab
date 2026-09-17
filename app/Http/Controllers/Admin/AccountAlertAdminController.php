<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountAlert;
use App\Models\User;
use App\Models\WithdrawalTokenRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountAlertAdminController extends Controller
{
    public function index(User $user)
    {
        abort_if($user->is_admin, 422, 'Direct customer alerts are not intended for admin accounts.');

        $alerts = $user->accountAlerts()->latest()->limit(40)->get();

        return view('admin.users.alerts', compact('user', 'alerts'));
    }

    public function store(Request $request, User $user)
    {
        abort_if($user->is_admin, 422, 'Direct customer alerts are not intended for admin accounts.');

        $data = $request->validate([
            'title' => ['required','string','max:160'],
            'message' => ['required','string','max:3000'],
            'priority' => ['required', Rule::in(['normal','important','urgent'])],
            'action_url' => ['nullable','string','max:255'],
            'action_label' => ['nullable','string','max:80'],
            'expires_at' => ['nullable','date','after:now'],
            'withdrawal_token_request_id' => ['nullable','integer'],
        ]);

        $withdrawalRequest = null;
        if (! empty($data['withdrawal_token_request_id'])) {
            $withdrawalRequest = WithdrawalTokenRequest::query()
                ->where('user_id', $user->id)
                ->whereKey($data['withdrawal_token_request_id'])
                ->where('status', 'token_issued')
                ->firstOrFail();
        }

        $alert = AccountAlert::query()->create([
            'user_id' => $user->id,
            'created_by_user_id' => auth()->id(),
            'type' => $withdrawalRequest ? 'withdrawal_token' : 'direct',
            'priority' => $data['priority'],
            'title' => $data['title'],
            'message' => $data['message'],
            'action_url' => $data['action_url'] ?? null,
            'action_label' => $data['action_label'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'metadata' => $withdrawalRequest ? [
                'withdrawal_token_request_id' => $withdrawalRequest->id,
                'amount' => (string) $withdrawalRequest->amount,
            ] : null,
        ]);

        if ($withdrawalRequest) {
            $withdrawalRequest->update(['account_alert_id' => $alert->id]);
        }

        return back()->with('success', 'Private dashboard alert sent to '.$user->name.'.');
    }
}
