<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountAccessController extends Controller
{
    public function verifyEmail(User $user)
    {
        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return back()->with('success', $user->email.' is now manually email-verified.');
    }

    public function update(Request $request, User $user)
    {
        abort_if(auth()->id() === $user->id, 422, 'You cannot change your own account access state.');

        $data = $request->validate([
            'account_status' => ['required', Rule::in(['active','blocked','banned','suspended'])],
            'status_reason' => ['nullable','string','max:1000'],
            'status_until' => ['nullable','date','after:now'],
        ]);

        if (in_array($data['account_status'], ['blocked','banned','suspended'], true) && blank($data['status_reason'] ?? null)) {
            return back()->withErrors(['status_reason' => 'A reason is required when restricting account access.']);
        }

        if ($data['account_status'] === 'suspended' && blank($data['status_until'] ?? null)) {
            return back()->withErrors(['status_until' => 'Suspension requires an expiry date/time.']);
        }

        $user->update([
            'account_status' => $data['account_status'],
            'status_reason' => $data['account_status'] === 'active' ? null : ($data['status_reason'] ?? null),
            'status_until' => $data['account_status'] === 'suspended' ? $data['status_until'] : null,
            'status_changed_at' => now(),
            'status_changed_by_user_id' => auth()->id(),
        ]);

        return back()->with('success', 'Account access updated to '.strtoupper($data['account_status']).'.');
    }
}
