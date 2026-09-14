<?php

namespace App\Http\Controllers;

use App\Models\InvestmentCategory;
use App\Models\InvestmentPlan;
use Illuminate\Http\Request;

class InvestmentPlanController extends Controller
{
    /**
     * Display investment plans marketplace
     */
    public function index(Request $request)
    {
        $query = InvestmentPlan::active();

        // Filter by type
        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        // Filter by risk level
        if ($request->filled('risk_level')) {
            $query->byRiskLevel($request->risk_level);
        }

        // Featured plans are a first-class filter on the canonical marketplace.
        if ($request->boolean('featured')) {
            $query->featured();
        }

        // Search by name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Sort options
        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');

        switch ($sort) {
            case 'nav':
                $query->orderBy('nav', $direction);
                break;
            case 'nav_change':
                $query->orderBy('nav_change_percentage', $direction);
                break;
            case 'management_fee':
                $query->orderBy('management_fee', $direction);
                break;
            case 'minimum_investment':
                $query->orderBy('minimum_investment', $direction);
                break;
            default:
                $query->orderBy('name', $direction);
        }

        $plans = $query->paginate(9);
        $categories = InvestmentPlan::active()->distinct()->pluck('category')->filter();
        $featuredPlans = InvestmentPlan::active()->featured()->paginate(3);

        return view('investments.index', compact('plans', 'categories', 'featuredPlans'));
    }

    /**
     * Show investment plan details
     */
    public function show(InvestmentPlan $plan)
    {
        // Check if plan is active
        if (!$plan->is_active) {
            abort(404);
        }

        // Get user's holding in this plan
        $userHolding = null;
        if (auth()->check()) {
            $userHolding = auth()->user()->investmentHoldings()
                ->where('investment_plan_id', $plan->id)
                ->first();
        }

        // Get recent transactions for this plan
        $recentTransactions = $plan->transactions()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get plan performance data (in real app, this would come from external API)
        $performanceData = $this->getPerformanceData($plan);

        return view('investments.show', compact(
            'plan',
            'userHolding',
            'recentTransactions',
            'performanceData'
        ));
    }

    /**
     * Search investment plans
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:120',
        ]);

        return redirect()->route('investments.index', [
            'search' => $validated['q'],
        ]);
    }

    /**
     * Show investment plan categories
     */
    public function categories()
    {
        $categories = InvestmentCategory::active()->withCount('investmentPlans')->get();

        return view('investments.categories', compact('categories'));
    }

    /**
     * Show featured investment plans
     */
    public function featured()
    {
        return redirect()->route('investments.index', ['featured' => 1]);
    }

    /**
     * Show plans by type
     */
    public function byType($type)
    {
        $validTypes = ['mutual_fund', 'etf', 'retirement', 'tesla_focused', 'esg', 'index_fund'];

        abort_unless(in_array($type, $validTypes, true), 404);

        return redirect()->route('investments.index', ['type' => $type]);
    }

    /**
     * Show plans by category
     */
    public function byCategory($category)
    {
        return redirect()->route('investments.index', ['category' => $category]);
    }

    /**
     * Get performance data for investment plan
     */
    private function getPerformanceData(InvestmentPlan $plan)
    {
        // In a real application, this would fetch data from external APIs
        // For now, we'll return mock data
        return [
            'nav_history' => [
                ['date' => '2024-01-01', 'nav' => $plan->nav * 0.95],
                ['date' => '2024-02-01', 'nav' => $plan->nav * 0.98],
                ['date' => '2024-03-01', 'nav' => $plan->nav * 1.02],
                ['date' => '2024-04-01', 'nav' => $plan->nav * 1.05],
                ['date' => '2024-05-01', 'nav' => $plan->nav],
            ],
            'total_return' => $plan->nav_change_percentage ?? 5.2,
            'annualized_return' => 8.5,
            'volatility' => 12.3,
            'sharpe_ratio' => 0.85,
        ];
    }
}
