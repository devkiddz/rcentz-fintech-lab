<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvestmentPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InvestmentPlanController extends Controller
{
    public function index()
    {
        $plans = InvestmentPlan::withCount(['holdings', 'transactions'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $stats = [
            'total' => InvestmentPlan::count(),
            'active' => InvestmentPlan::where('is_active', true)->count(),
            'featured' => InvestmentPlan::where('is_featured', true)->count(),
            'total_assets' => InvestmentPlan::sum('total_assets'),
        ];

        return view('admin.investments.plans.index', compact('plans', 'stats'));
    }

    public function create()
    {
        return view('admin.investments.plans.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|string|in:tesla_focused,esg,mutual_fund,etf,retirement',
            'category' => 'required|string|max:255',
            'risk_level' => 'required|string|in:low,medium,high,very_high',
            'minimum_investment' => 'required|numeric|min:0',
            'management_fee' => 'required|numeric|min:0|max:1',
            'expense_ratio' => 'required|numeric|min:0|max:1',
            'nav' => 'required|numeric|min:0',
            'previous_nav' => 'nullable|numeric|min:0',
            'nav_change' => 'nullable|numeric',
            'nav_change_percentage' => 'nullable|numeric',
            'total_assets' => 'nullable|numeric|min:0',
            'inception_date' => 'required|date',
            'dividend_yield' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'prospectus_url' => 'nullable|url',
            'fact_sheet_url' => 'nullable|url',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active');
        $data['is_featured'] = $request->has('is_featured');
        $data['last_updated'] = now();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('investment-plans', 'public');
        }

        InvestmentPlan::create($data);

        return redirect()->route('admin.investments.plans.index')
            ->with('success', 'Investment plan created successfully.');
    }

    public function show(InvestmentPlan $plan)
    {
        $plan->load(['holdings.user', 'transactions.user']);
        
        $stats = [
            'total_holdings' => $plan->holdings->count(),
            'total_investors' => $plan->holdings->unique('user_id')->count(),
            'total_transactions' => $plan->transactions->count(),
            'total_volume' => $plan->transactions->sum('total_amount'),
        ];

        return view('admin.investments.plans.show', compact('plan', 'stats'));
    }

    public function edit(InvestmentPlan $plan)
    {
        return view('admin.investments.plans.edit', compact('plan'));
    }

    public function update(Request $request, InvestmentPlan $plan)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|string|in:tesla_focused,esg,mutual_fund,etf,retirement',
            'category' => 'required|string|max:255',
            'risk_level' => 'required|string|in:low,medium,high,very_high',
            'minimum_investment' => 'required|numeric|min:0',
            'management_fee' => 'required|numeric|min:0|max:1',
            'expense_ratio' => 'required|numeric|min:0|max:1',
            'nav' => 'required|numeric|min:0',
            'previous_nav' => 'nullable|numeric|min:0',
            'nav_change' => 'nullable|numeric',
            'nav_change_percentage' => 'nullable|numeric',
            'total_assets' => 'nullable|numeric|min:0',
            'inception_date' => 'required|date',
            'dividend_yield' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'prospectus_url' => 'nullable|url',
            'fact_sheet_url' => 'nullable|url',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active');
        $data['is_featured'] = $request->has('is_featured');
        $data['last_updated'] = now();

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($plan->image) {
                Storage::disk('public')->delete($plan->image);
            }
            $data['image'] = $request->file('image')->store('investment-plans', 'public');
        }

        $plan->update($data);

        return redirect()->route('admin.investments.plans.index')
            ->with('success', 'Investment plan updated successfully.');
    }

    public function destroy(InvestmentPlan $plan)
    {
        // Check if plan has holdings or transactions
        if ($plan->holdings()->count() > 0 || $plan->transactions()->count() > 0) {
            return redirect()->route('admin.investments.plans.index')
                ->with('error', 'Cannot delete investment plan with existing holdings or transactions.');
        }

        // Delete image if exists
        if ($plan->image) {
            Storage::disk('public')->delete($plan->image);
        }

        $plan->delete();

        return redirect()->route('admin.investments.plans.index')
            ->with('success', 'Investment plan deleted successfully.');
    }
}
