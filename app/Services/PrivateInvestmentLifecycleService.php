<?php

namespace App\Services;

use App\Models\PrivateInvestmentAuditLog;
use App\Models\PrivateInvestmentHolding;
use App\Models\PrivateInvestmentInstrument;
use App\Models\PrivateInvestmentLifecycleEvent;
use App\Models\PrivateInvestmentTransaction;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrivateInvestmentLifecycleService
{
    public function apply(
        PrivateInvestmentInstrument $instrument,
        array $payload,
        ?int $actorUserId = null
    ): PrivateInvestmentLifecycleEvent {
        return DB::transaction(function () use ($instrument, $payload, $actorUserId) {
            $instrument = PrivateInvestmentInstrument::query()
                ->lockForUpdate()
                ->findOrFail($instrument->id);

            $type = $payload['type'];
            $mode = $payload['calculation_mode'];
            $value = (float) $payload['value'];
            $effectiveAt = $payload['effective_at'] ?? now();

            $holdings = PrivateInvestmentHolding::query()
                ->where('instrument_id', $instrument->id)
                ->where('status', 'active')
                ->where('units', '>', 0)
                ->with('user')
                ->lockForUpdate()
                ->get();

            if ($holdings->isEmpty()) {
                throw ValidationException::withMessages([
                    'type' => 'This instrument has no active customer holdings to process.',
                ]);
            }

            $event = PrivateInvestmentLifecycleEvent::query()->create([
                'instrument_id' => $instrument->id,
                'actor_user_id' => $actorUserId,
                'type' => $type,
                'calculation_mode' => $mode,
                'value' => $value,
                'total_amount' => 0,
                'affected_holdings' => 0,
                'reason' => $payload['reason'],
                'metadata' => [
                    'source' => 'admin_control_plane',
                    'instrument_price' => (string) $instrument->current_price,
                ],
                'effective_at' => $effectiveAt,
            ]);

            $total = 0.0;
            $affected = 0;

            foreach ($holdings as $holding) {
                $units = (float) $holding->units;
                $currentValue = (float) $holding->current_value;

                $amount = match ($mode) {
                    'fixed_per_unit' => round($units * $value, 2),
                    'percent_current_value' => round($currentValue * ($value / 100), 2),
                    default => throw ValidationException::withMessages([
                        'calculation_mode' => 'Unsupported lifecycle calculation mode.',
                    ]),
                };

                if ($amount <= 0) {
                    continue;
                }

                $wallet = Wallet::query()
                    ->where('user_id', $holding->user_id)
                    ->lockForUpdate()
                    ->first();

                if (! $wallet) {
                    throw ValidationException::withMessages([
                        'type' => 'Customer '.$holding->user_id.' has no wallet. No lifecycle event was applied.',
                    ]);
                }

                $direction = $type === 'distribution' ? 'credit' : 'debit';

                if ($type === 'deduction') {
                    $available = max(0, (float) $wallet->balance - (float) $wallet->reserved_balance);

                    if ($available + 0.000001 < $amount) {
                        $name = $holding->user?->name ?: 'Customer '.$holding->user_id;

                        throw ValidationException::withMessages([
                            'type' => $name.' does not have enough available wallet balance for this deduction. The entire lifecycle event was rolled back.',
                        ]);
                    }
                }

                $newBalance = $type === 'distribution'
                    ? round((float) $wallet->balance + $amount, 2)
                    : round((float) $wallet->balance - $amount, 2);

                $wallet->update(['balance' => $newBalance]);

                $reference = 'PINV-LIFE-'.$event->id.'-'.$holding->id.'-'.Str::upper(Str::random(6));

                $walletTransaction = WalletTransaction::query()->create([
                    'wallet_id' => $wallet->id,
                    'type' => 'investment',
                    'direction' => $direction,
                    'amount' => $amount,
                    'fee' => 0,
                    'status' => 'completed',
                    'reference_id' => $reference,
                    'description' => ($type === 'distribution' ? 'Investment distribution: ' : 'Investment deduction: ').$instrument->symbol,
                ]);

                PrivateInvestmentTransaction::query()->create([
                    'user_id' => $holding->user_id,
                    'instrument_id' => $instrument->id,
                    'holding_id' => $holding->id,
                    'type' => $type,
                    'units' => $units,
                    'price_per_unit' => $units > 0 ? round($amount / $units, 6) : 0,
                    'gross_amount' => $amount,
                    'fee' => 0,
                    'net_amount' => $amount,
                    'status' => 'completed',
                    'reference' => $reference,
                    'metadata' => [
                        'source' => 'admin_lifecycle',
                        'actor_user_id' => $actorUserId,
                        'lifecycle_event_id' => $event->id,
                        'wallet_transaction_id' => $walletTransaction->id,
                        'calculation_mode' => $mode,
                        'configured_value' => $value,
                        'reason' => $payload['reason'],
                    ],
                    'executed_at' => $effectiveAt,
                ]);

                $total = round($total + $amount, 2);
                $affected++;
            }

            if ($affected === 0) {
                throw ValidationException::withMessages([
                    'value' => 'The configured lifecycle value produced no payable amount.',
                ]);
            }

            $event->update([
                'total_amount' => $total,
                'affected_holdings' => $affected,
            ]);

            PrivateInvestmentAuditLog::query()->create([
                'actor_user_id' => $actorUserId,
                'target_user_id' => null,
                'instrument_id' => $instrument->id,
                'action' => 'lifecycle.'.$type,
                'reference' => 'PINV-LIFE-'.$event->id,
                'reason' => $payload['reason'],
                'metadata' => [
                    'lifecycle_event_id' => $event->id,
                    'calculation_mode' => $mode,
                    'configured_value' => $value,
                    'total_amount' => $total,
                    'affected_holdings' => $affected,
                ],
                'occurred_at' => $effectiveAt,
            ]);

            return $event->fresh();
        });
    }
}
