<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class FinancialOverviewService
{
    /**
     * Build the customer's current financial position from stored state.
     *
     * Important:
     * - available_balance = spendable wallet cash
     * - portfolio_value = current stock + investment holding value
     * - total_assets = cash + portfolio value
     * - invested_capital = cost basis still deployed in holdings
     * - total_return = portfolio value - invested capital
     */
    public function forUser(User $user, int $recentLimit = 8): array
    {
        $wallet = $user->wallet;

        $investmentHoldings = $user->investmentHoldings()
            ->with('investmentPlan')
            ->get();

        $stockHoldings = $user->stockHoldings()
            ->with('stock')
            ->get();

        $walletBalance = (float) ($wallet?->balance ?? 0);
        $reservedBalance = (float) ($wallet?->reserved_balance ?? 0);
        $availableBalance = (float) ($wallet?->available_balance ?? 0);

        $investmentValue = (float) $investmentHoldings->sum('current_value');
        $stockValue = (float) $stockHoldings->sum('current_value');
        $portfolioValue = $investmentValue + $stockValue;

        $investmentCost = (float) $investmentHoldings->sum('total_invested');
        $stockCost = (float) $stockHoldings->sum('total_invested');
        $investedCapital = $investmentCost + $stockCost;

        $totalAssets = $walletBalance + $portfolioValue;
        $totalReturn = $portfolioValue - $investedCapital;
        $returnPercentage = $investedCapital > 0
            ? ($totalReturn / $investedCapital) * 100
            : 0.0;

        $completedTransactions = $wallet
            ? $wallet->transactions()->completed()
            : null;

        $totalCredits = $completedTransactions
            ? (float) (clone $completedTransactions)->credits()->sum('amount')
            : 0.0;

        $totalDebits = $completedTransactions
            ? (float) (clone $completedTransactions)->debits()->sum('amount')
            : 0.0;

        $pendingTransactions = $wallet
            ? $wallet->transactions()->pending()->count()
            : 0;

        $recentTransactions = $wallet
            ? $wallet->transactions()
                ->with('paymentMethod')
                ->latest()
                ->limit($recentLimit)
                ->get()
            : collect();

        return [
            'wallet' => $wallet,
            'investmentHoldings' => $investmentHoldings,
            'stockHoldings' => $stockHoldings,

            'walletBalance' => $walletBalance,
            'reservedBalance' => $reservedBalance,
            'availableBalance' => $availableBalance,
            'investmentValue' => $investmentValue,
            'stockValue' => $stockValue,
            'portfolioValue' => $portfolioValue,
            'investedCapital' => $investedCapital,
            'totalAssets' => $totalAssets,
            'totalReturn' => $totalReturn,
            'returnPercentage' => $returnPercentage,

            'totalCredits' => $totalCredits,
            'totalDebits' => $totalDebits,
            'pendingTransactions' => $pendingTransactions,
            'recentTransactions' => $recentTransactions,

            'allocation' => $this->allocation(
                $totalAssets,
                $availableBalance,
                $stockValue,
                $investmentValue
            ),
        ];
    }

    private function allocation(
        float $totalAssets,
        float $availableBalance,
        float $stockValue,
        float $investmentValue
    ): Collection {
        if ($totalAssets <= 0) {
            return collect([
                ['label' => 'Cash', 'value' => 0.0, 'percentage' => 0.0],
                ['label' => 'Stocks', 'value' => 0.0, 'percentage' => 0.0],
                ['label' => 'Investments', 'value' => 0.0, 'percentage' => 0.0],
            ]);
        }

        return collect([
            [
                'label' => 'Cash',
                'value' => $availableBalance,
                'percentage' => ($availableBalance / $totalAssets) * 100,
            ],
            [
                'label' => 'Stocks',
                'value' => $stockValue,
                'percentage' => ($stockValue / $totalAssets) * 100,
            ],
            [
                'label' => 'Investments',
                'value' => $investmentValue,
                'percentage' => ($investmentValue / $totalAssets) * 100,
            ],
        ]);
    }
}
