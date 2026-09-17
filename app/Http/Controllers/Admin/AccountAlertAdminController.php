<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountAlert;
use App\Models\User;
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
        ]);

        AccountAlert::query()->create([
            'user_id' => $user->id,
            'created_by_user_id' => auth()->id(),
            'type' => 'direct',
            'priority' => $data['priority'],
            'title' => $data['title'],
            'message' => $data['message'],
            'action_url' => $data['action_url'] ?? null,
            'action_label' => $data['action_label'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return back()->with('success', 'Private dashboard alert sent to '.$user->name.'.');
    }
}
