<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvestmentTransaction;

class InvestmentTransactionController extends Controller
{
    public function index()
    {
        $transactions = InvestmentTransaction::with(['user', 'investmentPlan', 'walletTransaction.paymentMethod'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $stats = [
            'total' => InvestmentTransaction::count(),
            'total_users' => InvestmentTransaction::distinct('user_id')->count(),
            'total_plans' => InvestmentTransaction::distinct('investment_plan_id')->count(),
            'total_volume' => InvestmentTransaction::sum('total_amount'),
            'completed' => InvestmentTransaction::where('status', 'completed')->count(),
            'pending' => InvestmentTransaction::where('status', 'pending')->count(),
        ];

        return view('admin.investments.transactions.index', compact('transactions', 'stats'));
    }

    public function show(InvestmentTransaction $transaction)
    {
        $transaction->load(['user', 'investmentPlan', 'walletTransaction.paymentMethod']);
        
        return view('admin.investments.transactions.show', compact('transaction'));
    }
}
