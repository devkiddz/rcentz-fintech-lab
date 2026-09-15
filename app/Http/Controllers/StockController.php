<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockNews;
use Illuminate\Http\Request;
use App\Services\StockDataService;
use App\Services\StockAnalysisService;

class StockController extends Controller
{
    /**
     * Display stock marketplace
     */
    public function index(Request $request)
    {
        $query = Stock::active();

        // Filter by sector
        if ($request->filled('sector')) {
            $query->bySector($request->sector);
        }

        // Filter by industry
        if ($request->filled('industry')) {
            $query->byIndustry($request->industry);
        }

        // Search by symbol or company name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->bySymbol($search)
                  ->orWhere('company_name', 'LIKE', "%{$search}%");
            });
        }

        // Sort options
        $sort = $request->get('sort', 'symbol');
        $direction = $request->get('direction', 'asc');

        switch ($sort) {
            case 'price':
                $query->orderBy('current_price', $direction);
                break;
            case 'change':
                $query->orderBy('change_percentage', $direction);
                break;
            case 'volume':
                $query->orderBy('volume', $direction);
                break;
            case 'market_cap':
                $query->orderBy('market_cap', $direction);
                break;
            default:
                $query->orderBy('symbol', $direction);
        }

        $stocks = $query->paginate(10);
        $featuredStocks = Stock::active()->featured()->limit(3)->get();
        $gainers = Stock::active()->gainers()->limit(5)->get();
        $losers = Stock::active()->losers()->limit(5)->get();
        $mostActive = Stock::active()->mostActive()->limit(5)->get();

        // Get unique sectors and industries for filters
        $sectors = Stock::active()->distinct()->pluck('sector')->filter();
        $industries = Stock::active()->distinct()->pluck('industry')->filter();

        return view('stocks.index', compact(
            'stocks',
            'featuredStocks',
            'gainers',
            'losers',
            'mostActive',
            'sectors',
            'industries'
        ));
    }

    /**
     * Show stock details
     */
    public function show(Stock $stock)
    {
        if (!$stock->is_active) {
            abort(404);
        }

        // Get user's holding in this stock
        $userHolding = null;
        if (auth()->check()) {
            $userHolding = auth()->user()->stockHoldings()
                ->where('stock_id', $stock->id)
                ->first();
        }

        // Get recent transactions for this stock
        $recentTransactions = $stock->transactions()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get watchlist data
        $isInWatchlist = false;
        $watchlistItem = null;
        if (auth()->check()) {
            $watchlistItem = auth()->user()->stockWatchlist()
                ->where('stock_id', $stock->id)
                ->first();
            $isInWatchlist = $watchlistItem !== null;
        }

        // Stored-state market analysis for charts, moving averages and signal context.
        $analysis = app(StockAnalysisService::class)->forStock($stock);

        // Legacy performance payload retained temporarily for untouched consumers.
        $performanceData = $this->getPerformanceData($stock);

        // Latest news for this stock from DB; fallback to API cache if empty
        $newsFromDb = StockNews::getLatestNews($stock->symbol, 5);
        $newsFromApi = collect();
        if ($newsFromDb->isEmpty()) {
            /** @var StockDataService $stockDataService */
            $stockDataService = app(StockDataService::class);
            $apiNews = $stockDataService->getStockNews($stock->symbol, 5) ?? [];
            $newsFromApi = collect($apiNews);
        }

        return view('stocks.show', compact(
            'stock',
            'userHolding',
            'recentTransactions',
            'performanceData',
            'isInWatchlist',
            'watchlistItem',
            'newsFromDb',
            'newsFromApi',
            'analysis'
        ));
    }

    /**
     * Search stocks
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1',
        ]);

        $query = Stock::active();
        $search = $request->q;

        $query->where(function ($q) use ($search) {
            $q->bySymbol($search)
              ->orWhere('company_name', 'LIKE', "%{$search}%")
              ->orWhere('sector', 'LIKE', "%{$search}%")
              ->orWhere('industry', 'LIKE', "%{$search}%");
        });

        $stocks = $query->orderBy('symbol')->paginate(20);

        return view('stocks.search', compact('stocks', 'search'));
    }

    /**
     * Show stocks by sector
     */
    public function bySector($sector)
    {
        return redirect()->route('stocks.index', ['sector' => $sector]);
    }

    /**
     * Show stocks by industry
     */
    public function byIndustry($industry)
    {
        return redirect()->route('stocks.index', ['industry' => $industry]);
    }

    /**
     * Show top gainers
     */
    public function gainers()
    {
        $stocks = Stock::active()
            ->gainers()
            ->orderBy('change_percentage', 'desc')
            ->paginate(10);

        return view('stocks.gainers', compact('stocks'));
    }

    /**
     * Show top losers
     */
    public function losers()
    {
        $stocks = Stock::active()
            ->losers()
            ->orderBy('change_percentage', 'asc')
            ->paginate(10);

        return view('stocks.losers', compact('stocks'));
    }

    /**
     * Show most active stocks
     */
    public function mostActive()
    {
        $stocks = Stock::active()
            ->mostActive()
            ->paginate(10);

        return view('stocks.most-active', compact('stocks'));
    }

    /**
     * Get performance data for stock
     */
    private function getPerformanceData(Stock $stock)
    {
        // In a real application, this would fetch data from external APIs
        // For now, we'll return mock data
        return [
            'price_history' => [
                ['date' => '2024-01-01', 'price' => $stock->current_price * 0.95],
                ['date' => '2024-02-01', 'price' => $stock->current_price * 0.98],
                ['date' => '2024-03-01', 'price' => $stock->current_price * 1.02],
                ['date' => '2024-04-01', 'price' => $stock->current_price * 1.05],
                ['date' => '2024-05-01', 'price' => $stock->current_price],
            ],
            'total_return' => $stock->change_percentage ?? 5.2,
            'annualized_return' => 8.5,
            'volatility' => 12.3,
            'beta' => 1.2,
            'pe_ratio' => $stock->pe_ratio,
            'dividend_yield' => $stock->dividend_yield,
        ];
    }
}
