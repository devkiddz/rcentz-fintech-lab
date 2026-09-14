<?php

namespace App\Http\Controllers;

use App\Mail\InvestmentBuyEmail;
use App\Mail\InvestmentSellEmail;
use App\Models\InvestmentHolding;
use App\Models\InvestmentPlan;
use App\Models\InvestmentTransaction;
use App\Models\InvestmentWatchlist;
use App\Models\WalletTransaction;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class InvestmentController extends Controller
{
    /**
     * Show buy investment form
     */
    public function buy(InvestmentPlan $plan)
    {
        if (!$plan->is_active) {
            abort(404);
        }

        $user = Auth::user();
        $wallet = $user->wallet;
        $userHolding = $user->investmentHoldings()
            ->where('investment_plan_id', $plan->id)
            ->first();

        return view('investments.buy', compact('plan', 'wallet', 'userHolding'));
    }

    /**
     * Execute buy investment
     */
    public function executeBuy(Request $request, InvestmentPlan $plan)
    {
        if (!$plan->is_active) {
            abort(404);
        }

        $request->validate([
            'amount' => 'required|numeric|min:' . $plan->minimum_investment . '|max:100000',
        ]);

        $user = Auth::user();
        $wallet = $user->wallet;
        $amount = $request->amount;

        // Calculate units to buy
        $units = $amount / $plan->nav;
        $fee = 0.00; // Could be calculated based on amount or plan
        $totalCost = $amount + $fee;

        // Check if user has sufficient funds
        if (!$wallet->canWithdraw($totalCost)) {
            return back()->withErrors(['amount' => 'Insufficient funds in wallet.']);
        }

        try {
            DB::beginTransaction();

            // Create wallet transaction
            $walletTransaction = $wallet->transactions()->create([
                'payment_method_id' => 1, // Default payment method
                'type' => 'investment',
                'amount' => $totalCost,
                'fee' => $fee,
                'status' => 'completed',
                'description' => "Investment in {$plan->name}",
            ]);

            // Create investment transaction
            $investmentTransaction = InvestmentTransaction::create([
                'user_id' => $user->id,
                'investment_plan_id' => $plan->id,
                'wallet_transaction_id' => $walletTransaction->id,
                'type' => 'buy',
                'units' => $units,
                'nav_at_transaction' => $plan->nav,
                'total_amount' => $totalCost,
                'fee' => $fee,
                'status' => 'completed',
                'executed_at' => now(),
            ]);

            // Update or create holding
            $holding = $user->investmentHoldings()
                ->where('investment_plan_id', $plan->id)
                ->first();

            if ($holding) {
                // Update existing holding
                $totalUnits = $holding->units + $units;
                $totalInvested = $holding->total_invested + $amount;
                $averageCost = $totalInvested / $totalUnits;

                $holding->update([
                    'units' => $totalUnits,
                    'average_cost' => $averageCost,
                    'total_invested' => $totalInvested,
                    'current_value' => $totalUnits * $plan->nav,
                    'unrealized_gain_loss' => ($totalUnits * $plan->nav) - $totalInvested,
                    'unrealized_gain_loss_percentage' => (($totalUnits * $plan->nav) - $totalInvested) / $totalInvested * 100,
                ]);
            } else {
                // Create new holding
                $user->investmentHoldings()->create([
                    'investment_plan_id' => $plan->id,
                    'units' => $units,
                    'average_cost' => $plan->nav,
                    'total_invested' => $amount,
                    'current_value' => $units * $plan->nav,
                    'unrealized_gain_loss' => 0,
                    'unrealized_gain_loss_percentage' => 0,
                ]);
            }

            // Deduct funds from wallet
            $wallet->deductFunds($totalCost);

            // Create notification for successful investment purchase
            NotificationService::createInvestmentSuccessNotification(
                $user,
                $plan->name,
                $amount
            );

            // Calculate total portfolio value for email
            $totalPortfolioValue = $user->investmentHoldings->sum('current_value') + ($units * $plan->nav);

            // Send investment purchase email
            Mail::to($user->email)->send(new InvestmentBuyEmail($user, $investmentTransaction, $totalPortfolioValue));

            DB::commit();

            return redirect()->route('portfolio.index')
                ->with('success', "Successfully invested \${$amount} in {$plan->name}.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to process investment. Please try again.']);
        }
    }

    /**
     * Show sell investment form
     */
    public function sell(InvestmentPlan $plan)
    {
        if (!$plan->is_active) {
            abort(404);
        }

        $user = Auth::user();
        $holding = $user->investmentHoldings()
            ->where('investment_plan_id', $plan->id)
            ->first();

        if (!$holding) {
            return redirect()->route('portfolio.index')
                ->withErrors(['error' => 'You do not have any holdings in this investment plan.']);
        }

        return view('investments.sell', compact('plan', 'holding'));
    }

    /**
     * Execute sell investment
     */
    public function executeSell(Request $request, InvestmentPlan $plan)
    {
        if (!$plan->is_active) {
            abort(404);
        }

        $user = Auth::user();
        $holding = $user->investmentHoldings()
            ->where('investment_plan_id', $plan->id)
            ->first();

        if (!$holding) {
            return redirect()->route('portfolio.index')
                ->withErrors(['error' => 'You do not have any holdings in this investment plan.']);
        }

        $request->validate([
            'units' => 'required|numeric|min:0.000001|max:' . $holding->units,
        ]);

        $unitsToSell = $request->units;
        $amount = $unitsToSell * $plan->nav;
        $fee = 0.00; // Could be calculated based on amount
        $netAmount = $amount - $fee;

        try {
            DB::beginTransaction();

            // Create wallet transaction
            $walletTransaction = $user->wallet->transactions()->create([
                'payment_method_id' => 1, // Default payment method
                'type' => 'investment',
                'amount' => $netAmount,
                'fee' => $fee,
                'status' => 'completed',
                'description' => "Sale of {$plan->name}",
            ]);

            // Create investment transaction
            $investmentTransaction = InvestmentTransaction::create([
                'user_id' => $user->id,
                'investment_plan_id' => $plan->id,
                'wallet_transaction_id' => $walletTransaction->id,
                'type' => 'sell',
                'units' => $unitsToSell,
                'nav_at_transaction' => $plan->nav,
                'total_amount' => $amount,
                'fee' => $fee,
                'status' => 'completed',
                'executed_at' => now(),
            ]);

            // Update holding
            $remainingUnits = $holding->units - $unitsToSell;
            
            if ($remainingUnits > 0) {
                // Update existing holding
                $proportionSold = $unitsToSell / $holding->units;
                $costBasisSold = $holding->total_invested * $proportionSold;
                $remainingInvested = $holding->total_invested - $costBasisSold;

                $holding->update([
                    'units' => $remainingUnits,
                    'total_invested' => $remainingInvested,
                    'current_value' => $remainingUnits * $plan->nav,
                    'unrealized_gain_loss' => ($remainingUnits * $plan->nav) - $remainingInvested,
                    'unrealized_gain_loss_percentage' => $remainingInvested > 0 ? (($remainingUnits * $plan->nav) - $remainingInvested) / $remainingInvested * 100 : 0,
                ]);
            } else {
                // Delete holding if all units sold
                $holding->delete();
            }

            // Add funds to wallet
            $user->wallet->addFunds($netAmount);

            // Create notification for successful investment sale
            NotificationService::createInvestmentSuccessNotification(
                $user,
                $plan->name . ' (Sale)',
                $netAmount
            );

            // Calculate total portfolio value for email
            $totalPortfolioValue = $user->investmentHoldings->sum('current_value');

            // Send investment sale email
            Mail::to($user->email)->send(new InvestmentSellEmail($user, $investmentTransaction, $totalPortfolioValue));

            DB::commit();

            return redirect()->route('portfolio.index')
                ->with('success', "Successfully sold {$unitsToSell} units of {$plan->name} for \${$netAmount}.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to process sale. Please try again.']);
        }
    }

    /**
     * Show user portfolio
     */
    public function portfolio()
    {
        $user = Auth::user();
        
        $holdings = $user->investmentHoldings()
            ->with('investmentPlan')
            ->orderBy('current_value', 'desc')
            ->get();

        $totalInvested = $holdings->sum('total_invested');
        $totalCurrentValue = $holdings->sum('current_value');
        $totalGainLoss = $totalCurrentValue - $totalInvested;
        $totalGainLossPercentage = $totalInvested > 0 ? ($totalGainLoss / $totalInvested) * 100 : 0;

        $recentTransactions = $user->investmentTransactions()
            ->with('investmentPlan')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('portfolio.index', compact(
            'holdings',
            'totalInvested',
            'totalCurrentValue',
            'totalGainLoss',
            'totalGainLossPercentage',
            'recentTransactions'
        ));
    }

    /**
     * Show portfolio holdings
     */
    public function holdings()
    {
        $user = Auth::user();
        
        $holdings = $user->investmentHoldings()
            ->with('investmentPlan')
            ->orderBy('current_value', 'desc')
            ->get();

        $totalInvested = $holdings->sum('total_invested');
        $totalCurrentValue = $holdings->sum('current_value');
        $totalGainLoss = $totalCurrentValue - $totalInvested;
        $totalGainLossPercentage = $totalInvested > 0 ? ($totalGainLoss / $totalInvested) * 100 : 0;

        return view('portfolio.holdings', compact(
            'holdings',
            'totalInvested',
            'totalCurrentValue',
            'totalGainLoss',
            'totalGainLossPercentage'
        ));
    }

    /**
     * Show portfolio transactions
     */
    public function transactions(Request $request)
    {
        $user = Auth::user();
        
        $query = $user->investmentTransactions()
            ->with('investmentPlan');

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

        return view('portfolio.transactions', compact('transactions'));
    }

    /**
     * Show portfolio analytics
     */
    public function analytics()
    {
        $user = Auth::user();
        
        $holdings = $user->investmentHoldings()
            ->with('investmentPlan')
            ->orderBy('current_value', 'desc')
            ->get();

        $totalInvested = $holdings->sum('total_invested');
        $totalCurrentValue = $holdings->sum('current_value');
        $totalGainLoss = $totalCurrentValue - $totalInvested;
        $totalGainLossPercentage = $totalInvested > 0 ? ($totalGainLoss / $totalInvested) * 100 : 0;

        $recentTransactions = $user->investmentTransactions()
            ->with('investmentPlan')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('portfolio.analytics', compact(
            'holdings',
            'totalInvested',
            'totalCurrentValue',
            'totalGainLoss',
            'totalGainLossPercentage',
            'recentTransactions'
        ));
    }

    /**
     * Show watchlist
     */
    public function watchlist()
    {
        $user = Auth::user();
        
        $watchlist = $user->investmentWatchlist()
            ->with('investmentPlan')
            ->get();

        return view('portfolio.watchlist', compact('watchlist'));
    }

    /**
     * Add to watchlist
     */
    public function addToWatchlist(Request $request, InvestmentPlan $plan)
    {
        $user = Auth::user();
        
        $request->validate([
            'alert_nav' => 'nullable|numeric|min:0',
            'alert_type' => 'nullable|in:above,below',
        ]);

        $user->investmentWatchlist()->updateOrCreate(
            ['investment_plan_id' => $plan->id],
            [
                'alert_nav' => $request->alert_nav,
                'alert_type' => $request->alert_type,
            ]
        );

        return back()->with('success', "{$plan->name} added to your watchlist.");
    }

    /**
     * Remove from watchlist
     */
    public function removeFromWatchlist(InvestmentPlan $plan)
    {
        $user = Auth::user();
        
        $user->investmentWatchlist()
            ->where('investment_plan_id', $plan->id)
            ->delete();

        return back()->with('success', "{$plan->name} removed from your watchlist.");
    }
}
