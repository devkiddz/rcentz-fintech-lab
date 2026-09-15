<?php

namespace App\Http\Controllers;

use App\Models\StockTradePlan;
use Illuminate\Support\Facades\Auth;

class StockTradePlanController extends Controller
{
    public function cancel(StockTradePlan $plan)
    {
        abort_unless($plan->user_id === Auth::id(), 403);
        abort_unless(in_array($plan->status,['active','due'],true), 422, 'This trade plan can no longer be cancelled.');

        $plan->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Trade plan cancelled.');
    }
}
