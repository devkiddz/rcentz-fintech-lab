<?php

namespace App\Services;

use App\Models\PrivateInvestmentHolding;
use App\Models\PrivateInvestmentTransaction;
use App\Models\User;
use Illuminate\Support\Collection;

class PrivateInvestmentPortfolioService
{
    public function buildForUser(User $user): array
    {
        $holdings = PrivateInvestmentHolding::query()
            ->with('instrument')
            ->where('user_id', $user->id)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderByDesc('updated_at')
            ->get();

        $transactions = PrivateInvestmentTransaction::query()
            ->with('instrument')
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->latest('executed_at')
            ->latest('id')
            ->get();

        $transactionsByHolding = $transactions
            ->filter(fn ($transaction) => $transaction->holding_id !== null)
            ->groupBy('holding_id');

        $positions = $holdings->map(function (PrivateInvestmentHolding $holding) use ($transactionsByHolding) {
            /** @var Collection<int, PrivateInvestmentTransaction> $activity */
            $activity = $transactionsByHolding->get($holding->id, collect());

            $subscriptions = round((float) $activity
                ->where('type', 'subscription')
                ->sum('gross_amount'), 2);

            $redemptions = round((float) $activity
                ->where('type', 'redemption')
                ->sum('net_amount'), 2);

            $distributions = round((float) $activity
                ->where('type', 'distribution')
                ->sum('net_amount'), 2);

            $deductions = round((float) $activity
                ->where('type', 'deduction')
                ->sum('net_amount'), 2);

            $fees = round((float) $activity->sum('fee'), 2);

            $unrealized = round((float) $holding->unrealized_profit_loss, 2);
            $realized = round((float) $holding->realized_profit_loss, 2);

            // Fees are already reflected in the holding/order math:
            // subscription fees reduce deployed value and redemption fees reduce realized P/L.
            // Do not subtract them again here.
            $netPerformance = round(
                $unrealized + $realized + $distributions - $deductions,
                2
            );

            $returnPercent = $subscriptions > 0
                ? round(($netPerformance / $subscriptions) * 100, 4)
                : 0.0;

            return [
                'holding' => $holding,
                'instrument' => $holding->instrument,
                'status' => $holding->status,
                'capital_at_work' => round((float) $holding->cost_basis, 2),
                'total_subscribed' => $subscriptions,
                'current_value' => round((float) $holding->current_value, 2),
                'unrealized_profit_loss' => $unrealized,
                'realized_profit_loss' => $realized,
                'distributions' => $distributions,
                'deductions' => $deductions,
                'redemptions' => $redemptions,
                'fees_paid' => $fees,
                'net_performance' => $netPerformance,
                'return_percent' => $returnPercent,
                'recent_activity' => $activity->take(5)->values(),
            ];
        })->values();

        $activePositions = $positions->filter(
            fn (array $position) => $position['status'] === 'active'
                && (float) $position['holding']->units > 0
        );

        $totalSubscribed = round((float) $transactions
            ->where('type', 'subscription')
            ->sum('gross_amount'), 2);

        $totalDistributions = round((float) $transactions
            ->where('type', 'distribution')
            ->sum('net_amount'), 2);

        $totalDeductions = round((float) $transactions
            ->where('type', 'deduction')
            ->sum('net_amount'), 2);

        $realized = round((float) $positions->sum('realized_profit_loss'), 2);
        $unrealized = round((float) $activePositions->sum('unrealized_profit_loss'), 2);
        $netPerformance = round(
            $unrealized + $realized + $totalDistributions - $totalDeductions,
            2
        );

        $wallet = $user->wallet;
        $walletAvailable = $wallet
            ? max(0, round((float) $wallet->balance - (float) $wallet->reserved_balance, 2))
            : 0.0;

        $summary = [
            'active_holdings' => $activePositions->count(),
            'capital_at_work' => round((float) $activePositions->sum('capital_at_work'), 2),
            'current_value' => round((float) $activePositions->sum('current_value'), 2),
            'unrealized_profit_loss' => $unrealized,
            'realized_profit_loss' => $realized,
            'distributions' => $totalDistributions,
            'deductions' => $totalDeductions,
            'net_performance' => $netPerformance,
            'total_subscribed' => $totalSubscribed,
            'return_percent' => $totalSubscribed > 0
                ? round(($netPerformance / $totalSubscribed) * 100, 4)
                : 0.0,
            'available_wallet' => $walletAvailable,
        ];

        return [
            'positions' => $positions,
            'summary' => $summary,
            'activity' => $transactions->take(12)->values(),
        ];
    }
}
