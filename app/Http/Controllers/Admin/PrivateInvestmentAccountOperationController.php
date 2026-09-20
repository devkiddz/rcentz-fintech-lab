<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrivateInvestmentInstrument;
use App\Models\User;
use App\Services\PrivateInvestmentOrderEngine;
use Illuminate\Http\Request;

class PrivateInvestmentAccountOperationController extends Controller
{
    public function subscribe(
        Request $request,
        PrivateInvestmentInstrument $instrument,
        PrivateInvestmentOrderEngine $orders
    ) {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'idempotency_key' => 'required|string|max:120',
        ]);

        $user = User::query()->findOrFail($data['user_id']);
        abort_if($user->isAdmin(), 422, 'Select a customer account.');

        $orders->subscribe(
            $user,
            $instrument,
            (float) $data['amount'],
            auth()->id(),
            'admin_control',
            $data['idempotency_key']
        );

        return back()->with('success', 'Admin subscription completed for '.$user->name.'.');
    }

    public function redeem(
        Request $request,
        PrivateInvestmentInstrument $instrument,
        PrivateInvestmentOrderEngine $orders
    ) {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'units' => 'required|numeric|min:0.000001',
            'idempotency_key' => 'required|string|max:120',
        ]);

        $user = User::query()->findOrFail($data['user_id']);
        abort_if($user->isAdmin(), 422, 'Select a customer account.');

        $orders->redeem(
            $user,
            $instrument,
            (float) $data['units'],
            auth()->id(),
            'admin_control',
            $data['idempotency_key']
        );

        return back()->with('success', 'Admin redemption completed for '.$user->name.'.');
    }
}
