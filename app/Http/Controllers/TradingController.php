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
    public function executeBuy(Request $request, Stock $stock, FinancialActivityService $activity, CopyTradingService $copyTrading, StockTradePlanService $tradePlans)
    {
        if (!$stock->is_active) {
            abort(404);
        }

        $request->validate([
            'quantity' => 'required|numeric|min:1|max:10000',
            'plan_duration_minutes' => 'nullable|integer|min:0|max:10080',
            'plan_mode' => 'nullable|in:reminder,automatic',
        ]);

        $user = Auth::user();
        $wallet = $user->wallet;
        $quantity = $request->quantity;
        $pricePerShare = $stock->current_price;
        $totalCost = $quantity * $pricePerShare;
        $fee = 0.00; // Could be calculated based on amount
        $totalAmount = $totalCost + $fee;

        try {
            DB::beginTransaction();

            $wallet = $user->wallet()->lockForUpdate()->firstOrFail();
            if (! $wallet->canWithdraw($totalAmount)) throw new \RuntimeException('Insufficient available balance.');
            $before = $activity->snapshot($wallet);
            $reference = $activity->reference('STK-BUY');

            // Create wallet transaction
            $walletTransaction = $wallet->transactions()->create([
                'payment_method_id' => 1, // Default payment method
                'type' => 'investment',
                'direction' => 'debit',
                'amount' => $totalAmount,
                'fee' => $fee,
                'status' => 'completed',
                'reference_id' => $reference,
                'description' => "Purchase of {$quantity} shares of {$stock->symbol}",
            ]);

            // Create stock transaction
            $stockTransaction = StockTransaction::create([
                'user_id' => $user->id,
                'stock_id' => $stock->id,
                'wallet_transaction_id' => $walletTransaction->id,
                'type' => 'buy',
                'quantity' => $quantity,
                'price_per_share' => $pricePerShare,
                'total_amount' => $totalAmount,
                'fee' => $fee,
                'status' => 'completed',
                'executed_at' => now(),
            ]);

            // Update or create holding
            $holding = $user->stockHoldings()
                ->where('stock_id', $stock->id)
                ->first();

            if ($holding) {
                // Update existing holding
                $totalQuantity = $holding->quantity + $quantity;
                $totalInvested = $holding->total_invested + $totalCost;
                $averageBuyPrice = $totalInvested / $totalQuantity;

                $holding->update([
                    'quantity' => $totalQuantity,
                    'average_buy_price' => $averageBuyPrice,
                    'total_invested' => $totalInvested,
                    'current_value' => $totalQuantity * $stock->current_price,
                    'unrealized_gain_loss' => ($totalQuantity * $stock->current_price) - $totalInvested,
                    'unrealized_gain_loss_percentage' => (($totalQuantity * $stock->current_price) - $totalInvested) / $totalInvested * 100,
                ]);
            } else {
                // Create new holding
                $user->stockHoldings()->create([
                    'stock_id' => $stock->id,
                    'quantity' => $quantity,
                    'average_buy_price' => $pricePerShare,
                    'total_invested' => $totalCost,
                    'current_value' => $quantity * $stock->current_price,
                    'unrealized_gain_loss' => 0,
                    'unrealized_gain_loss_percentage' => 0,
                ]);
            }

            // Deduct funds from wallet
            $wallet->deductFunds($totalAmount);
            $activity->record($user,'stock.buy','Bought '.$stock->symbol,'Purchased '.$quantity.' shares of '.$stock->symbol.'.',$reference,'completed','debit',(float)$totalAmount,$wallet,$walletTransaction,null,$before,['stock_id'=>$stock->id,'quantity'=>(float)$quantity],'user',$user->id);

            // Create notification for successful stock purchase
            NotificationService::createInvestmentSuccessNotification(
                $user,
                $stock->symbol,
                $totalCost
            );

            // Calculate total portfolio value for email
            $totalPortfolioValue = $user->stockHoldings->sum('current_value') + ($quantity * $stock->current_price);

            // Send stock purchase email
            Mail::to($user->email)->send(new StockBuyEmail($user, $stockTransaction, $totalPortfolioValue));

            DB::commit();

            // Provider trades are mirrored only after the provider trade commits.
            // A follower failure can never roll back the provider's own trade.
            try { $copyTrading->mirrorCompletedTrade($stockTransaction); } catch (\Throwable $e) { \Log::warning('Copy mirroring failed after provider buy', ['trade_id' => $stockTransaction->id, 'error' => $e->getMessage()]); }
            $tradePlans->createForTransaction($stockTransaction, $request->only(['plan_duration_minutes','plan_mode']));

            return redirect()->route('trading.portfolio')
                ->with('success', "Successfully purchased {$quantity} shares of {$stock->symbol} for \${$totalCost}.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to process purchase. Please try again.']);
        }
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
    public function executeSell(Request $request, Stock $stock, FinancialActivityService $activity, CopyTradingService $copyTrading, StockTradePlanService $tradePlans)
    {
        if (!$stock->is_active) {
            abort(404);
        }

        $user = Auth::user();
        $holding = $user->stockHoldings()
            ->where('stock_id', $stock->id)
            ->first();

        if (!$holding) {
            return redirect()->route('trading.portfolio')
                ->with('error', 'You do not have any holdings in this stock.');
        }

        $request->validate([
            'quantity' => 'required|numeric|min:1|max:' . $holding->quantity,
            'plan_duration_minutes' => 'nullable|integer|min:0|max:10080',
            'plan_mode' => 'nullable|in:reminder,automatic',
        ]);

        $quantityToSell = $request->quantity;
        $pricePerShare = $stock->current_price;
        $totalAmount = $quantityToSell * $pricePerShare;
        $fee = 0.00; // Could be calculated based on amount
        $netAmount = $totalAmount - $fee;

        try {
            DB::beginTransaction();

            $wallet=$user->wallet()->lockForUpdate()->firstOrFail(); $before=$activity->snapshot($wallet); $reference=$activity->reference('STK-SELL');
            // Create wallet transaction
            $walletTransaction = $wallet->transactions()->create([
                'payment_method_id' => 1, // Default payment method
                'type' => 'investment',
                'direction' => 'credit',
                'amount' => $netAmount,
                'fee' => $fee,
                'status' => 'completed',
                'reference_id' => $reference,
                'description' => "Sale of {$quantityToSell} shares of {$stock->symbol}",
            ]);

            // Create stock transaction
            $stockTransaction = StockTransaction::create([
                'user_id' => $user->id,
                'stock_id' => $stock->id,
                'wallet_transaction_id' => $walletTransaction->id,
                'type' => 'sell',
                'quantity' => $quantityToSell,
                'price_per_share' => $pricePerShare,
                'total_amount' => $totalAmount,
                'fee' => $fee,
                'status' => 'completed',
                'executed_at' => now(),
            ]);

            // Update holding
            $remainingQuantity = $holding->quantity - $quantityToSell;
            
            if ($remainingQuantity > 0) {
                // Update existing holding
                $proportionSold = $quantityToSell / $holding->quantity;
                $costBasisSold = $holding->total_invested * $proportionSold;
                $remainingInvested = $holding->total_invested - $costBasisSold;

                $holding->update([
                    'quantity' => $remainingQuantity,
                    'total_invested' => $remainingInvested,
                    'current_value' => $remainingQuantity * $stock->current_price,
                    'unrealized_gain_loss' => ($remainingQuantity * $stock->current_price) - $remainingInvested,
                    'unrealized_gain_loss_percentage' => $remainingInvested > 0 ? (($remainingQuantity * $stock->current_price) - $remainingInvested) / $remainingInvested * 100 : 0,
                ]);
            } else {
                // Delete holding if all shares sold
                $holding->delete();
            }

            // Add funds to wallet
            $wallet->addFunds($netAmount);
            $activity->record($user,'stock.sell','Sold '.$stock->symbol,'Sold '.$quantityToSell.' shares of '.$stock->symbol.'.',$reference,'completed','credit',(float)$netAmount,$wallet,$walletTransaction,null,$before,['stock_id'=>$stock->id,'quantity'=>(float)$quantityToSell],'user',$user->id);

            // Create notification for successful stock sale
            NotificationService::createInvestmentSuccessNotification(
                $user,
                $stock->symbol . ' (Sale)',
                $netAmount
            );

            // Calculate total portfolio value for email
            $totalPortfolioValue = $user->stockHoldings->sum('current_value');

            // Send stock sale email
            Mail::to($user->email)->send(new StockSellEmail($user, $stockTransaction, $totalPortfolioValue));

            DB::commit();

            try { $copyTrading->mirrorCompletedTrade($stockTransaction); } catch (\Throwable $e) { \Log::warning('Copy mirroring failed after provider sale', ['trade_id' => $stockTransaction->id, 'error' => $e->getMessage()]); }
            $tradePlans->createForTransaction($stockTransaction, $request->only(['plan_duration_minutes','plan_mode']));

            return redirect()->route('trading.portfolio')
                ->with('success', "Successfully sold {$quantityToSell} shares of {$stock->symbol} for \${$netAmount}.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to process sale. Please try again.']);
        }
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
