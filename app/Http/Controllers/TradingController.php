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
use App\Services\MarketTradeContractEngine;
use App\Services\MarketPriceRouter;
use App\Services\PortfolioValuationService;
use App\Services\StockAnalysisService;
use App\Models\TradePosition;
use App\Models\StockTradePlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $marketplace = app(MarketPriceRouter::class)->activeMarketplace();
        $wallet = $user->wallet;
        $userHolding = $user->stockHoldings()
            ->where('stock_id', $stock->id)
            ->where('marketplace', $marketplace)
            ->first();

        // V5.6 active-position workstation controls.
        // The workstation manages one concrete trade contract at a time.
        // If the user has bought the same stock more than once, the latest
        // still-open manual contract is treated as the current position here;
        // the full Position Desk remains available for all contracts.
        $currentPosition = TradePosition::with('stock')
            ->where('user_id', $user->id)
            ->where('stock_id', $stock->id)
            ->where('marketplace', $marketplace)
            ->whereNotIn('context_type', ['copy_relationship', 'trading_bot'])
            ->whereIn('status', ['open', 'exit_queued'])
            ->where('open_quantity', '>', 0)
            ->latest('opened_at')
            ->first();

        $activePositionCount = TradePosition::query()
            ->where('user_id', $user->id)
            ->where('stock_id', $stock->id)
            ->where('marketplace', $marketplace)
            ->whereNotIn('context_type', ['copy_relationship', 'trading_bot'])
            ->whereIn('status', ['open', 'exit_queued'])
            ->where('open_quantity', '>', 0)
            ->count();

        // Legacy chart payload retained while the new analysis engine owns the workstation chart.
        $chartData = $this->getStockChartData($stock->symbol);
        $analysis = app(StockAnalysisService::class)->forStockInMarketplace($stock, $marketplace);

        return view('trading.buy', compact(
            'stock',
            'wallet',
            'userHolding',
            'chartData',
            'analysis',
            'currentPosition',
            'activePositionCount'
        ));
    }

    /**
     * Execute buy stock
     */
    public function executeBuy(
        Request $request,
        Stock $stock,
        MarketTradeContractEngine $marketTrades,
        CopyTradingService $copyTrading
    ) {
        // V5.10 canonical market-contract wiring.
        if (! $stock->is_active) {
            abort(404);
        }

        $data = $request->validate([
            'quantity' => 'required|numeric|min:1|max:10000',
            'plan_duration_minutes' => 'nullable|integer|min:0|max:10080',
            'plan_mode' => 'nullable|in:reminder,automatic',
            'stop_loss_percent' => 'nullable|numeric|min:0.01|max:100',
            'take_profit_percent' => 'nullable|numeric|min:0.01|max:100',
        ]);

        $user = Auth::user();
        $quantity = (float) $data['quantity'];

        try {
            // One orchestration boundary now owns BUY + living position creation.
            // If the position cannot be created, the financial BUY is rolled back too.
            $stockTransaction = $marketTrades->openLong(
                $user,
                $stock,
                $quantity,
                'stock_trade',
                'manual_trade',
                [
                    'stop_loss_percent' => $data['stop_loss_percent'] ?? null,
                    'take_profit_percent' => $data['take_profit_percent'] ?? null,
                    'duration_minutes' => ! empty($data['plan_duration_minutes'])
                        ? (int) $data['plan_duration_minutes']
                        : null,
                ],
                null,
                null,
                null,
                'user',
                $user->id
            );
        } catch (\Throwable $e) {
            \Log::warning('Canonical managed stock buy failed', [
                'user_id' => $user->id,
                'stock_id' => $stock->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'error' => $e->getMessage() ?: 'The managed trade could not be opened. No partial purchase was kept.',
            ])->withInput();
        }

        // Notifications, email and copy mirroring remain post-commit side effects.
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
        $marketplace = app(MarketPriceRouter::class)->activeMarketplace();
        $wallet = $user->wallet;
        $holding = $user->stockHoldings()
            ->where('stock_id', $stock->id)
            ->where('marketplace', $marketplace)
            ->first();

        // A Live holding and Controlled holding are different exposures.
        if (!$holding) {
            return redirect()->route('trading.portfolio')
                ->with('error', 'You do not have any '.strtoupper($marketplace).' holdings in this stock.');
        }
        
        // Legacy chart payload retained while the new analysis engine owns the workstation chart.
        $chartData = $this->getStockChartData($stock->symbol, '1m');
        $analysis = app(StockAnalysisService::class)->forStockInMarketplace($stock, $marketplace);
        
        return view('trading.sell', compact('stock', 'wallet', 'holding', 'chartData', 'analysis'));
    }

    /**
     * Execute sell stock
     */
    public function executeSell(
        Request $request,
        Stock $stock,
        MarketTradeContractEngine $marketTrades,
        CopyTradingService $copyTrading
    ) {
        // V5.10 canonical market-contract wiring.
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
            // Financial SELL + TradePosition reconciliation are one transaction now.
            // A reconciliation failure cannot leave a completed sell detached from
            // the position lifecycle.
            $stockTransaction = $marketTrades->sellExposure(
                $user,
                $stock,
                $quantity,
                'stock_trade',
                'manual_close',
                null,
                null,
                null,
                null,
                'user',
                $user->id
            );
        } catch (\Throwable $e) {
            \Log::warning('Canonical managed stock sell failed', [
                'user_id' => $user->id,
                'stock_id' => $stock->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'error' => $e->getMessage() ?: 'The managed sale could not be completed. No partial sell was kept.',
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
    public function portfolio(
        MarketPriceRouter $prices,
        PortfolioValuationService $valuation
    )
    {
        $user = Auth::user();
        $activeMarketplace = $prices->activeMarketplace();

        // A user's Live and Controlled exposures are independent portfolios.
        // Revalue only the active portfolio against its own price authority.
        $holdings = $valuation->syncUser($user, $activeMarketplace);

        $totalInvested = (float) $holdings->sum('total_invested');
        $totalCurrentValue = (float) $holdings->sum('current_value');
        $totalGainLoss = $totalCurrentValue - $totalInvested;
        $totalGainLossPercentage = $totalInvested > 0
            ? ($totalGainLoss / $totalInvested) * 100
            : 0;

        $tradePlans = collect(); // Legacy rows stay readable; new entries use TradePosition.
        $positions = TradePosition::with(['stock','events' => fn ($q) => $q->latest()->limit(3)])
            ->where('user_id', $user->id)
            ->where('marketplace', $activeMarketplace)
            ->whereIn('status', ['open','exit_queued'])
            ->orderBy('expires_at')
            ->get();

        $recentTransactions = $user->stockTransactions()
            ->with('stock')
            ->where('marketplace', $activeMarketplace)
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
            'tradePlans',
            'positions',
            'activeMarketplace'
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
