<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Purchase;
use App\Models\User;
use App\Models\InvestmentPlan;
use App\Models\InvestmentTransaction;
use App\Models\InvestmentHolding;
use App\Models\Stock;
use App\Models\StockTransaction;
use App\Models\StockHolding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalCars = Car::count();
        $availableCars = Car::where('is_available', true)->count();
        $soldCars = Car::where('is_available', false)->count();
        
        $totalUsers = User::where('is_admin', false)->count();
        $totalSales = Purchase::where('status', 'completed')->sum('amount');
        $totalPurchases = Purchase::count();
        $conversionRate = $totalUsers > 0 ? round(($totalPurchases / $totalUsers) * 100) : 0;

        $recentPurchases = Purchase::with(['user', 'car', 'paymentMethod'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Sales (cars) month-over-month
        $monthlyRevenue = Purchase::where('status', 'completed')
            ->whereMonth('purchased_at', now()->month)
            ->whereYear('purchased_at', now()->year)
            ->sum('amount');
        $prevMonthlyRevenue = Purchase::where('status', 'completed')
            ->whereMonth('purchased_at', now()->copy()->subMonth()->month)
            ->whereYear('purchased_at', now()->copy()->subMonth()->year)
            ->sum('amount');
        $monthlyRevenueChange = $prevMonthlyRevenue > 0
            ? round((($monthlyRevenue - $prevMonthlyRevenue) / $prevMonthlyRevenue) * 100, 1)
            : null;

        $carsByMake = Car::select('make', DB::raw('count(*) as total'))
            ->groupBy('make')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();

        // Investments overview
        $activeInvestmentPlans = InvestmentPlan::active()->count();
        $totalInvestmentTransactions = InvestmentTransaction::count();
        $investmentTransactionsThisMonth = InvestmentTransaction::whereMonth('executed_at', now()->month)
            ->whereYear('executed_at', now()->year)
            ->count();
        $investmentVolumeThisMonth = InvestmentTransaction::where('status', 'completed')
            ->whereMonth('executed_at', now()->month)
            ->whereYear('executed_at', now()->year)
            ->sum('total_amount');
        $investmentVolumePrevMonth = InvestmentTransaction::where('status', 'completed')
            ->whereMonth('executed_at', now()->copy()->subMonth()->month)
            ->whereYear('executed_at', now()->copy()->subMonth()->year)
            ->sum('total_amount');
        $investmentVolumeChange = $investmentVolumePrevMonth > 0
            ? round((($investmentVolumeThisMonth - $investmentVolumePrevMonth) / $investmentVolumePrevMonth) * 100, 1)
            : null;

        // Calculate AUM for investments by summing units * plan NAV
        $totalInvestmentAum = InvestmentHolding::with('investmentPlan')->get()
            ->sum(function ($holding) {
                $nav = optional($holding->investmentPlan)->nav ?? 0;
                return (float) $holding->units * (float) $nav;
            });
        $investmentUsers = InvestmentHolding::distinct('user_id')->count('user_id');

        $recentInvestmentTransactions = InvestmentTransaction::with(['user', 'investmentPlan'])
            ->orderBy('executed_at', 'desc')
            ->limit(10)
            ->get();

        // Stocks overview
        $activeStocks = Stock::active()->count();
        $marketGainers = Stock::where('change_percentage', '>', 0)->count();
        $marketLosers = Stock::where('change_percentage', '<', 0)->count();
        $totalStockTransactions = StockTransaction::count();
        $stockTransactionsThisMonth = StockTransaction::whereMonth('executed_at', now()->month)
            ->whereYear('executed_at', now()->year)
            ->count();
        $stockVolumeThisMonth = StockTransaction::where('status', 'completed')
            ->whereMonth('executed_at', now()->month)
            ->whereYear('executed_at', now()->year)
            ->sum('total_amount');
        $stockVolumePrevMonth = StockTransaction::where('status', 'completed')
            ->whereMonth('executed_at', now()->copy()->subMonth()->month)
            ->whereYear('executed_at', now()->copy()->subMonth()->year)
            ->sum('total_amount');
        $stockVolumeChange = $stockVolumePrevMonth > 0
            ? round((($stockVolumeThisMonth - $stockVolumePrevMonth) / $stockVolumePrevMonth) * 100, 1)
            : null;

        $totalStockAum = (float) StockHolding::sum('current_value');
        $stockUsers = StockHolding::distinct('user_id')->count('user_id');

        $recentStockTransactions = StockTransaction::with(['user', 'stock'])
            ->orderBy('executed_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact(
            'totalCars',
            'availableCars', 
            'soldCars',
            'totalUsers',
            'totalSales',
            'totalPurchases',
            'conversionRate',
            'recentPurchases',
            'monthlyRevenue',
            'monthlyRevenueChange',
            'carsByMake',
            // Investments
            'activeInvestmentPlans',
            'totalInvestmentTransactions',
            'investmentTransactionsThisMonth',
            'investmentVolumeThisMonth',
            'investmentVolumeChange',
            'totalInvestmentAum',
            'investmentUsers',
            'recentInvestmentTransactions',
            // Stocks
            'activeStocks',
            'marketGainers',
            'marketLosers',
            'totalStockTransactions',
            'stockTransactionsThisMonth',
            'stockVolumeThisMonth',
            'stockVolumeChange',
            'totalStockAum',
            'stockUsers',
            'recentStockTransactions',
        ));
    }
}
