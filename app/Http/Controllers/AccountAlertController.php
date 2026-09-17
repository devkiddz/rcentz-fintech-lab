<?php

namespace App\Http\Controllers;

use App\Models\AccountAlert;
use Illuminate\Http\Request;

class AccountAlertController extends Controller
{
    public function read(Request $request, AccountAlert $alert)
    {
        abort_unless($alert->user_id === $request->user()->id, 403);
        $alert->update(['read_at' => $alert->read_at ?? now()]);
        return back();
    }

    public function dismiss(Request $request, AccountAlert $alert)
    {
        abort_unless($alert->user_id === $request->user()->id, 403);
        $alert->update(['dismissed_at' => now(), 'read_at' => $alert->read_at ?? now()]);
        return back();
    }
}
