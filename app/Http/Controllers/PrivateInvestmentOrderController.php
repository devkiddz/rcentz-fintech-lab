<?php

namespace App\Http\Controllers;

use App\Models\PrivateInvestmentInstrument;
use App\Services\PrivateInvestmentOrderEngine;
use Illuminate\Http\Request;

class PrivateInvestmentOrderController extends Controller
{
    public function subscribe(
        Request $request,
        PrivateInvestmentInstrument $instrument,
        PrivateInvestmentOrderEngine $orders
    ) {
        abort_if(auth()->user()->isAdmin(), 403);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $orders->subscribe(
            auth()->user(),
            $instrument,
            (float) $data['amount'],
            auth()->id(),
            'customer'
        );

        return back()->with('success', 'Investment subscription completed.');
    }

    public function redeem(
        Request $request,
        PrivateInvestmentInstrument $instrument,
        PrivateInvestmentOrderEngine $orders
    ) {
        abort_if(auth()->user()->isAdmin(), 403);

        $data = $request->validate([
            'units' => 'required|numeric|min:0.000001',
        ]);

        $orders->redeem(
            auth()->user(),
            $instrument,
            (float) $data['units'],
            auth()->id(),
            'customer'
        );

        return back()->with('success', 'Investment redemption completed.');
    }
}
