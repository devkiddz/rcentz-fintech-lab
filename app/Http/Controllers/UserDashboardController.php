<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Purchase;
use App\Models\Stock;
use App\Models\StockPriceHistory;
use Carbon\Carbon;

class UserDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Car purchase data
        $recentPurchases = $user->purchases()
            ->with(['car', 'paymentMethod'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $totalSpent = $user->purchases()
            ->where('status', 'completed')
            ->sum('amount');

        $totalPurchases = $user->purchases()->where('status', 'completed')->get();

        // Get investment and stock holdings
        $investmentHoldings = $user->investmentHoldings()
            ->with('investmentPlan')
            ->get();

        $stockHoldings = $user->stockHoldings()
            ->with('stock')
            ->get();

        // Calculate portfolio values
        $totalInvestmentValue = $investmentHoldings->sum('current_value');
        $totalStockValue = $stockHoldings->sum('current_value');
        $totalPortfolioValue = $totalInvestmentValue + $totalStockValue + ($user->wallet->balance ?? 0);

        $totalInvestmentInvested = $investmentHoldings->sum('total_invested');
        $totalStockInvested = $stockHoldings->sum('total_invested');
        $totalInvested = $totalInvestmentInvested + $totalStockInvested;

        $totalGainLoss = $totalPortfolioValue - $totalInvested;
        $totalGainLossPercentage = $totalInvested > 0 ? ($totalGainLoss / $totalInvested) * 100 : 0;

        // Calculate monthly performance
        $monthlyPerformance = $this->calculateMonthlyPerformance($user);

        // Get stock data for chart
        $chartData = $this->getStockChartData();
        $marketOverview = $this->getMarketOverview();

        return view('user.dashboard', compact(
            'recentPurchases',
            'totalSpent',
            'totalPurchases',
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
            'chartData',
            'marketOverview'
        ));
    }

    private function getStockChartData()
    {
        // Get top stocks for chart
        $topStocks = Stock::where('is_active', true)
            ->orderBy('volume', 'desc')
            ->take(5)
            ->get(['symbol', 'company_name', 'current_price', 'change_percentage', 'volume']);

        // Get historical data for the last 30 days
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        
        $historicalData = StockPriceHistory::where('timestamp', '>=', $thirtyDaysAgo)
            ->whereIn('symbol', $topStocks->pluck('symbol'))
            ->orderBy('timestamp', 'asc')
            ->get(['symbol', 'close', 'timestamp'])
            ->groupBy('symbol');

        // Format data for Chart.js
        $chartData = [
            'labels' => [],
            'datasets' => []
        ];

        // Generate labels (last 30 days)
        $labels = [];
        for ($i = 29; $i >= 0; $i--) {
            $labels[] = Carbon::now()->subDays($i)->format('M j');
        }
        $chartData['labels'] = $labels;

        // Create datasets for each stock
        $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];
        
        foreach ($topStocks as $index => $stock) {
            $stockData = $historicalData->get($stock->symbol, collect());
            
            // Create price data array
            $prices = [];
            foreach ($labels as $label) {
                $date = Carbon::createFromFormat('M j', $label)->year(Carbon::now()->year);
                $priceData = $stockData->where('timestamp', '>=', $date->startOfDay())
                    ->where('timestamp', '<=', $date->endOfDay())
                    ->first();
                
                $prices[] = $priceData ? $priceData->close : null;
            }

            // Remove null values and interpolate
            $prices = $this->interpolatePrices($prices);

            $chartData['datasets'][] = [
                'label' => $stock->symbol,
                'data' => $prices,
                'borderColor' => $colors[$index % count($colors)],
                'backgroundColor' => $colors[$index % count($colors)] . '20',
                'borderWidth' => 2,
                'fill' => false,
                'tension' => 0.4,
                'pointRadius' => 0,
                'pointHoverRadius' => 4,
            ];
        }

        return $chartData;
    }

    private function createSampleChartData($stocks)
    {
        $chartData = [
            'labels' => [],
            'datasets' => []
        ];

        // Generate labels (last 30 days)
        $labels = [];
        for ($i = 29; $i >= 0; $i--) {
            $labels[] = Carbon::now()->subDays($i)->format('M j');
        }
        $chartData['labels'] = $labels;

        // Create sample datasets
        $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];
        
        foreach ($stocks as $index => $stock) {
            $basePrice = $stock->current_price ?? 100;
            $prices = [];
            
            // Generate realistic price movements
            for ($i = 0; $i < 30; $i++) {
                $variation = (rand(-10, 10) / 100); // ±10% variation
                $price = $basePrice * (1 + $variation);
                $prices[] = round($price, 2);
            }

            $chartData['datasets'][] = [
                'label' => $stock->symbol,
                'data' => $prices,
                'borderColor' => $colors[$index % count($colors)],
                'backgroundColor' => $colors[$index % count($colors)] . '20',
                'borderWidth' => 2,
                'fill' => false,
                'tension' => 0.4,
                'pointRadius' => 0,
                'pointHoverRadius' => 4,
            ];
        }

        return $chartData;
    }

    private function interpolatePrices($prices)
    {
        $interpolated = [];
        $lastPrice = null;
        
        foreach ($prices as $price) {
            if ($price !== null) {
                $lastPrice = $price;
                $interpolated[] = $price;
            } elseif ($lastPrice !== null) {
                $interpolated[] = $lastPrice;
            } else {
                $interpolated[] = 0;
            }
        }
        
        return $interpolated;
    }

    private function getMarketOverview()
    {
        // Get market overview data
        $topGainers = Stock::where('is_active', true)
            ->orderBy('change_percentage', 'desc')
            ->take(3)
            ->get(['symbol', 'company_name', 'current_price', 'change_percentage', 'logo_url']);

        $topLosers = Stock::where('is_active', true)
            ->orderBy('change_percentage', 'asc')
            ->take(3)
            ->get(['symbol', 'company_name', 'current_price', 'change_percentage', 'logo_url']);

        $mostActive = Stock::where('is_active', true)
            ->orderBy('volume', 'desc')
            ->take(3)
            ->get(['symbol', 'company_name', 'current_price', 'volume', 'logo_url']);

        return [
            'gainers' => $topGainers,
            'losers' => $topLosers,
            'most_active' => $mostActive
        ];
    }

    public function history()
    {
        $user = Auth::user();
        $purchases = $user->purchases()
            ->with(['car', 'paymentMethod'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('user.history', compact('purchases'));
    }

    public function downloadInvoice(Purchase $purchase)
    {
        // Ensure the purchase belongs to the authenticated user
        if ($purchase->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this invoice.');
        }

        // Ensure the purchase is completed
        if ($purchase->status !== 'completed') {
            abort(403, 'Invoice is only available for completed purchases.');
        }

        // Load relationships
        $purchase->load(['car', 'paymentMethod', 'user']);

        // Generate PDF using barryvdh/laravel-dompdf facade (better asset handling)
        // Ensure font cache directory exists to avoid fopen errors
        if (!is_dir(storage_path('fonts'))) {
            @mkdir(storage_path('fonts'), 0775, true);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::setOption(['isRemoteEnabled' => true, 'defaultFont' => 'sans-serif'])
            ->loadView('pdf.invoice', compact('purchase'))
            ->setPaper('a4', 'portrait')
            ->setWarnings(false);

        return $pdf->download('invoice-' . $purchase->id . '.pdf');
    }

    private function calculateMonthlyPerformance($user)
    {
        // Calculate portfolio value one month ago vs now
        $now = now();
        $oneMonthAgo = $now->copy()->subMonth();

        // Get current portfolio value
        $currentPortfolioValue = $user->investmentHoldings()->sum('current_value') + 
                               $user->stockHoldings()->sum('current_value') + 
                               ($user->wallet->balance ?? 0);

        // For simplicity, assume portfolio value was 10% less a month ago
        // In a real app, you'd store historical portfolio values
        $previousPortfolioValue = $currentPortfolioValue * 0.9;

        $change = $currentPortfolioValue - $previousPortfolioValue;
        $percentageChange = $previousPortfolioValue > 0 ? ($change / $previousPortfolioValue) * 100 : 0;

        return [
            'current_value' => $currentPortfolioValue,
            'previous_value' => $previousPortfolioValue,
            'change' => $change,
            'percentage_change' => $percentageChange,
            'is_positive' => $change >= 0,
        ];
    }
}
