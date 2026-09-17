<?php

namespace App\Console\Commands;

use App\Models\PrivateInvestmentHolding;
use App\Models\PrivateInvestmentInstrument;
use App\Models\PrivateInvestmentTransaction;
use App\Models\WalletTransaction;
use Illuminate\Console\Command;

class ReconcilePrivateInvestments extends Command
{
    protected $signature = 'investment:reconcile';
    protected $description = 'Verify private investment holdings, supply, transaction fees, and wallet references.';

    public function handle(): int
    {
        $errors = [];

        foreach (PrivateInvestmentInstrument::query()->get() as $instrument) {
            $heldUnits = (float) PrivateInvestmentHolding::query()
                ->where('instrument_id', $instrument->id)
                ->where('status', 'active')
                ->sum('units');

            $expectedAvailable = max(0, round((float) $instrument->unit_supply - $heldUnits, 6));
            $actualAvailable = round((float) $instrument->available_units, 6);

            if (abs($expectedAvailable - $actualAvailable) > 0.00001) {
                $errors[] = sprintf(
                    '%s supply mismatch: expected available %.6f, found %.6f',
                    $instrument->symbol,
                    $expectedAvailable,
                    $actualAvailable
                );
            }
        }

        PrivateInvestmentHolding::query()
            ->with('instrument')
            ->get()
            ->each(function ($holding) use (&$errors) {
                $price = (float) optional($holding->instrument)->current_price;
                $expectedValue = round((float) $holding->units * $price, 2);
                $expectedPnl = round($expectedValue - (float) $holding->cost_basis, 2);

                if (abs($expectedValue - round((float) $holding->current_value, 2)) > 0.01) {
                    $errors[] = "Holding {$holding->id} current_value mismatch.";
                }

                if (abs($expectedPnl - round((float) $holding->unrealized_profit_loss, 2)) > 0.01) {
                    $errors[] = "Holding {$holding->id} unrealized P/L mismatch.";
                }

                if ($holding->status === 'closed' && abs((float) $holding->units) > 0.000001) {
                    $errors[] = "Holding {$holding->id} is closed but still has units.";
                }
            });

        PrivateInvestmentTransaction::query()
            ->with('user.wallet')
            ->get()
            ->each(function ($transaction) use (&$errors) {
                $gross = round((float) $transaction->gross_amount, 2);
                $fee = round((float) $transaction->fee, 2);
                $net = round((float) $transaction->net_amount, 2);

                if (abs($gross - round($fee + $net, 2)) > 0.01) {
                    $errors[] = "Transaction {$transaction->id} gross/fee/net mismatch.";
                }

                $walletTransaction = WalletTransaction::query()
                    ->where('reference_id', $transaction->reference)
                    ->first();

                if (! $walletTransaction) {
                    $errors[] = "Transaction {$transaction->id} has no wallet transaction with reference {$transaction->reference}.";
                    return;
                }

                $walletId = optional($transaction->user->wallet)->id;
                if ($walletId && (int) $walletTransaction->wallet_id !== (int) $walletId) {
                    $errors[] = "Transaction {$transaction->id} wallet owner mismatch.";
                }

                if ($transaction->type === 'subscription') {
                    $expectedDeployed = round((float) $transaction->units * (float) $transaction->price_per_unit, 2);

                    if (abs($expectedDeployed - $net) > 0.02) {
                        $errors[] = "Subscription {$transaction->id} units/price/net mismatch.";
                    }

                    if ($walletTransaction->direction !== 'debit') {
                        $errors[] = "Subscription {$transaction->id} wallet direction must be debit.";
                    }
                }

                if ($transaction->type === 'redemption') {
                    $expectedGross = round((float) $transaction->units * (float) $transaction->price_per_unit, 2);

                    if (abs($expectedGross - $gross) > 0.02) {
                        $errors[] = "Redemption {$transaction->id} units/price/gross mismatch.";
                    }

                    if ($walletTransaction->direction !== 'credit') {
                        $errors[] = "Redemption {$transaction->id} wallet direction must be credit.";
                    }
                }
            });

        $this->line('Private Investment Reconciliation');
        $this->line('--------------------------------');

        if ($errors) {
            foreach ($errors as $error) {
                $this->error('FAIL: '.$error);
            }

            $this->error(count($errors).' reconciliation issue(s) found.');
            return self::FAILURE;
        }

        $this->info('PASS: holdings, supply, transaction math, and wallet references reconcile.');
        return self::SUCCESS;
    }
}
