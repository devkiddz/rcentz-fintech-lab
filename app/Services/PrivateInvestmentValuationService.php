<?php

namespace App\Services;

use App\Models\PrivateInvestmentEvent;
use App\Models\PrivateInvestmentHolding;
use App\Models\PrivateInvestmentInstrument;
use App\Models\PrivateInvestmentPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrivateInvestmentValuationService
{
    public function resetAdminTestHistory(PrivateInvestmentInstrument $instrument): void
    {
        DB::transaction(function () use ($instrument) {
            $instrument = PrivateInvestmentInstrument::query()->lockForUpdate()->findOrFail($instrument->id);

            $base = PrivateInvestmentPrice::query()
                ->where('instrument_id', $instrument->id)
                ->where('source', '!=', 'admin_valuation_event')
                ->latest('recorded_at')
                ->first();

            if (! $base) {
                throw ValidationException::withMessages(['reset' => 'No non-admin baseline price exists for this instrument.']);
            }

            PrivateInvestmentPrice::query()
                ->where('instrument_id', $instrument->id)
                ->where('source', 'admin_valuation_event')
                ->delete();

            PrivateInvestmentEvent::query()
                ->where('instrument_id', $instrument->id)
                ->whereNotNull('created_by_user_id')
                ->delete();

            $price = (float) $base->close;

            $instrument->update([
                'current_price' => $price,
                'previous_price' => (float) $base->open,
                'last_valued_at' => $base->recorded_at,
            ]);

            PrivateInvestmentHolding::query()
                ->where('instrument_id', $instrument->id)
                ->where('status', 'active')
                ->get()
                ->each(function ($holding) use ($price) {
                    $currentValue = (float) $holding->units * $price;
                    $costBasis = (float) $holding->cost_basis;
                    $profitLoss = $currentValue - $costBasis;

                    $holding->update([
                        'current_value' => $currentValue,
                        'unrealized_profit_loss' => $profitLoss,
                        'unrealized_return_percent' => $costBasis > 0 ? ($profitLoss / $costBasis) * 100 : 0,
                    ]);
                });
        });
    }

    public function apply(
        PrivateInvestmentInstrument $instrument,
        array $payload,
        ?int $createdByUserId = null
    ): PrivateInvestmentEvent {
        return DB::transaction(function () use ($instrument, $payload, $createdByUserId) {
            $instrument = PrivateInvestmentInstrument::query()->lockForUpdate()->findOrFail($instrument->id);

            $previous = (float) $instrument->current_price;
            $value = (float) $payload['adjustment_value'];
            $type = $payload['adjustment_type'];
            $direction = $payload['direction'];

            $signed = $direction === 'negative' ? -abs($value) : abs($value);

            $newPrice = match ($type) {
                'percentage' => $previous * (1 + ($signed / 100)),
                'fixed' => $previous + $signed,
                'set' => $value,
                default => throw ValidationException::withMessages(['adjustment_type' => 'Unsupported adjustment type.']),
            };

            if ($newPrice <= 0) {
                throw ValidationException::withMessages(['adjustment_value' => 'The resulting investment price must remain above zero.']);
            }

            $effectiveAt = $payload['effective_at'] ?? now();

            $event = PrivateInvestmentEvent::query()->create([
                'instrument_id' => $instrument->id,
                'asset_id' => $payload['asset_id'] ?? null,
                'event_type' => $payload['event_type'],
                'direction' => $direction,
                'adjustment_type' => $type,
                'adjustment_value' => $value,
                'previous_price' => $previous,
                'new_price' => $newPrice,
                'reason' => $payload['reason'],
                'approval_state' => 'approved',
                'created_by_user_id' => $createdByUserId,
                'metadata' => ['source' => 'admin_control_plane'],
                'effective_at' => $effectiveAt,
            ]);

            $recordedAt = now();
            while (PrivateInvestmentPrice::query()
                ->where('instrument_id', $instrument->id)
                ->where('timeframe', 'event')
                ->where('recorded_at', $recordedAt)
                ->exists()) {
                $recordedAt = $recordedAt->copy()->addSecond();
            }

            $change = $newPrice - $previous;
            $percent = $previous > 0 ? ($change / $previous) * 100 : 0;

            PrivateInvestmentPrice::query()->create([
                'instrument_id' => $instrument->id,
                'event_id' => $event->id,
                'timeframe' => 'event',
                'open' => $previous,
                'high' => max($previous, $newPrice),
                'low' => min($previous, $newPrice),
                'close' => $newPrice,
                'change_amount' => $change,
                'change_percent' => $percent,
                'source' => 'admin_valuation_event',
                'recorded_at' => $recordedAt,
            ]);

            $instrument->update([
                'previous_price' => $previous,
                'current_price' => $newPrice,
                'last_valued_at' => $recordedAt,
            ]);

            PrivateInvestmentHolding::query()
                ->where('instrument_id', $instrument->id)
                ->where('status', 'active')
                ->get()
                ->each(function ($holding) use ($newPrice) {
                    $currentValue = (float) $holding->units * $newPrice;
                    $costBasis = (float) $holding->cost_basis;
                    $profitLoss = $currentValue - $costBasis;

                    $holding->update([
                        'current_value' => $currentValue,
                        'unrealized_profit_loss' => $profitLoss,
                        'unrealized_return_percent' => $costBasis > 0 ? ($profitLoss / $costBasis) * 100 : 0,
                    ]);
                });

            return $event;
        });
    }
}
