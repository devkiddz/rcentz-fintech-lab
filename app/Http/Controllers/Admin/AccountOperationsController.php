<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAccountOperation;
use App\Models\User;
use App\Services\AccountOperationsService;
use Illuminate\Http\Request;

class AccountOperationsController extends Controller
{
    public function show(User $user)
    {
        $user->load([
            'wallet',
            'stockHoldings.stock',
            'investmentHoldings.investmentPlan',
        ]);

        $operations = AdminAccountOperation::with('admin')
            ->where('user_id',$user->id)
            ->latest()
            ->paginate(25);

        return view('admin.users.account-operations', compact('user','operations'));
    }

    public function store(
        Request $request,
        User $user,
        AccountOperationsService $operations
    ) {
        $data = $request->validate([
            'operation_type'=>'required|in:wallet_credit,wallet_debit,profit_credit,history_event',
            'amount'=>'nullable|numeric|min:0.01|max:1000000000',
            'label'=>'required|string|max:180',
            'reason'=>'required|string|max:2000',
            'effective_at'=>'nullable|date',
            'history_direction'=>'nullable|in:credit,debit',
        ]);

        if ($data['operation_type'] !== 'history_event' && empty($data['amount'])) {
            return back()->withErrors(['amount'=>'Amount is required for balance and profit operations.'])->withInput();
        }

        $metadata = [];

        if ($data['operation_type'] === 'history_event') {
            $metadata['history_direction'] = $data['history_direction'] ?? null;
        }

        try {
            $operation = $operations->apply(
                $user,
                auth()->user(),
                $data['operation_type'],
                isset($data['amount']) ? (float)$data['amount'] : null,
                $data['label'],
                $data['reason'],
                $data['effective_at'] ?? null,
                $metadata
            );
        } catch (\Throwable $e) {
            return back()->withErrors([
                'operation'=>$e->getMessage() ?: 'Account operation failed.',
            ])->withInput();
        }

        return back()->with('success','Account operation recorded: '.$operation->reference);
    }
}
