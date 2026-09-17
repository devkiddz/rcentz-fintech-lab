<?php

namespace App\Console\Commands;

use App\Models\PrivateInvestmentHolding;
use App\Models\PrivateInvestmentInstrument;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\PrivateInvestmentOrderEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeedPrivateInvestmentDemoHoldings extends Command
{
    protected $signature = 'investment:seed-demo-holdings {--customers=5 : Maximum customer accounts to seed}';
    protected $description = 'Seed realistic private investment holdings into existing non-admin customer accounts for lab testing.';

    public function handle(PrivateInvestmentOrderEngine $orders): int
    {
        $limit = max(1, min(20, (int) $this->option('customers')));

        $customers = User::query()
            ->where('is_admin', false)
            ->whereHas('wallet')
            ->with('wallet')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($customers->isEmpty()) {
            $this->error('No non-admin customer accounts with wallets were found.');
            return self::FAILURE;
        }

        $instruments = PrivateInvestmentInstrument::query()
            ->where('status', 'active')
            ->where('is_visible', true)
            ->orderByDesc('is_featured')
            ->orderBy('id')
            ->get();

        if ($instruments->isEmpty()) {
            $this->error('No active visible private investment instruments were found.');
            return self::FAILURE;
        }

        $preferredAmounts = [2500, 5000, 7500, 10000, 12500];
        $created = 0;
        $skipped = 0;

        foreach ($customers as $customerIndex => $customer) {
            $targetCount = min($instruments->count(), 2 + ($customerIndex % 3));
            $targets = collect();

            for ($slot = 0; $slot < $targetCount; $slot++) {
                $instrument = $instruments[($customerIndex + ($slot * 2)) % $instruments->count()];

                if ($targets->contains('id', $instrument->id)) {
                    continue;
                }

                $alreadyHeld = PrivateInvestmentHolding::query()
                    ->where('user_id', $customer->id)
                    ->where('instrument_id', $instrument->id)
                    ->where('units', '>', 0)
                    ->exists();

                if ($alreadyHeld) {
                    $skipped++;
                    continue;
                }

                $minimum = max(0.01, (float) $instrument->minimum_investment);
                $maximum = (float) $instrument->maximum_investment;
                $preferred = (float) $preferredAmounts[($customerIndex + $slot) % count($preferredAmounts)];
                $amount = max($minimum, $preferred);

                if ($maximum > 0) {
                    $amount = min($amount, $maximum);
                }

                if ($amount + 0.000001 < $minimum) {
                    $this->warn("Skipped {$instrument->symbol} for {$customer->email}: configured maximum is below minimum.");
                    $skipped++;
                    continue;
                }

                $targets->push([
                    'instrument' => $instrument,
                    'amount' => round($amount, 2),
                ]);
            }

            if ($targets->isEmpty()) {
                $this->line("No new demo holdings needed for {$customer->email}.");
                continue;
            }

            $funding = round((float) $targets->sum('amount'), 2);

            DB::transaction(function () use ($customer, $funding) {
                $wallet = Wallet::query()
                    ->where('user_id', $customer->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $wallet->update([
                    'balance' => round((float) $wallet->balance + $funding, 2),
                ]);

                WalletTransaction::query()->create([
                    'wallet_id' => $wallet->id,
                    'type' => 'deposit',
                    'direction' => 'credit',
                    'amount' => $funding,
                    'fee' => 0,
                    'status' => 'completed',
                    'reference_id' => 'PINV-DEMO-FUND-'.Str::upper(Str::random(12)),
                    'description' => 'V5.29 demo investment seed funding',
                ]);
            });

            $this->info("Seed funding {$customer->email}: ".currency_symbol().number_format($funding, 2));

            foreach ($targets as $target) {
                $transaction = $orders->subscribe(
                    $customer,
                    $target['instrument'],
                    $target['amount'],
                    null,
                    'demo_seed'
                );

                $created++;
                $this->line(sprintf(
                    '  + %s → %s %s (%s units)',
                    $customer->name ?: $customer->email,
                    $target['instrument']->symbol,
                    currency_symbol().number_format($target['amount'], 2),
                    number_format((float) $transaction->units, 6)
                ));
            }
        }

        $this->newLine();
        $this->info("Demo investment seeding complete: {$created} new holding subscription(s), {$skipped} existing/invalid target(s) skipped.");
        $this->line('The command is safe to rerun: existing positive holdings are not duplicated.');

        return self::SUCCESS;
    }
}
