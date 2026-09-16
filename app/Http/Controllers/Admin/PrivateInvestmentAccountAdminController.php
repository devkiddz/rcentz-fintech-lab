<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrivateInvestmentInstrument;
use App\Models\PrivateInvestmentWatchlist;
use App\Models\User;
use Illuminate\Http\Request;

class PrivateInvestmentAccountAdminController extends Controller
{
    public function storeWatchlist(Request $request, User $user, PrivateInvestmentInstrument $instrument)
    {
        abort_if($user->isAdmin(), 422, 'An administrator is not a customer investment account.');

        $data=$request->validate([
            'target_price'=>'nullable|numeric|min:0.000001',
            'priority'=>'nullable|in:low,normal,high',
            'note'=>'nullable|string|max:1000',
        ]);

        PrivateInvestmentWatchlist::query()->updateOrCreate(
            ['user_id'=>$user->id,'instrument_id'=>$instrument->id],
            [
                'target_price'=>$data['target_price'] ?? null,
                'priority'=>$data['priority'] ?? 'normal',
                'note'=>$data['note'] ?? null,
                'last_reviewed_at'=>now(),
            ]
        );

        return back()->with('success',"{$user->name}'s investment watchlist was updated by admin.");
    }

    public function updateWatchlist(Request $request, User $user, PrivateInvestmentInstrument $instrument)
    {
        abort_if($user->isAdmin(),422,'An administrator is not a customer investment account.');

        $data=$request->validate([
            'target_price'=>'nullable|numeric|min:0.000001',
            'priority'=>'required|in:low,normal,high',
            'note'=>'nullable|string|max:1000',
        ]);

        PrivateInvestmentWatchlist::query()
            ->where('user_id',$user->id)
            ->where('instrument_id',$instrument->id)
            ->firstOrFail()
            ->update([...$data,'last_reviewed_at'=>now()]);

        return back()->with('success',"{$user->name}'s investment watchlist was changed by admin.");
    }

    public function destroyWatchlist(User $user, PrivateInvestmentInstrument $instrument)
    {
        abort_if($user->isAdmin(),422,'An administrator is not a customer investment account.');

        PrivateInvestmentWatchlist::query()
            ->where('user_id',$user->id)
            ->where('instrument_id',$instrument->id)
            ->delete();

        return back()->with('success',"{$user->name}'s investment was removed from the watchlist by admin.");
    }
}
