<?php

namespace App\Http\Controllers;

use App\Models\PrivateInvestmentInstrument;
use App\Services\FeatureAccessService;
use App\Services\PrivateInvestmentOrderEngine;
use Illuminate\Http\Request;

class PrivateInvestmentOrderController extends Controller
{
    public function subscribe(
        Request $request,
        PrivateInvestmentInstrument $instrument,
        PrivateInvestmentOrderEngine $orders,
        FeatureAccessService $access
    ) {
        abort_if(auth()->user()->isAdmin(), 403);
        $access->require(auth()->user(), FeatureAccessService::INVESTMENTS);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'idempotency_key' => 'required|string|max:120',
        ]);

        $orders->subscribe(
            auth()->user(),
            $instrument,
            (float) $data['amount'],
            auth()->id(),
            'customer',
            $data['idempotency_key']
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
            'idempotency_key' => 'required|string|max:120',
        ]);

        $orders->redeem(
            auth()->user(),
            $instrument,
            (float) $data['units'],
            auth()->id(),
            'customer',
            $data['idempotency_key']
        );

        return back()->with('success', 'Investment redemption completed.');
    }
}
