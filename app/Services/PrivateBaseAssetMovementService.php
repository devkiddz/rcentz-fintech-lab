<?php

namespace App\Services;

use App\Models\PrivateInvestmentAsset;
use App\Models\PrivateInvestmentInstrument;
use App\Models\PrivateInvestmentReserveEvent;
use App\Models\PrivateMarketReference;
use App\Models\PrivateMarketReferencePrice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class PrivateBaseAssetMovementService
{
    public function __construct(
        private readonly PrivateInvestmentReserveService $reserves
    ) {}

    public function settings(PrivateMarketReference $reference): array
    {
        return [
            'mode' => (string) ($reference->movement_mode ?: 'manual'),
            'behavior' => (string) ($reference->movement_behavior ?: 'smart'),
            'strength' => (float) ($reference->movement_strength ?: 1),
            'volatility_percent' => (float) (
                $reference->movement_volatility_percent ?: 0.05
            ),
            'tick_seconds' => (int) ($reference->movement_tick_seconds ?: 30),
            'anchor_price' => (float) (
                $reference->movement_anchor_price
                ?: $reference->current_price
            ),
            'last_moved_at' => optional(
                $reference->movement_last_moved_at
            )?->toIso8601String(),
        ];
    }

    public function updateSettings(
        PrivateMarketReference $reference,
        array $data
    ): PrivateMarketReference {
        $mode = strtolower((string) $data['movement_mode']);
        $behavior = strtolower((string) $data['movement_behavior']);

        if (! in_array($mode, ['manual', 'auto'], true)) {
            throw new RuntimeException('Movement mode must be manual or auto.');
        }

        if (! in_array($behavior, ['smart', 'up', 'down', 'range'], true)) {
            throw new RuntimeException(
                'Movement behavior must be smart, up, down or range.'
            );
        }

        $reference->forceFill([
            'movement_mode' => $mode,
            'movement_behavior' => $behavior,
            'movement_strength' => max(
                0.10,
                min(3.00, (float) $data['movement_strength'])
            ),
            'movement_volatility_percent' => max(
                0.001,
                min(
                    5.00,
                    (float) $data['movement_volatility_percent']
                )
            ),
            'movement_tick_seconds' => (int) $data['movement_tick_seconds'],
            'movement_anchor_price' => (float) $reference->current_price,
        ])->save();

        return $reference->refresh();
    }

    public function tickIfDue(
        PrivateMarketReference $reference
    ): array {
        $reference->refresh();

        if (
            $reference->status !== 'active'
            || $reference->movement_mode !== 'auto'
        ) {
            return [
                'moved' => false,
                'reason' => 'manual_or_inactive',
                'reference' => $reference,
            ];
        }

        $seconds = max(
            5,
            min(300, (int) $reference->movement_tick_seconds)
        );

        if (
            $reference->movement_last_moved_at
            && $reference->movement_last_moved_at->gt(
                now()->subSeconds($seconds)
            )
        ) {
            return [
                'moved' => false,
                'reason' => 'not_due',
                'reference' => $reference,
            ];
        }

        $lock = Cache::lock(
            'private-base-asset:movement:'.$reference->id,
            max(10, $seconds)
        );

        if (! $lock->get()) {
            return [
                'moved' => false,
                'reason' => 'locked',
                'reference' => $reference,
            ];
        }

        try {
            $reference->refresh();

            if (
                $reference->movement_last_moved_at
                && $reference->movement_last_moved_at->gt(
                    now()->subSeconds($seconds)
                )
            ) {
                return [
                    'moved' => false,
                    'reason' => 'not_due',
                    'reference' => $reference,
                ];
            }

            $reference = $this->move(
                $reference,
                (string) $reference->movement_behavior,
                null,
                false
            );

            return [
                'moved' => true,
                'reason' => 'due',
                'reference' => $reference,
            ];
        } finally {
            $lock->release();
        }
    }

    public function moveNow(
        PrivateMarketReference $reference,
        string $behavior,
        ?int $userId
    ): PrivateMarketReference {
        return $this->move(
            $reference,
            strtolower($behavior),
            $userId,
            true
        );
    }

    public function tickDueReferences(): array
    {
        $result = [
            'checked' => 0,
            'moved' => 0,
            'failed' => 0,
        ];

        PrivateMarketReference::query()
            ->where('status', 'active')
            ->where('movement_mode', 'auto')
            ->orderBy('id')
            ->chunkById(100, function ($references) use (&$result) {
                foreach ($references as $reference) {
                    $result['checked']++;

                    try {
                        $tick = $this->tickIfDue($reference);

                        if ($tick['moved']) {
                            $result['moved']++;
                        }
                    } catch (\Throwable $e) {
                        $result['failed']++;

                        \Log::warning(
                            'Private Base Asset movement failed',
                            [
                                'reference_id' => $reference->id,
                                'symbol' => $reference->symbol,
                                'error' => $e->getMessage(),
                            ]
                        );
                    }
                }
            });

        return $result;
    }

    private function move(
        PrivateMarketReference $reference,
        string $behavior,
        ?int $userId,
        bool $manualRequest
    ): PrivateMarketReference {
        if (! in_array($behavior, ['smart', 'up', 'down', 'range'], true)) {
            throw new RuntimeException(
                'Movement behavior must be smart, up, down or range.'
            );
        }

        if ($reference->status !== 'active') {
            throw new RuntimeException(
                'Paused Private Base Assets cannot move.'
            );
        }

        $current = (float) $reference->current_price;

        if ($current <= 0) {
            throw new RuntimeException(
                'Private Base Asset has no positive current price.'
            );
        }

        $strength = max(
            0.10,
            min(3.00, (float) $reference->movement_strength)
        );

        $volatility = max(
            0.001,
            min(
                5.00,
                (float) $reference->movement_volatility_percent
            )
        );

        $direction = $this->direction(
            $reference,
            $behavior,
            $volatility,
            $strength,
            $manualRequest
        );

        $noise = random_int(0, 1000000) / 1000000;
        $magnitude = $volatility
            * $strength
            * (0.35 + ($noise * 0.65));

        if ($behavior === 'range') {
            $magnitude *= 0.60;
        }

        $movePercent = $magnitude * $direction;
        $raw = $current * (1 + ($movePercent / 100));

        $precision = strtoupper($reference->currency) === 'USD'
            ? 2
            : 4;

        $next = round(max(0.00000001, $raw), $precision);

        if ($next === round($current, $precision)) {
            $tick = 1 / (10 ** $precision);
            $next = round(
                max(
                    $tick,
                    $current + ($direction * $tick)
                ),
                $precision
            );
        }

        $reason = $manualRequest
            ? 'Private Base Asset manual '.$behavior.' movement.'
            : 'Private Base Asset automatic '.$behavior.' movement.';

        return $this->recordValuation(
            $reference,
            $next,
            $reason,
            $userId
        );
    }

    private function direction(
        PrivateMarketReference $reference,
        string $behavior,
        float $volatility,
        float $strength,
        bool $manualRequest
    ): int {
        if ($manualRequest && $behavior === 'up') {
            return 1;
        }

        if ($manualRequest && $behavior === 'down') {
            return -1;
        }

        $current = (float) $reference->current_price;
        $anchor = (float) (
            $reference->movement_anchor_price
            ?: $current
        );

        $distance = $anchor > 0
            ? (($current - $anchor) / $anchor) * 100
            : 0;

        $band = max(
            $volatility * $strength * 6,
            $volatility * 2
        );

        if ($behavior === 'range') {
            if ($distance > $band) {
                return -1;
            }

            if ($distance < -$band) {
                return 1;
            }

            return random_int(0, 1) === 1 ? 1 : -1;
        }

        if ($behavior === 'up') {
            return (
                random_int(0, 10000) / 10000
            ) <= 0.74 ? 1 : -1;
        }

        if ($behavior === 'down') {
            return (
                random_int(0, 10000) / 10000
            ) <= 0.26 ? 1 : -1;
        }

        $recent = $reference->prices()
            ->latest('recorded_at')
            ->limit(8)
            ->get()
            ->sortBy('recorded_at')
            ->values();

        $first = (float) (
            $recent->first()?->price
            ?? $current
        );

        $momentum = $first > 0
            ? (($current - $first) / $first) * 100
            : 0;

        $upProbability = 0.50;

        if ($distance > $band) {
            $upProbability = 0.30;
        } elseif ($distance < -$band) {
            $upProbability = 0.70;
        } elseif ($momentum > $volatility * 2) {
            $upProbability = 0.60;
        } elseif ($momentum < -($volatility * 2)) {
            $upProbability = 0.40;
        }

        $micro = random_int(-500, 500) / 10000;
        $upProbability = max(
            0.18,
            min(0.82, $upProbability + $micro)
        );

        return (
            random_int(0, 10000) / 10000
        ) <= $upProbability ? 1 : -1;
    }

    private function recordValuation(
        PrivateMarketReference $reference,
        float $next,
        string $reason,
        ?int $userId
    ): PrivateMarketReference {
        return DB::transaction(function () use (
            $reference,
            $next,
            $reason,
            $userId
        ) {
            $reference = PrivateMarketReference::query()
                ->lockForUpdate()
                ->findOrFail($reference->id);

            $previous = (float) $reference->current_price;
            $change = $next - $previous;
            $percent = $previous > 0
                ? ($change / $previous) * 100
                : 0;
            $now = now();

            $reference->forceFill([
                'previous_price' => $previous,
                'current_price' => $next,
                'last_valued_at' => $now,
                'movement_last_moved_at' => $now,
            ])->save();

            PrivateMarketReferencePrice::query()->create([
                'reference_id' => $reference->id,
                'previous_price' => $previous,
                'price' => $next,
                'change_amount' => $change,
                'change_percent' => $percent,
                'reason' => $reason,
                'valued_by_user_id' => $userId,
                'recorded_at' => $now,
            ]);

            $assets = PrivateInvestmentAsset::query()
                ->where(
                    'private_market_reference_id',
                    $reference->id
                )
                ->where('status', 'active')
                ->where('is_reserve_backing', true)
                ->lockForUpdate()
                ->get();

            $instrumentIds = [];

            foreach ($assets as $asset) {
                $previousValuation = (float) $asset->current_valuation;
                $quantity = (float) $asset->reserve_quantity;
                $newValuation = round($quantity * $next, 2);

                $asset->update([
                    'valuation_mode' =>
                        PrivateInvestmentAsset::VALUATION_PRIVATE,
                    'public_investment_base_asset_id' => null,
                    'market_instrument_id' => null,
                    'current_unit_price' => $next,
                    'current_valuation' => $newValuation,
                    'last_valued_at' => $now,
                ]);

                PrivateInvestmentReserveEvent::query()->create([
                    'instrument_id' => $asset->instrument_id,
                    'asset_id' => $asset->id,
                    'action' => 'private_reference_revalued',
                    'valuation_mode' =>
                        PrivateInvestmentAsset::VALUATION_PRIVATE,
                    'previous_quantity' => $quantity,
                    'new_quantity' => $quantity,
                    'previous_unit_price' => $previous,
                    'new_unit_price' => $next,
                    'previous_valuation' => $previousValuation,
                    'new_valuation' => $newValuation,
                    'market_price' => null,
                    'reason' => $reason,
                    'created_by_user_id' => $userId,
                    'metadata' => [
                        'private_market_reference_id' =>
                            $reference->id,
                        'private_market_reference_symbol' =>
                            $reference->symbol,
                        'movement_behavior' =>
                            $reference->movement_behavior,
                        'movement_mode' =>
                            $reference->movement_mode,
                    ],
                    'effective_at' => $now,
                ]);

                $instrumentIds[$asset->instrument_id] = true;
            }

            foreach (array_keys($instrumentIds) as $instrumentId) {
                $this->reserves->revalueFromReserves(
                    PrivateInvestmentInstrument::query()
                        ->findOrFail($instrumentId)
                );
            }

            return $reference->refresh();
        });
    }
}
