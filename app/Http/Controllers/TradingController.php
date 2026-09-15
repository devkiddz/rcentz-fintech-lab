<?php

namespace App\Http\Controllers;

use App\Mail\StockBuyEmail;
use App\Mail\StockSellEmail;
use App\Models\Stock;
use App\Models\StockHolding;
use App\Models\StockTransaction;
use App\Models\StockWatchlist;
use App\Models\WalletTransaction;
use App\Models\StockPriceHistory;
use App\Services\NotificationService;
use App\Services\FinancialActivityService;
use App\Services\CopyTradingService;
use App\Services\StockTradePlanService;
use App\Services\StockAnalysisService;
use App\Services\StockTradeExecutor;
use App\Models\StockTradePlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class TradingController extends Controller
{
    /**
     * Show buy stock form
     */
    public function buy(Stock $stock)
    {
        if (!$stock->is_active) {
            abort(404);
        }

        $user = Auth::user();
        $wallet = $user->wallet;
        $userHolding = $user->stockHoldings()
            ->where('stock_id', $stock->id)
            ->first();

        // Legacy chart payload retained while the new analysis engine owns the workstation chart.
        $chartData = $this->getStockChartData($stock->symbol);
        $analysis = app(StockAnalysisService::class)->forStock($stock);

        return view('trading.buy', compact('stock', 'wallet', 'userHolding', 'chartData', 'analysis'));
    }

    /**
     * Execute buy stock
     */
    public function executeBuy(
        Request $request,
        Stock $stock,
        StockTradeExecutor $executor,
        CopyTradingService $copyTrading,
        StockTradePlanService $tradePlans
    ) {
        if (! $stock->is_active) {
            abort(404);
        }

        $data = $request->validate([
            'quantity' => 'required|numeric|min:1|max:10000',
            'plan_duration_minutes' => 'nullable|integer|min:0|max:10080',
            'plan_mode' => 'nullable|in:reminder,automatic',
        ]);

        $user = Auth::user();
        $quantity = (float) $data['quantity'];

        try {
            // Single source of truth for wallet mutation, holding mutation,
            // stock transaction creation and financial activity.
            $stockTransaction = $executor->buy(
                $user,
                $stock,
                $quantity,
                'stock_trade'
            );
        } catch (\Throwable $e) {
            \Log::warning('Manual stock buy failed', [
                'user_id' => $user->id,
                'stock_id' => $stock->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'error' => $e->getMessage() ?: 'Failed to process purchase. Please try again.',
            ])->withInput();
        }

        // Everything below is post-execution side effect. A notification,
        // email, copy mirror or trade-plan failure must never roll back a
        // completed financial execution.
        try {
            NotificationService::createInvestmentSuccessNotification(
                $user,
                $stock->symbol,
                (float) $stockTransaction->total_amount
            );
        } catch (\Throwable $e) {
            \Log::warning('Stock buy notification failed', [
                'trade_id' => $stockTransaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $totalPortfolioValue = (float) $user->stockHoldings()->sum('current_value');
            Mail::to($user->email)->send(
                new StockBuyEmail($user, $stockTransaction, $totalPortfolioValue)
            );
        } catch (\Throwable $e) {
            \Log::warning('Stock buy email failed', [
                'trade_id' => $stockTransaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $copyTrading->mirrorCompletedTrade($stockTransaction);
        } catch (\Throwable $e) {
            \Log::warning('Copy mirroring failed after provider buy', [
                'trade_id' => $stockTransaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $tradePlans->createForTransaction(
                $stockTransaction,
                $request->only(['plan_duration_minutes', 'plan_mode'])
            );
        } catch (\Throwable $e) {
            \Log::warning('Trade plan creation failed after stock buy', [
                'trade_id' => $stockTransaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('trading.portfolio')
            ->with(
                'success',
                'Successfully purchased '.number_format($quantity, 6).' shares of '.
                $stock->symbol.' for '.currency_symbol().number_format((float) $stockTransaction->total_amount, 2).'.'
            );
    }

    /**
     * Show sell stock form
     */
    public function sell(Stock $stock)
    {
        $user = auth()->user();
        $wallet = $user->wallet;
        $holding = $user->stockHoldings()->where('stock_id', $stock->id)->first();

        // Redirect if user doesn't have holdings in this stock
        if (!$holding) {
            return redirect()->route('trading.portfolio')
                ->with('error', 'You do not have any holdings in this stock.');
        }
        
        // Legacy chart payload retained while the new analysis engine owns the workstation chart.
        $chartData = $this->getStockChartData($stock->symbol, '1m');
        $analysis = app(StockAnalysisService::class)->forStock($stock);
        
        return view('trading.sell', compact('stock', 'wallet', 'holding', 'chartData', 'analysis'));
    }

    /**
     * Execute sell stock
     */
    public function executeSell(
        Request $request,
        Stock $stock,
        StockTradeExecutor $executor,
        CopyTradingService $copyTrading,
        StockTradePlanService $tradePlans
    ) {
        if (! $stock->is_active) {
            abort(404);
        }

        $data = $request->validate([
            'quantity' => 'required|numeric|min:1|max:10000',
            'plan_duration_minutes' => 'nullable|integer|min:0|max:10080',
            'plan_mode' => 'nullable|in:reminder,automatic',
        ]);

        $user = Auth::user();
        $quantity = (float) $data['quantity'];

        try {
            // StockTradeExecutor locks the holding and wallet inside the same
            // database transaction and validates the final available quantity.
            $stockTransaction = $executor->sell(
                $user,
                $stock,
                $quantity,
                'stock_trade'
            );
        } catch (\Throwable $e) {
            \Log::warning('Manual stock sell failed', [
                'user_id' => $user->id,
                'stock_id' => $stock->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'error' => $e->getMessage() ?: 'Failed to process sale. Please try again.',
            ])->withInput();
        }

        try {
            NotificationService::createInvestmentSuccessNotification(
                $user,
                $stock->symbol.' (Sale)',
                (float) $stockTransaction->total_amount
            );
        } catch (\Throwable $e) {
            \Log::warning('Stock sell notification failed', [
                'trade_id' => $stockTransaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $totalPortfolioValue = (float) $user->stockHoldings()->sum('current_value');
            Mail::to($user->email)->send(
                new StockSellEmail($user, $stockTransaction, $totalPortfolioValue)
            );
        } catch (\Throwable $e) {
            \Log::warning('Stock sell email failed', [
                'trade_id' => $stockTransaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $copyTrading->mirrorCompletedTrade($stockTransaction);
        } catch (\Throwable $e) {
            \Log::warning('Copy mirroring failed after provider sale', [
                'trade_id' => $stockTransaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $tradePlans->createForTransaction(
                $stockTransaction,
                $request->only(['plan_duration_minutes', 'plan_mode'])
            );
        } catch (\Throwable $e) {
            \Log::warning('Trade plan creation failed after stock sell', [
                'trade_id' => $stockTransaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('trading.portfolio')
            ->with(
                'success',
                'Successfully sold '.number_format($quantity, 6).' shares of '.
                $stock->symbol.' for '.currency_symbol().number_format((float) $stockTransaction->total_amount, 2).'.'
            );
    }

    /**
     * Show stock portfolio
     */
    public function portfolio()
    {
        $user = Auth::user();
        
        $holdings = $user->stockHoldings()
            ->with('stock')
            ->get();

        $totalInvested = $holdings->sum('total_invested');
        $totalCurrentValue = $holdings->sum('current_value');
        $totalGainLoss = $totalCurrentValue - $totalInvested;
        $totalGainLossPercentage = $totalInvested > 0 ? ($totalGainLoss / $totalInvested) * 100 : 0;

        // Sort holdings by current value
        $holdings = $holdings->sortByDesc('current_value')->values();

        $tradePlans = StockTradePlan::with('stock')
            ->where('user_id', $user->id)
            ->whereIn('status', ['active','due'])
            ->orderBy('due_at')
            ->get();

        $recentTransactions = $user->stockTransactions()
            ->with('stock')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('trading.portfolio', compact(
            'holdings',
            'totalInvested',
            'totalCurrentValue',
            'totalGainLoss',
            'totalGainLossPercentage',
            'recentTransactions',
            'tradePlans'
        ));
    }

    /**
     * Show stock transactions
     */
    public function transactions(Request $request)
    {
        $user = Auth::user();
        
        $query = $user->stockTransactions()
            ->with('stock');

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('trading.transactions', compact('transactions'));
    }

    /**
     * Show stock watchlist
     */
    public function watchlist()
    {
        $user = Auth::user();
        
        $watchlist = $user->stockWatchlist()
            ->with('stock')
            ->get();

        return view('trading.watchlist', compact('watchlist'));
    }

    /**
     * Add to watchlist
     */
    public function addToWatchlist(Request $request, Stock $stock)
    {
        $user = Auth::user();
        
        $request->validate([
            'alert_price' => 'nullable|numeric|min:0',
            'alert_type' => 'nullable|in:above,below',
        ]);

        $user->stockWatchlist()->updateOrCreate(
            ['stock_id' => $stock->id],
            [
                'alert_price' => $request->alert_price,
                'alert_type' => $request->alert_type,
            ]
        );

        return back()->with('success', "{$stock->symbol} added to your watchlist.");
    }

    /**
     * Update watchlist alert
     */
    public function updateWatchlist(Request $request, Stock $stock)
    {
        $user = Auth::user();
        
        $request->validate([
            'alert_price' => 'nullable|numeric|min:0',
            'alert_type' => 'nullable|in:above,below',
        ]);

        $user->stockWatchlist()->updateOrCreate(
            ['stock_id' => $stock->id],
            [
                'alert_price' => $request->alert_price,
                'alert_type' => $request->alert_type,
            ]
        );

        return back()->with('success', "Price alert for {$stock->symbol} updated successfully.");
    }

    /**
     * Remove from watchlist
     */
    public function removeFromWatchlist(Stock $stock)
    {
        $user = Auth::user();
        
        $user->stockWatchlist()
            ->where('stock_id', $stock->id)
            ->delete();

        return back()->with('success', "{$stock->symbol} removed from your watchlist.");
    }

    /**
     * Get chart data for a stock (1M period only)
     */
    public function getStockChartData($symbol, $period = '1m')
    {
        // Get 1 month of historical data
        $startDate = Carbon::now()->subMonth();
        
        $history = StockPriceHistory::where('symbol', $symbol)
            ->where('timestamp', '>=', $startDate)
            ->orderBy('timestamp', 'asc')
            ->limit(30)
            ->get(['close', 'timestamp']);

        if ($history->isEmpty()) {
            // Return empty chart data if no history
            return [
                'labels' => [],
                'datasets' => [
                    [
                        'label' => 'Price',
                        'data' => [],
                        'borderColor' => '#3B82F6',
                        'backgroundColor' => '#3B82F620',
                        'fill' => false,
                        'tension' => 0.4
                    ]
                ]
            ];
        }
        
        // Prepare chart data
        $labels = [];
        $prices = [];
        
        foreach ($history as $record) {
            $labels[] = $record->timestamp->format('M j');
            $prices[] = $record->close;
        }
        
        // Determine line color based on price trend
        $firstPrice = $prices[0];
        $lastPrice = end($prices);
        $lineColor = $lastPrice >= $firstPrice ? '#10B981' : '#EF4444'; // Green if up, red if down
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Price',
                    'data' => $prices,
                    'borderColor' => $lineColor,
                    'backgroundColor' => str_replace(')', ', 0.1)', $lineColor),
                    'fill' => true,
                    'tension' => 0.4
                ]
            ]
        ];
    }
}
