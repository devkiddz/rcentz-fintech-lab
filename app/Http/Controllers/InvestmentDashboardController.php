<?php

namespace App\Http\Controllers;

use App\Models\InvestmentHolding;
use App\Models\InvestmentTransaction;
use App\Models\StockHolding;
use App\Models\StockTransaction;
use App\Models\AutomaticInvestmentPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvestmentDashboardController extends Controller
{
    /**
     * Show investment dashboard
     */
    public function index()
    {
        $user = Auth::user();
        
        // Investment Holdings
        $investmentHoldings = $user->investmentHoldings()
            ->with('investmentPlan')
            ->orderBy('current_value', 'desc')
            ->get();

        // Stock Holdings
        $stockHoldings = $user->stockHoldings()
            ->with('stock')
            ->orderBy('current_value', 'desc')
            ->get();

        // Calculate totals
        $totalInvestmentValue = $investmentHoldings->sum('current_value');
        $totalStockValue = $stockHoldings->sum('current_value');
        $totalPortfolioValue = $totalInvestmentValue + $totalStockValue;

        $totalInvestmentInvested = $investmentHoldings->sum('total_invested');
        $totalStockInvested = $stockHoldings->sum('total_invested');
        $totalInvested = $totalInvestmentInvested + $totalStockInvested;

        $totalGainLoss = $totalPortfolioValue - $totalInvested;
        $totalGainLossPercentage = $totalInvested > 0 ? ($totalGainLoss / $totalInvested) * 100 : 0;

        // Calculate monthly performance
        $monthlyPerformance = $this->calculateMonthlyPerformance($user);

        // Recent transactions
        $recentInvestmentTransactions = $user->investmentTransactions()
            ->with('investmentPlan')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentStockTransactions = $user->stockTransactions()
            ->with('stock')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Automatic investment plans
        $automaticPlans = $user->automaticInvestmentPlans()
            ->with('investmentPlan')
            ->get();

        // Performance data
        $performanceData = $this->getPerformanceData($user);

        return view('dashboard.investment', compact(
            'investmentHoldings',
            'stockHoldings',
            'totalInvestmentValue',
            'totalStockValue',
            'totalPortfolioValue',
            'totalInvestmentInvested',
            'totalStockInvested',
            'totalInvested',
            'totalGainLoss',
            'totalGainLossPercentage',
            'monthlyPerformance',
            'recentInvestmentTransactions',
            'recentStockTransactions',
            'automaticPlans',
            'performanceData'
        ));
    }

    /**
     * Show portfolio analytics
     */
    public function analytics()
    {
        $user = Auth::user();
        
        // Get all holdings
        $investmentHoldings = $user->investmentHoldings()->with('investmentPlan')->get();
        $stockHoldings = $user->stockHoldings()->with('stock')->get();

        // Asset allocation
        $assetAllocation = $this->calculateAssetAllocation($investmentHoldings, $stockHoldings);

        // Sector allocation (for stocks)
        $sectorAllocation = $this->calculateSectorAllocation($stockHoldings);

        // Investment type allocation
        $typeAllocation = $this->calculateTypeAllocation($investmentHoldings);

        // Performance over time
        $performanceHistory = $this->getPerformanceHistory($user);

        // Risk metrics
        $riskMetrics = $this->calculateRiskMetrics($investmentHoldings, $stockHoldings);

        return view('dashboard.analytics', compact(
            'assetAllocation',
            'sectorAllocation',
            'typeAllocation',
            'performanceHistory',
            'riskMetrics'
        ));
    }

    /**
     * Show transaction history
     */
    public function transactions(Request $request)
    {
        $user = Auth::user();

        $query = $user->investmentTransactions()->with('investmentPlan');

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by investment plan
        if ($request->filled('investment_plan_id')) {
            $query->where('investment_plan_id', $request->investment_plan_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $investmentTransactions = $query->orderBy('created_at', 'desc')->paginate(15);

        // Stock transactions
        $stockQuery = $user->stockTransactions()->with('stock');

        if ($request->filled('type')) {
            $stockQuery->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $stockQuery->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $stockQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $stockTransactions = $stockQuery->orderBy('created_at', 'desc')->paginate(15);

        return view('dashboard.transactions', compact('investmentTransactions', 'stockTransactions'));
    }

    /**
     * Show automatic investment plans
     */
    public function automaticPlans()
    {
        $user = Auth::user();
        
        $automaticPlans = $user->automaticInvestmentPlans()
            ->with('investmentPlan')
            ->get();

        return view('dashboard.automatic-plans', compact('automaticPlans'));
    }

    /**
     * Show edit form for automatic investment plan
     */
    public function editAutomaticPlan(AutomaticInvestmentPlan $plan)
    {
        // Ensure user owns this plan
        if ($plan->user_id !== Auth::id()) {
            abort(403);
        }

        return response()->json([
            'id' => $plan->id,
            'amount' => $plan->amount,
            'frequency' => $plan->frequency,
            'is_active' => $plan->is_active,
            'next_investment_date' => $plan->next_investment_date?->format('Y-m-d'),
        ]);
    }

    /**
     * Create automatic investment plan
     */
    public function createAutomaticPlan(Request $request)
    {
        $request->validate([
            'investment_plan_id' => 'required|exists:investment_plans,id',
            'amount' => 'required|numeric|min:1',
            'frequency' => 'required|in:weekly,biweekly,monthly,quarterly',
            'start_date' => 'required|date|after:today',
        ]);

        $user = Auth::user();

        // Check if user has sufficient funds
        $wallet = $user->wallet;
        if (!$wallet || !$wallet->canWithdraw($request->amount)) {
            return back()->withErrors(['amount' => 'Insufficient funds in wallet.']);
        }

        $automaticPlan = $user->automaticInvestmentPlans()->create([
            'investment_plan_id' => $request->investment_plan_id,
            'amount' => $request->amount,
            'frequency' => $request->frequency,
            'start_date' => $request->start_date,
            'next_investment_date' => $request->start_date,
            'is_active' => true,
        ]);

        return redirect()->route('automatic-investments.index')
            ->with('success', 'Automatic investment plan created successfully.');
    }

    /**
     * Update automatic investment plan
     */
    public function updateAutomaticPlan(Request $request, AutomaticInvestmentPlan $plan)
    {
        // Ensure user owns this plan
        if ($plan->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'frequency' => 'required|in:weekly,biweekly,monthly,quarterly',
            'is_active' => 'boolean',
        ]);

        $plan->update($request->only(['amount', 'frequency', 'is_active']));

        return back()->with('success', 'Automatic investment plan updated successfully.');
    }

    /**
     * Toggle automatic investment plan
     */
    public function toggleAutomaticPlan(AutomaticInvestmentPlan $plan)
    {
        // Ensure user owns this plan
        if ($plan->user_id !== Auth::id()) {
            abort(403);
        }

        $plan->update(['is_active' => !$plan->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Plan ' . ($plan->is_active ? 'activated' : 'deactivated') . ' successfully.',
            'is_active' => $plan->is_active
        ]);
    }

    /**
     * Delete automatic investment plan
     */
    public function deleteAutomaticPlan(AutomaticInvestmentPlan $plan)
    {
        // Ensure user owns this plan
        if ($plan->user_id !== Auth::id()) {
            abort(403);
        }

        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Automatic investment plan deleted successfully.'
        ]);
    }

    /**
     * Calculate asset allocation
     */
    private function calculateAssetAllocation($investmentHoldings, $stockHoldings)
    {
        $totalValue = $investmentHoldings->sum('current_value') + $stockHoldings->sum('current_value');
        
        if ($totalValue == 0) {
            return [
                'investments' => 0,
                'stocks' => 0,
            ];
        }

        return [
            'investments' => ($investmentHoldings->sum('current_value') / $totalValue) * 100,
            'stocks' => ($stockHoldings->sum('current_value') / $totalValue) * 100,
        ];
    }

    /**
     * Calculate sector allocation
     */
    private function calculateSectorAllocation($stockHoldings)
    {
        $sectors = [];
        $totalValue = $stockHoldings->sum('current_value');

        foreach ($stockHoldings as $holding) {
            $sector = $holding->stock->sector;
            if (!isset($sectors[$sector])) {
                $sectors[$sector] = 0;
            }
            $sectors[$sector] += $holding->current_value;
        }

        // Convert to percentages
        foreach ($sectors as $sector => $value) {
            $sectors[$sector] = $totalValue > 0 ? ($value / $totalValue) * 100 : 0;
        }

        return $sectors;
    }

    /**
     * Calculate type allocation
     */
    private function calculateTypeAllocation($investmentHoldings)
    {
        $types = [];
        $totalValue = $investmentHoldings->sum('current_value');

        foreach ($investmentHoldings as $holding) {
            $type = $holding->investmentPlan->type;
            if (!isset($types[$type])) {
                $types[$type] = 0;
            }
            $types[$type] += $holding->current_value;
        }

        // Convert to percentages
        foreach ($types as $type => $value) {
            $types[$type] = $totalValue > 0 ? ($value / $totalValue) * 100 : 0;
        }

        return $types;
    }

    /**
     * Get performance history
     */
    private function getPerformanceHistory($user)
    {
        // In a real application, this would fetch historical data
        // For now, we'll return mock data
        return [
            ['date' => '2024-01-01', 'value' => 10000],
            ['date' => '2024-02-01', 'value' => 10500],
            ['date' => '2024-03-01', 'value' => 11000],
            ['date' => '2024-04-01', 'value' => 10800],
            ['date' => '2024-05-01', 'value' => 11500],
        ];
    }

    /**
     * Calculate risk metrics
     */
    private function calculateRiskMetrics($investmentHoldings, $stockHoldings)
    {
        // In a real application, this would calculate actual risk metrics
        // For now, we'll return mock data
        return [
            'volatility' => 12.5,
            'beta' => 1.1,
            'sharpe_ratio' => 0.85,
            'max_drawdown' => -8.2,
        ];
    }

    /**
     * Get performance data
     */
    private function getPerformanceData($user)
    {
        // Get performance data for the last 12 months
        $performanceData = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthName = $date->format('M');
            
            // Calculate portfolio value for this month (simplified)
            $monthlyValue = $this->calculateMonthlyPortfolioValue($user, $date);
            
            $performanceData[] = [
                'month' => $monthName,
                'value' => $monthlyValue,
            ];
        }
        
        return $performanceData;
    }

    private function calculateMonthlyPerformance($user)
    {
        // Get current portfolio value
        $currentValue = $user->investmentHoldings()->sum('current_value') + 
                       $user->stockHoldings()->sum('current_value') + 
                       ($user->wallet->balance ?? 0);
        
        // For monthly performance, we'll simulate a 5% monthly growth
        // In a real app, you'd store historical portfolio values
        $lastMonthValue = $currentValue * 0.95; // Assume 5% growth
        
        $percentageChange = $lastMonthValue > 0 ? (($currentValue - $lastMonthValue) / $lastMonthValue) * 100 : 0;
        
        return [
            'current_value' => $currentValue,
            'last_month_value' => $lastMonthValue,
            'percentage_change' => $percentageChange,
            'is_positive' => $percentageChange >= 0,
        ];
    }

    private function calculateMonthlyPortfolioValue($user, $date)
    {
        // Get investment holdings value for the month
        $investmentValue = $user->investmentHoldings()
            ->where('created_at', '<=', $date->endOfMonth())
            ->sum('total_invested');
        
        // Get stock holdings value for the month
        $stockValue = $user->stockHoldings()
            ->where('created_at', '<=', $date->endOfMonth())
            ->sum('total_invested');
        
        // Add wallet balance
        $walletBalance = $user->wallet->balance ?? 0;
        
        return $investmentValue + $stockValue + $walletBalance;
    }
}
