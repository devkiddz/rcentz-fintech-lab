<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountAlert;
use App\Models\WithdrawalTokenRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class WithdrawalTokenRequestController extends Controller
{
    public function index()
    {
        $requests = WithdrawalTokenRequest::query()
            ->with(['user.wallet', 'generator'])
            ->latest()
            ->paginate(30);

        return view('admin.withdrawal-token-requests.index', compact('requests'));
    }

    public function generate(WithdrawalTokenRequest $tokenRequest)
    {
        abort_unless(in_array($tokenRequest->status, ['pending','token_issued'], true), 422);

        $token = (string) random_int(100000, 999999);

        DB::transaction(function () use ($tokenRequest, $token) {
            if ($tokenRequest->alert) {
                $tokenRequest->alert->update(['dismissed_at' => now()]);
            }

            $alert = AccountAlert::query()->create([
                'user_id' => $tokenRequest->user_id,
                'created_by_user_id' => auth()->id(),
                'type' => 'withdrawal_token',
                'priority' => 'urgent',
                'title' => 'Withdrawal verification token',
                'message' => 'Your withdrawal verification code is '.$token.'. It expires in 30 minutes.',
                'action_url' => route('wallet.withdrawal.token.form', $tokenRequest),
                'action_label' => 'Continue withdrawal',
                'expires_at' => now()->addMinutes(30),
                'metadata' => [
                    'withdrawal_token_request_id' => $tokenRequest->id,
                    'amount' => (string) $tokenRequest->amount,
                ],
            ]);

            $tokenRequest->update([
                'status' => 'token_issued',
                'token_hash' => Hash::make($token),
                'token_last_four' => substr($token, -4),
                'token_generated_at' => now(),
                'token_expires_at' => now()->addMinutes(30),
                'token_verified_at' => null,
                'generated_by_user_id' => auth()->id(),
                'account_alert_id' => $alert->id,
            ]);
        });

        return back()->with('success', '30-minute withdrawal token generated and pushed to the customer dashboard alert window.');
    }

    public function destroyToken(WithdrawalTokenRequest $tokenRequest)
    {
        abort_unless(in_array($tokenRequest->status, ['pending','token_issued'], true), 422);

        DB::transaction(function () use ($tokenRequest) {
            $tokenRequest->alert?->update(['dismissed_at' => now()]);

            $tokenRequest->update([
                'status' => 'cancelled',
                'token_hash' => null,
                'token_last_four' => null,
                'token_generated_at' => null,
                'token_expires_at' => null,
                'token_verified_at' => null,
                'account_alert_id' => null,
            ]);
        });

        return back()->with('success', 'Withdrawal token/request destroyed. It can no longer be used.');
    }
}
