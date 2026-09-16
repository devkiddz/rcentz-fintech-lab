<?php

namespace App\Http\Controllers;

use App\Models\PrivateInvestmentInstrument;
use App\Models\PrivateInvestmentTransaction;
use App\Models\PrivateInvestmentWatchlist;
use Illuminate\Http\Request;

class PrivateInvestmentAccountController extends Controller
{
    public function transactions()
    {
        $transactions = auth()->user()->isAdmin()
            ? collect()
            : PrivateInvestmentTransaction::query()
                ->with('instrument')
                ->where('user_id', auth()->id())
                ->latest('executed_at')
                ->paginate(20);

        return view('private-investments.transactions', compact('transactions'));
    }

    public function watchlist()
    {
        $items = auth()->user()->isAdmin()
            ? collect()
            : PrivateInvestmentWatchlist::query()
                ->with('instrument')
                ->where('user_id', auth()->id())
                ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")
                ->latest('updated_at')
                ->get();

        return view('private-investments.watchlist', compact('items'));
    }

    public function storeWatchlist(Request $request, PrivateInvestmentInstrument $instrument)
    {
        $data=$request->validate([
            'target_price'=>'nullable|numeric|min:0.000001',
            'priority'=>'nullable|in:low,normal,high',
            'note'=>'nullable|string|max:1000',
        ]);

        PrivateInvestmentWatchlist::query()->updateOrCreate(
            ['user_id'=>auth()->id(),'instrument_id'=>$instrument->id],
            [
                'target_price'=>$data['target_price'] ?? null,
                'priority'=>$data['priority'] ?? 'normal',
                'note'=>$data['note'] ?? null,
                'last_reviewed_at'=>now(),
            ]
        );

        return back()->with('success','Investment added to your watchlist.');
    }

    public function updateWatchlist(Request $request, PrivateInvestmentInstrument $instrument)
    {
        $data=$request->validate([
            'target_price'=>'nullable|numeric|min:0.000001',
            'priority'=>'required|in:low,normal,high',
            'note'=>'nullable|string|max:1000',
        ]);

        PrivateInvestmentWatchlist::query()
            ->where('user_id',auth()->id())
            ->where('instrument_id',$instrument->id)
            ->firstOrFail()
            ->update([...$data,'last_reviewed_at'=>now()]);

        return back()->with('success','Watchlist settings updated.');
    }

    public function destroyWatchlist(PrivateInvestmentInstrument $instrument)
    {
        PrivateInvestmentWatchlist::query()
            ->where('user_id',auth()->id())
            ->where('instrument_id',$instrument->id)
            ->delete();

        return back()->with('success','Investment removed from your watchlist.');
    }
}
