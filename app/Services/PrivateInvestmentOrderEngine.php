<?php

namespace App\Services;

use App\Models\PrivateInvestmentHolding;
use App\Models\PrivateInvestmentInstrument;
use App\Models\PrivateInvestmentTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class PrivateInvestmentOrderEngine
{
    public function subscribe(
        User $user,
        PrivateInvestmentInstrument $instrument,
        float $amount,
        ?int $actorUserId = null,
        string $source = 'customer',
        ?string $idempotencyKey = null
    ): PrivateInvestmentTransaction {
        $idempotencyKey = $this->normalizeIdempotencyKey($idempotencyKey);

        return DB::transaction(function () use ($user, $instrument, $amount, $actorUserId, $source, $idempotencyKey) {
            $instrument = PrivateInvestmentInstrument::query()->lockForUpdate()->findOrFail($instrument->id);
            $wallet = Wallet::query()->where('user_id', $user->id)->lockForUpdate()->first();

            if (! $wallet) {
                throw ValidationException::withMessages(['amount' => 'This customer does not have a wallet.']);
            }

            if ($idempotencyKey !== null) {
                $existing = PrivateInvestmentTransaction::query()
                    ->where('user_id', $user->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    $this->assertSubscriptionReplay($existing, $instrument, $amount);
                    return $existing;
                }
            }

            if ($instrument->status !== 'active') {
                throw ValidationException::withMessages(['amount' => 'This investment is not currently accepting subscriptions.']);
            }

            $price = (float) $instrument->current_price;
            if ($price <= 0) {
                throw ValidationException::withMessages(['amount' => 'The authoritative investment price is invalid.']);
            }

            $minimum = (float) $instrument->minimum_investment;
            $maximum = (float) $instrument->maximum_investment;

            if ($amount < $minimum) {
                throw ValidationException::withMessages(['amount' => 'Minimum investment is '.currency_symbol().number_format($minimum, 2).'.']);
            }

            if ($maximum > 0 && $amount > $maximum) {
                throw ValidationException::withMessages(['amount' => 'Maximum investment per subscription is '.currency_symbol().number_format($maximum, 2).'.']);
            }

            $availableBalance = max(0, (float) $wallet->balance - (float) $wallet->reserved_balance);
            if ($availableBalance + 0.000001 < $amount) {
                throw ValidationException::withMessages(['amount' => 'Insufficient available wallet balance.']);
            }

            $subscriptionFee = round($amount * ((float) $instrument->subscription_fee_percent / 100), 2);
            $deployedAmount = round($amount - $subscriptionFee, 2);

            if ($deployedAmount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Subscription fees consume the full investment amount.']);
            }

            $units = round($deployedAmount / $price, 6);
            if ($units <= 0) {
                throw ValidationException::withMessages(['amount' => 'Subscription amount is too small for the current price.']);
            }

            if ($units > (float) $instrument->available_units + 0.000001) {
                throw ValidationException::withMessages(['amount' => 'Not enough investment units are currently available.']);
            }

            $holding = PrivateInvestmentHolding::query()
                ->where('user_id', $user->id)
                ->where('instrument_id', $instrument->id)
                ->lockForUpdate()
                ->first();

            $oldUnits = $holding ? (float) $holding->units : 0;
            $oldCost = $holding ? (float) $holding->cost_basis : 0;
            $newUnits = round($oldUnits + $units, 6);
            $newCost = round($oldCost + $amount, 2);
            $average = $newUnits > 0 ? $newCost / $newUnits : $price;
            $currentValue = round($newUnits * $price, 2);
            $unrealized = round($currentValue - $newCost, 2);

            $newLock = (int) $instrument->lock_period_days > 0
                ? now()->addDays((int) $instrument->lock_period_days)
                : null;

            if ($holding) {
                $existingLock = $holding->locked_until;
                if ($existingLock && (! $newLock || $existingLock->greaterThan($newLock))) {
                    $newLock = $existingLock;
                }

                $holding->update([
                    'units' => $newUnits,
                    'average_entry_price' => $average,
                    'cost_basis' => $newCost,
                    'current_value' => $currentValue,
                    'unrealized_profit_loss' => $unrealized,
                    'unrealized_return_percent' => $newCost > 0 ? ($unrealized / $newCost) * 100 : 0,
                    'status' => 'active',
                    'started_at' => $holding->started_at ?? now(),
                    'locked_until' => $newLock,
                    'closed_at' => null,
                ]);
            } else {
                $holding = PrivateInvestmentHolding::query()->create([
                    'user_id' => $user->id,
                    'instrument_id' => $instrument->id,
                    'units' => $newUnits,
                    'average_entry_price' => $average,
                    'cost_basis' => $newCost,
                    'current_value' => $currentValue,
                    'unrealized_profit_loss' => $unrealized,
                    'unrealized_return_percent' => 0,
                    'realized_profit_loss' => 0,
                    'status' => 'active',
                    'started_at' => now(),
                    'locked_until' => $newLock,
                ]);
            }

            $wallet->update(['balance' => round((float) $wallet->balance - $amount, 2)]);
            $instrument->update(['available_units' => max(0, round((float) $instrument->available_units - $units, 6))]);

            $reference = 'PINV-SUB-'.Str::upper(Str::random(12));

            $walletTransaction = WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'type' => 'investment',
                'direction' => 'debit',
                'amount' => $deployedAmount,
                'fee' => $subscriptionFee,
                'status' => 'completed',
                'reference_id' => $reference,
                'description' => 'Private investment subscription: '.$instrument->symbol,
            ]);

            return PrivateInvestmentTransaction::query()->create([
                'user_id' => $user->id,
                'instrument_id' => $instrument->id,
                'holding_id' => $holding->id,
                'type' => 'subscription',
                'units' => $units,
                'price_per_unit' => $price,
                'gross_amount' => round($amount, 2),
                'fee' => $subscriptionFee,
                'net_amount' => $deployedAmount,
                'status' => 'completed',
                'reference' => $reference,
                'idempotency_key' => $idempotencyKey,
                'metadata' => [
                    'source' => $source,
                    'actor_user_id' => $actorUserId,
                    'wallet_transaction_id' => $walletTransaction->id,
                    'locked_until' => $newLock?->toIso8601String(),
                    'idempotency_key' => $idempotencyKey,
                ],
                'executed_at' => now(),
            ]);
        });
    }

    public function redeem(
        User $user,
        PrivateInvestmentInstrument $instrument,
        float $units,
        ?int $actorUserId = null,
        string $source = 'customer',
        ?string $idempotencyKey = null
    ): PrivateInvestmentTransaction {
        $idempotencyKey = $this->normalizeIdempotencyKey($idempotencyKey);

        return DB::transaction(function () use ($user, $instrument, $units, $actorUserId, $source, $idempotencyKey) {
            $instrument = PrivateInvestmentInstrument::query()->lockForUpdate()->findOrFail($instrument->id);
            $wallet = Wallet::query()->where('user_id', $user->id)->lockForUpdate()->first();

            if (! $wallet) {
                throw ValidationException::withMessages(['units' => 'This customer does not have a wallet.']);
            }

            if ($idempotencyKey !== null) {
                $existing = PrivateInvestmentTransaction::query()
                    ->where('user_id', $user->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    $this->assertRedemptionReplay($existing, $instrument, $units);
                    return $existing;
                }
            }

            if ($instrument->status !== 'active') {
                throw ValidationException::withMessages(['units' => 'This investment is not currently accepting redemptions.']);
            }

            $holding = PrivateInvestmentHolding::query()
                ->where('user_id', $user->id)
                ->where('instrument_id', $instrument->id)
                ->lockForUpdate()
                ->first();

            if (! $holding || $holding->status !== 'active' || (float) $holding->units <= 0) {
                throw ValidationException::withMessages(['units' => 'No active holding exists for this investment.']);
            }

            if ($holding->locked_until && now()->lt($holding->locked_until)) {
                throw ValidationException::withMessages([
                    'units' => 'This holding is locked until '.$holding->locked_until->format('M j, Y H:i').'.',
                ]);
            }

            if ($units <= 0 || $units > (float) $holding->units + 0.000001) {
                throw ValidationException::withMessages(['units' => 'Redemption units exceed the active holding.']);
            }

            $price = (float) $instrument->current_price;
            if ($price <= 0) {
                throw ValidationException::withMessages(['units' => 'The authoritative investment price is invalid.']);
            }

            $gross = round($units * $price, 2);
            $fee = round($gross * ((float) $instrument->redemption_fee_percent / 100), 2);
            $net = round($gross - $fee, 2);
            $costRemoved = round($units * (float) $holding->average_entry_price, 2);
            $realized = round($net - $costRemoved, 2);

            $remainingUnits = max(0, round((float) $holding->units - $units, 6));
            $remainingCost = max(0, round((float) $holding->cost_basis - $costRemoved, 2));
            $remainingValue = round($remainingUnits * $price, 2);
            $remainingUnrealized = round($remainingValue - $remainingCost, 2);

            $holding->update([
                'units' => $remainingUnits,
                'cost_basis' => $remainingCost,
                'current_value' => $remainingValue,
                'unrealized_profit_loss' => $remainingUnrealized,
                'unrealized_return_percent' => $remainingCost > 0 ? ($remainingUnrealized / $remainingCost) * 100 : 0,
                'realized_profit_loss' => round((float) $holding->realized_profit_loss + $realized, 2),
                'status' => $remainingUnits > 0 ? 'active' : 'closed',
                'closed_at' => $remainingUnits > 0 ? null : now(),
                'locked_until' => $remainingUnits > 0 ? $holding->locked_until : null,
            ]);

            $wallet->update(['balance' => round((float) $wallet->balance + $net, 2)]);
            $instrument->update([
                'available_units' => min(
                    (float) $instrument->unit_supply,
                    round((float) $instrument->available_units + $units, 6)
                ),
            ]);

            $reference = 'PINV-RED-'.Str::upper(Str::random(12));

            $walletTransaction = WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'type' => 'investment',
                'direction' => 'credit',
                'amount' => $net,
                'fee' => $fee,
                'status' => 'completed',
                'reference_id' => $reference,
                'description' => 'Sale of private investment '.$instrument->symbol,
            ]);

            return PrivateInvestmentTransaction::query()->create([
                'user_id' => $user->id,
                'instrument_id' => $instrument->id,
                'holding_id' => $holding->id,
                'type' => 'redemption',
                'units' => $units,
                'price_per_unit' => $price,
                'gross_amount' => $gross,
                'fee' => $fee,
                'net_amount' => $net,
                'status' => 'completed',
                'reference' => $reference,
                'idempotency_key' => $idempotencyKey,
                'metadata' => [
                    'source' => $source,
                    'actor_user_id' => $actorUserId,
                    'wallet_transaction_id' => $walletTransaction->id,
                    'realized_profit_loss' => $realized,
                    'cost_basis_removed' => $costRemoved,
                    'idempotency_key' => $idempotencyKey,
                ],
                'executed_at' => now(),
            ]);
        });
    }

    private function normalizeIdempotencyKey(?string $key): ?string
    {
        if ($key === null || trim($key) === '') {
            return null;
        }

        $key = trim($key);
        if (strlen($key) > 120) {
            throw new InvalidArgumentException('Private investment idempotency key cannot exceed 120 characters.');
        }

        return $key;
    }

    private function assertSubscriptionReplay(
        PrivateInvestmentTransaction $transaction,
        PrivateInvestmentInstrument $instrument,
        float $amount
    ): void {
        $matches = $transaction->type === 'subscription'
            && (int) $transaction->instrument_id === (int) $instrument->id
            && abs((float) $transaction->gross_amount - $amount) < 0.01;

        if (! $matches) {
            throw new RuntimeException('Idempotency key was already used for a different private investment request.');
        }
    }

    private function assertRedemptionReplay(
        PrivateInvestmentTransaction $transaction,
        PrivateInvestmentInstrument $instrument,
        float $units
    ): void {
        $matches = $transaction->type === 'redemption'
            && (int) $transaction->instrument_id === (int) $instrument->id
            && abs((float) $transaction->units - $units) < 0.000001;

        if (! $matches) {
            throw new RuntimeException('Idempotency key was already used for a different private investment request.');
        }
    }
}
