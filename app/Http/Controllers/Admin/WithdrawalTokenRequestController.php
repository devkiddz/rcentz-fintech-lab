<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountAlert;
use App\Models\WithdrawalTokenRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class WithdrawalTokenRequestController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $requests = WithdrawalTokenRequest::query()
            ->with(['user.wallet', 'generator', 'walletTransaction'])
            ->when($request->filled('user'), fn ($query) => $query->where('user_id', $request->integer('user')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim($request->string('q')->toString());
                $query->whereHas('user', fn ($userQuery) => $userQuery
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%"));
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.withdrawal-token-requests.index', compact('requests'));
    }

    public function generate(WithdrawalTokenRequest $tokenRequest)
    {
        abort_unless(in_array($tokenRequest->status, ['pending','token_issued'], true), 422);

        $token = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(30);

        DB::transaction(function () use ($tokenRequest, $token, $expiresAt) {
            $tokenRequest->alert?->update(['dismissed_at' => now()]);

            $tokenRequest->update([
                'status' => 'token_issued',
                'token_hash' => Hash::make($token),
                'token_last_four' => substr($token, -4),
                'token_generated_at' => now(),
                'token_expires_at' => $expiresAt,
                'token_verified_at' => null,
                'generated_by_user_id' => auth()->id(),
                'account_alert_id' => null,
            ]);
        });

        return back()
            ->with('success', 'Verification code generated. Copy it and send it manually through the customer dashboard notice.')
            ->with('generated_withdrawal_token', [
                'request_id' => $tokenRequest->id,
                'user_id' => $tokenRequest->user_id,
                'customer_name' => $tokenRequest->user->name,
                'customer_email' => $tokenRequest->user->email,
                'amount' => format_currency($tokenRequest->amount, 'USD', $tokenRequest->user->currency, $tokenRequest->user),
                'token' => $token,
                'expires_at' => $expiresAt->format('M j, Y · h:i A'),
            ]);
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
