<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvestmentHolding;

class InvestmentHoldingController extends Controller
{
    public function index()
    {
        $holdings = InvestmentHolding::with(['user', 'investmentPlan'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $stats = [
            'total' => InvestmentHolding::count(),
            'total_investors' => InvestmentHolding::distinct('user_id')->count(),
            'total_plans' => InvestmentHolding::distinct('investment_plan_id')->count(),
            'total_value' => InvestmentHolding::sum('current_value'),
        ];

        return view('admin.investments.holdings.index', compact('holdings', 'stats'));
    }

    public function show(InvestmentHolding $holding)
    {
        $holding->load(['user', 'investmentPlan']);
        
        return view('admin.investments.holdings.show', compact('holding'));
    }
}
