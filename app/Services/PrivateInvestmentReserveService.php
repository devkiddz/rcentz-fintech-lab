<?php

namespace App\Services;

use App\Models\PrivateInvestmentAsset;
use App\Models\PrivateInvestmentHolding;
use App\Models\PrivateInvestmentInstrument;
use App\Models\MarketInstrument;
use App\Models\PrivateInvestmentPrice;
use App\Models\PrivateInvestmentReserveEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class PrivateInvestmentReserveService
{
    public function __construct(
        private readonly PrivateInvestmentReserveMarketService $markets
    ) {}

    public function summary(
        PrivateInvestmentInstrument $instrument
    ): array {
        $assets = $instrument->assets()
            ->where('status', 'active')
            ->where('is_reserve_backing', true)
            ->get();

        $reserveValue = (float) $assets->sum(
            fn ($asset) => (float) $asset->current_valuation
        );

        $customerUnits = (float) $instrument->holdings()
            ->where('status', 'active')
            ->sum('units');

        $price = (float) $instrument->current_price;
        $unitSupply = (float) $instrument->unit_supply;
        $availableUnits = (float) $instrument->available_units;

        $listedValue = $unitSupply * $price;
        $customerLiability = $customerUnits * $price;

        $listedCoverage = $listedValue > 0
            ? ($reserveValue / $listedValue) * 100
            : 0;

        $customerCoverage = $customerLiability > 0
            ? ($reserveValue / $customerLiability) * 100
            : ($reserveValue > 0 ? 100 : 0);

        $capacity = $price > 0
            ? $reserveValue / $price
            : 0;

        $remainingBackedCapacity = max(
            0,
            $capacity - $customerUnits
        );

        $sellableUnits = max(
            0,
            min($availableUnits, $remainingBackedCapacity)
        );

        $customerFullyBacked =
            $reserveValue > 0
            && $reserveValue + 0.01 >= $customerLiability;

        $listedFullyBacked =
            $reserveValue > 0
            && $listedValue > 0
            && $reserveValue + 0.01 >= $listedValue;

        return [
            'reserve_assets' => $assets->count(),
            'market_linked_assets' => $assets
                ->where(
                    'valuation_mode',
                    PrivateInvestmentAsset::VALUATION_MARKET_LINKED
                )
                ->count(),

            'reserve_value' => $reserveValue,
            'listed_value' => $listedValue,

            'customer_units' => $customerUnits,
            'customer_liability' => $customerLiability,

            'backed_unit_capacity' => $capacity,
            'remaining_backed_capacity' => $remainingBackedCapacity,
            'sellable_units' => $sellableUnits,

            'listed_coverage_percent' => $listedCoverage,
            'customer_coverage_percent' => $customerCoverage,

            'customer_fully_backed' => $customerFullyBacked,
            'listed_fully_backed' => $listedFullyBacked,

            // Backward-compatible alias: reserve safety is defined by
            // actual customer exposure, not arbitrary unsold catalogue supply.
            'fully_backed' => $customerFullyBacked,
        ];
    }

    public function baselineSupplyFor(
        float $reserveValue,
        float $currentPrice
    ): float {
        if ($reserveValue <= 0 || $currentPrice <= 0) {
            return 0;
        }

        return $this->floorUnits(
            $reserveValue / $currentPrice
        );
    }

    public function normalizeInstrumentSupply(
        PrivateInvestmentInstrument $instrument,
        string $reason = 'Normalize catalogue supply to reserve-backed capacity.'
    ): PrivateInvestmentInstrument {
        return DB::transaction(function () use (
            $instrument,
            $reason
        ) {
            $instrument = PrivateInvestmentInstrument::query()
                ->lockForUpdate()
                ->findOrFail($instrument->id);

            $reserveValue = (float) PrivateInvestmentAsset::query()
                ->where('instrument_id', $instrument->id)
                ->where('status', 'active')
                ->where('is_reserve_backing', true)
                ->sum('current_valuation');

            $customerUnits = (float) PrivateInvestmentHolding::query()
                ->where('instrument_id', $instrument->id)
                ->where('status', 'active')
                ->sum('units');

            $price = (float) $instrument->current_price;

            if ($price <= 0) {
                throw new RuntimeException(
                    'Reserve-backed investments require a positive current price before supply normalization.'
                );
            }

            if ($reserveValue <= 0) {
                throw new RuntimeException(
                    'Reserve-backed investments require a positive declared reserve before supply normalization.'
                );
            }

            $backedSupply = $this->baselineSupplyFor(
                $reserveValue,
                $price
            );

            if ($backedSupply + 0.000001 < $customerUnits) {
                throw new RuntimeException(
                    'Declared reserves do not cover the existing customer units at the current price.'
                );
            }

            $previousSupply = (float) $instrument->unit_supply;
            $previousAvailable = (float) $instrument->available_units;

            $availableUnits = $this->floorUnits(
                max(0, $backedSupply - $customerUnits)
            );

            $instrument->update([
                'unit_supply' => $backedSupply,
                'available_units' => $availableUnits,
                'last_valued_at' => now(),
            ]);

            PrivateInvestmentReserveEvent::query()->create([
                'instrument_id' => $instrument->id,
                'asset_id' => null,
                'action' => 'baseline_supply_normalized',
                'valuation_mode' => null,
                'previous_quantity' => $previousSupply,
                'new_quantity' => $backedSupply,
                'previous_unit_price' => $price,
                'new_unit_price' => $price,
                'previous_valuation' => round(
                    $previousSupply * $price,
                    2
                ),
                'new_valuation' => round(
                    $backedSupply * $price,
                    2
                ),
                'market_price' => null,
                'reason' => $reason,
                'created_by_user_id' => null,
                'metadata' => [
                    'source' => 'reserve_authority_backfill',
                    'reserve_value' => round($reserveValue, 2),
                    'customer_units' => $customerUnits,
                    'previous_available_units' => $previousAvailable,
                    'new_available_units' => $availableUnits,
                    'price_preserved' => true,
                ],
                'effective_at' => now(),
            ]);

            return $instrument->fresh();
        });
    }

    public function quarantineInstrumentSupply(
        PrivateInvestmentInstrument $instrument,
        string $reason = 'Reserve configuration is incomplete; pause new subscriptions and quarantine unbacked catalogue availability.'
    ): PrivateInvestmentInstrument {
        return DB::transaction(function () use (
            $instrument,
            $reason
        ) {
            $instrument = PrivateInvestmentInstrument::query()
                ->lockForUpdate()
                ->findOrFail($instrument->id);

            $customerUnits = (float) PrivateInvestmentHolding::query()
                ->where('instrument_id', $instrument->id)
                ->where('status', 'active')
                ->sum('units');

            $previousSupply = (float) $instrument->unit_supply;
            $previousAvailable = (float) $instrument->available_units;
            $previousStatus = (string) $instrument->status;
            $price = (float) $instrument->current_price;

            // A blocked product may preserve existing customer ownership,
            // but it must advertise no unbacked catalogue capacity.
            $quarantinedSupply = $this->floorUnits(
                max(0, $customerUnits)
            );

            $instrument->update([
                'unit_supply' => $quarantinedSupply,
                'available_units' => 0,
                'status' => 'paused',
                'last_valued_at' => now(),
            ]);

            PrivateInvestmentReserveEvent::query()->create([
                'instrument_id' => $instrument->id,
                'asset_id' => null,
                'action' => 'baseline_reserve_blocked',
                'valuation_mode' => null,
                'previous_quantity' => $previousSupply,
                'new_quantity' => $quarantinedSupply,
                'previous_unit_price' => $price,
                'new_unit_price' => $price,
                'previous_valuation' => round(
                    $previousSupply * $price,
                    2
                ),
                'new_valuation' => round(
                    $quarantinedSupply * $price,
                    2
                ),
                'market_price' => null,
                'reason' => $reason,
                'created_by_user_id' => null,
                'metadata' => [
                    'source' => 'reserve_authority_backfill',
                    'customer_units_preserved' => $customerUnits,
                    'previous_available_units' => $previousAvailable,
                    'new_available_units' => 0,
                    'previous_status' => $previousStatus,
                    'new_status' => 'paused',
                    'unbacked_catalogue_capacity_removed' => true,
                ],
                'effective_at' => now(),
            ]);

            return $instrument->fresh();
        });
    }

    public function bootstrapAsset(
        PrivateInvestmentAsset $asset,
        array $values,
        string $reason = 'Reserve authority baseline backfill.'
    ): PrivateInvestmentAsset {
        return DB::transaction(function () use (
            $asset,
            $values,
            $reason
        ) {
            $asset = PrivateInvestmentAsset::query()
                ->lockForUpdate()
                ->findOrFail($asset->id);

            $previousQuantity =
                (float) ($asset->reserve_quantity ?? 0);

            $previousUnitPrice =
                (float) ($asset->current_unit_price ?? 0);

            $previousValuation =
                (float) $asset->current_valuation;

            $asset->update([
                'valuation_mode' => $values['valuation_mode'],
                'is_reserve_backing' => true,
                'reserve_quantity' => $values['reserve_quantity'],
                'reserve_unit' => $values['reserve_unit'],
                'acquisition_unit_price' =>
                    $values['acquisition_unit_price'],
                'current_unit_price' =>
                    $values['current_unit_price'],
                'acquisition_value' =>
                    $values['acquisition_value'],
                'current_valuation' =>
                    $values['current_valuation'],
                'last_valued_at' => now(),
            ]);

            PrivateInvestmentReserveEvent::query()->create([
                'instrument_id' => $asset->instrument_id,
                'asset_id' => $asset->id,
                'action' => 'baseline_backfill',
                'valuation_mode' => $values['valuation_mode'],
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $values['reserve_quantity'],
                'previous_unit_price' => $previousUnitPrice,
                'new_unit_price' => $values['current_unit_price'],
                'previous_valuation' => $previousValuation,
                'new_valuation' => $values['current_valuation'],
                'market_price' =>
                    $values['market_price'] ?? null,
                'reason' => $reason,
                'created_by_user_id' => null,
                'metadata' => [
                    'source' => 'reserve_authority_backfill',
                    'reserve_value_preserved' =>
                        abs(
                            $previousValuation
                            - (float) $values['current_valuation']
                        ) < 0.01,
                ],
                'effective_at' => now(),
            ]);

            return $asset->fresh();
        });
    }

    public function syncInstrument(
        PrivateInvestmentInstrument $instrument,
        bool $rebuildHistory = false
    ): PrivateInvestmentInstrument {
        $instrument = PrivateInvestmentInstrument::query()
            ->findOrFail($instrument->id);

        $assets = $instrument->assets()
            ->with('marketInstrument')
            ->where('status', 'active')
            ->where('is_reserve_backing', true)
            ->where(
                'valuation_mode',
                PrivateInvestmentAsset::VALUATION_MARKET_LINKED
            )
            ->whereNotNull('market_instrument_id')
            ->get();

        foreach ($assets as $asset) {
            $marketPrice = $this->markets->currentPrice(
                $asset->marketInstrument
            );

            $asset->update([
                'current_unit_price' => $marketPrice,
                'current_valuation' => round(
                    (float) $asset->reserve_quantity * $marketPrice,
                    2
                ),
                'last_valued_at' => now(),
            ]);
        }

        if ($rebuildHistory) {
            $this->rebuildSingleMarketLinkedHistory(
                $instrument
            );
        }

        return $this->revalueFromReserves($instrument);
    }

    public function revalueFromReserves(
        PrivateInvestmentInstrument $instrument
    ): PrivateInvestmentInstrument {
        return DB::transaction(function () use ($instrument) {
            $instrument = PrivateInvestmentInstrument::query()
                ->lockForUpdate()
                ->findOrFail($instrument->id);

            $unitSupply = (float) $instrument->unit_supply;

            if ($unitSupply <= 0) {
                throw new RuntimeException(
                    'Reserve-backed investments require a positive unit supply.'
                );
            }

            $reserveValue = (float) PrivateInvestmentAsset::query()
                ->where('instrument_id', $instrument->id)
                ->where('status', 'active')
                ->where('is_reserve_backing', true)
                ->sum('current_valuation');

            if ($reserveValue <= 0) {
                $instrument->update(['status' => 'paused']);

                return $instrument->fresh();
            }

            $previous = (float) $instrument->current_price;
            $nav = round($reserveValue / $unitSupply, 6);

            $change = $nav - $previous;

            $percent = $previous > 0
                ? ($change / $previous) * 100
                : 0;

            $recordedAt = now()->startOfDay();

            $price = PrivateInvestmentPrice::query()
                ->firstOrNew([
                    'instrument_id' => $instrument->id,
                    'timeframe' => '1d',
                    'recorded_at' => $recordedAt,
                ]);

            $open = $price->exists
                ? (float) $price->open
                : ($previous > 0 ? $previous : $nav);

            $price->fill([
                'event_id' => null,
                'open' => $open,
                'high' => max(
                    $open,
                    (float) ($price->high ?? 0),
                    $nav
                ),
                'low' => min(
                    $open,
                    (float) ($price->low ?: $open),
                    $nav
                ),
                'close' => $nav,
                'change_amount' => $change,
                'change_percent' => $percent,
                'source' => 'reserve_nav',
            ]);

            $price->save();

            $instrument->update([
                'previous_price' => $previous,
                'current_price' => $nav,
                'last_valued_at' => now(),
            ]);

            PrivateInvestmentHolding::query()
                ->where('instrument_id', $instrument->id)
                ->where('status', 'active')
                ->get()
                ->each(function ($holding) use ($nav) {
                    $currentValue = round(
                        (float) $holding->units * $nav,
                        2
                    );

                    $costBasis =
                        (float) $holding->cost_basis;

                    $profitLoss =
                        $currentValue - $costBasis;

                    $holding->update([
                        'current_value' => $currentValue,
                        'unrealized_profit_loss' => $profitLoss,
                        'unrealized_return_percent' =>
                            $costBasis > 0
                                ? ($profitLoss / $costBasis) * 100
                                : 0,
                    ]);
                });

            return $instrument->fresh();
        });
    }

    public function rebuildSingleMarketLinkedHistory(
        PrivateInvestmentInstrument $instrument
    ): int {
        // Compatibility alias retained for existing callers.
        return $this->rebuildSingleBaseAssetHistory($instrument);
    }

    public function rebuildSingleBaseAssetHistory(
        PrivateInvestmentInstrument $instrument
    ): int {
        $instrument = PrivateInvestmentInstrument::query()
            ->findOrFail($instrument->id);

        $assets = $instrument->assets()
            ->with(['marketInstrument', 'privateMarketReference'])
            ->where('status', 'active')
            ->where('is_reserve_backing', true)
            ->get();

        if ($assets->count() !== 1) {
            return 0;
        }

        $asset = $assets->first();
        $unitSupply = (float) $instrument->unit_supply;
        $quantity = (float) $asset->reserve_quantity;

        if ($unitSupply <= 0 || $quantity <= 0) {
            return 0;
        }

        $written = 0;
        $previousClose = null;

        if ($asset->isMarketLinked() && $asset->marketInstrument) {
            $history = $this->markets->dailyHistory(
                $asset->marketInstrument
            );

            foreach ($history as $row) {
                if (! $row['time'] || (float) $row['close'] <= 0) {
                    continue;
                }

                $open = ($quantity * (float) $row['open']) / $unitSupply;
                $high = ($quantity * (float) $row['high']) / $unitSupply;
                $low = ($quantity * (float) $row['low']) / $unitSupply;
                $close = ($quantity * (float) $row['close']) / $unitSupply;

                $change = $previousClose !== null
                    ? $close - $previousClose
                    : 0;

                $percent = $previousClose !== null && $previousClose > 0
                    ? ($change / $previousClose) * 100
                    : 0;

                $this->writeReserveDerivedHistoryPoint(
                    $instrument->id,
                    $row['time']->copy()->startOfDay(),
                    $open,
                    $high,
                    $low,
                    $close,
                    $change,
                    $percent,
                    'reserve_public_base_history'
                );

                $previousClose = $close;
                $written++;
            }
        } elseif ($asset->isPrivateLinked() && $asset->privateMarketReference) {
            $rows = $asset->privateMarketReference
                ->prices()
                ->orderBy('recorded_at')
                ->orderBy('id')
                ->get();

            $days = [];

            foreach ($rows as $row) {
                if (! $row->recorded_at || (float) $row->price <= 0) {
                    continue;
                }

                $key = $row->recorded_at->format('Y-m-d');
                $previousBase = (float) $row->previous_price > 0
                    ? (float) $row->previous_price
                    : (float) $row->price;
                $closeBase = (float) $row->price;

                if (! isset($days[$key])) {
                    $days[$key] = [
                        'time' => $row->recorded_at->copy()->startOfDay(),
                        'open' => $previousBase,
                        'high' => max($previousBase, $closeBase),
                        'low' => min($previousBase, $closeBase),
                        'close' => $closeBase,
                    ];
                } else {
                    $days[$key]['high'] = max(
                        $days[$key]['high'],
                        $previousBase,
                        $closeBase
                    );
                    $days[$key]['low'] = min(
                        $days[$key]['low'],
                        $previousBase,
                        $closeBase
                    );
                    $days[$key]['close'] = $closeBase;
                }
            }

            foreach ($days as $row) {
                $open = ($quantity * (float) $row['open']) / $unitSupply;
                $high = ($quantity * (float) $row['high']) / $unitSupply;
                $low = ($quantity * (float) $row['low']) / $unitSupply;
                $close = ($quantity * (float) $row['close']) / $unitSupply;

                $change = $previousClose !== null
                    ? $close - $previousClose
                    : 0;

                $percent = $previousClose !== null && $previousClose > 0
                    ? ($change / $previousClose) * 100
                    : 0;

                $this->writeReserveDerivedHistoryPoint(
                    $instrument->id,
                    $row['time'],
                    $open,
                    $high,
                    $low,
                    $close,
                    $change,
                    $percent,
                    'reserve_private_base_history'
                );

                $previousClose = $close;
                $written++;
            }
        } else {
            return 0;
        }

        $this->reconcileCurrentInvestmentHistoryPoint(
            $instrument->fresh()
        );

        return $written;
    }

    private function writeReserveDerivedHistoryPoint(
        int $instrumentId,
        $recordedAt,
        float $open,
        float $high,
        float $low,
        float $close,
        float $change,
        float $percent,
        string $source
    ): void {
        PrivateInvestmentPrice::query()->updateOrCreate(
            [
                'instrument_id' => $instrumentId,
                'timeframe' => '1d',
                'recorded_at' => $recordedAt,
            ],
            [
                'event_id' => null,
                'open' => round($open, 6),
                'high' => round($high, 6),
                'low' => round($low, 6),
                'close' => round($close, 6),
                'change_amount' => round($change, 6),
                'change_percent' => round($percent, 6),
                'source' => $source,
            ]
        );
    }

    private function reconcileCurrentInvestmentHistoryPoint(
        PrivateInvestmentInstrument $instrument
    ): void {
        $current = (float) $instrument->current_price;

        if ($current <= 0) {
            return;
        }

        $recordedAt = now()->startOfDay();

        $existing = PrivateInvestmentPrice::query()
            ->where('instrument_id', $instrument->id)
            ->where('timeframe', '1d')
            ->where('recorded_at', $recordedAt)
            ->first();

        $previous = (float) (
            PrivateInvestmentPrice::query()
                ->where('instrument_id', $instrument->id)
                ->where('timeframe', '1d')
                ->where('recorded_at', '<', $recordedAt)
                ->latest('recorded_at')
                ->value('close')
                ?? $instrument->previous_price
                ?? $current
        );

        if ($previous <= 0) {
            $previous = $current;
        }

        $open = $existing
            ? (float) $existing->open
            : $previous;

        $high = max(
            $open,
            (float) ($existing?->high ?? $open),
            $current
        );

        $existingLow = (float) ($existing?->low ?? $open);
        $low = min(
            $open,
            $existingLow > 0 ? $existingLow : $open,
            $current
        );

        $change = $current - $previous;
        $percent = $previous > 0
            ? ($change / $previous) * 100
            : 0;

        $this->writeReserveDerivedHistoryPoint(
            $instrument->id,
            $recordedAt,
            $open,
            $high,
            $low,
            $current,
            $change,
            $percent,
            'reserve_base_current'
        );
    }

    public function addReserveAsset(
        PrivateInvestmentInstrument $instrument,
        array $data,
        ?int $createdByUserId = null
    ): PrivateInvestmentAsset {
        return DB::transaction(function () use (
            $instrument,
            $data,
            $createdByUserId
        ) {
            $instrument = PrivateInvestmentInstrument::query()
                ->lockForUpdate()
                ->findOrFail($instrument->id);

            $values = $this->resolveReserveAssetValues($data);

            $asset = $instrument->assets()->create([
                'market_instrument_id' => $values['market_instrument_id'],
                'valuation_mode' => $values['valuation_mode'],
                'is_reserve_backing' => true,
                'reserve_quantity' => $values['reserve_quantity'],
                'reserve_unit' => $values['reserve_unit'],
                'acquisition_unit_price' => $values['acquisition_unit_price'],
                'current_unit_price' => $values['current_unit_price'],
                'last_valued_at' => now(),
                'asset_type' => $data['asset_type'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'acquisition_value' => $values['acquisition_value'],
                'current_valuation' => $values['current_valuation'],
                'ownership_percentage' => 0,
                'status' => 'active',
                'acquired_at' => now()->toDateString(),
                'effective_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            if (
                ! $instrument->reference_asset_id
                && ! $instrument->assets()
                    ->where('status', 'active')
                    ->where('is_reserve_backing', true)
                    ->where('id', '!=', $asset->id)
                    ->exists()
            ) {
                $instrument->update([
                    'reference_asset_id' => $asset->id,
                ]);
            }

            $this->refreshReserveWeightsLocked($instrument);

            $this->recordAdminReserveEvent(
                $instrument,
                $asset,
                'reserve_asset_added',
                0,
                (float) $asset->reserve_quantity,
                0,
                (float) $asset->current_unit_price,
                0,
                (float) $asset->current_valuation,
                $values['market_price'],
                'Admin added an active reserve-backing asset.',
                $createdByUserId,
                ['source' => 'admin_reserve_management']
            );

            $this->synchronizeAdminCapacityLocked(
                $instrument,
                'Synchronize supply after reserve asset addition.',
                $createdByUserId
            );

            // R4C R1.6: once reserve-backed capacity exists, derive eligible private
            // NAV history from the stored public-market history. No market data is fabricated.
            $this->rebuildSingleMarketLinkedHistory(
                $instrument->fresh()
            );
            return $asset->fresh(['marketInstrument']);
        });
    }

    public function updateReserveAsset(
        PrivateInvestmentInstrument $instrument,
        PrivateInvestmentAsset $asset,
        array $data,
        ?int $createdByUserId = null
    ): PrivateInvestmentAsset {
        return DB::transaction(function () use (
            $instrument,
            $asset,
            $data,
            $createdByUserId
        ) {
            $instrument = PrivateInvestmentInstrument::query()
                ->lockForUpdate()
                ->findOrFail($instrument->id);

            $asset = PrivateInvestmentAsset::query()
                ->where('instrument_id', $instrument->id)
                ->lockForUpdate()
                ->findOrFail($asset->id);

            if ($asset->status !== 'active') {
                throw ValidationException::withMessages([
                    'reserve_quantity' => 'Only active reserve assets may be edited.',
                ]);
            }

            $values = $this->resolveReserveAssetValues($data);

            $otherReserveValue = (float) PrivateInvestmentAsset::query()
                ->where('instrument_id', $instrument->id)
                ->where('status', 'active')
                ->where('is_reserve_backing', true)
                ->where('id', '!=', $asset->id)
                ->sum('current_valuation');

            $projectedReserveValue = $otherReserveValue
                + (float) $values['current_valuation'];

            $this->assertReserveReductionAllowed(
                $instrument,
                $projectedReserveValue
            );

            $previousQuantity = (float) $asset->reserve_quantity;
            $previousUnitPrice = (float) $asset->current_unit_price;
            $previousValuation = (float) $asset->current_valuation;

            $asset->update([
                'market_instrument_id' => $values['market_instrument_id'],
                'valuation_mode' => $values['valuation_mode'],
                'is_reserve_backing' => true,
                'reserve_quantity' => $values['reserve_quantity'],
                'reserve_unit' => $values['reserve_unit'],
                'acquisition_unit_price' => $values['acquisition_unit_price'],
                'current_unit_price' => $values['current_unit_price'],
                'last_valued_at' => now(),
                'asset_type' => $data['asset_type'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'acquisition_value' => $values['acquisition_value'],
                'current_valuation' => $values['current_valuation'],
                'effective_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->refreshReserveWeightsLocked($instrument);

            $this->recordAdminReserveEvent(
                $instrument,
                $asset,
                'reserve_asset_updated',
                $previousQuantity,
                (float) $asset->reserve_quantity,
                $previousUnitPrice,
                (float) $asset->current_unit_price,
                $previousValuation,
                (float) $asset->current_valuation,
                $values['market_price'],
                'Admin updated reserve authority for an active backing asset.',
                $createdByUserId,
                [
                    'source' => 'admin_reserve_management',
                    'projected_reserve_value' => round($projectedReserveValue, 2),
                ]
            );

            $this->synchronizeAdminCapacityLocked(
                $instrument,
                'Synchronize supply after reserve asset update.',
                $createdByUserId
            );

            // R4C R1.6: once reserve-backed capacity exists, derive eligible private
            // NAV history from the stored public-market history. No market data is fabricated.
            $this->rebuildSingleMarketLinkedHistory(
                $instrument->fresh()
            );
            return $asset->fresh(['marketInstrument']);
        });
    }

    public function removeReserveAsset(
        PrivateInvestmentInstrument $instrument,
        PrivateInvestmentAsset $asset,
        ?int $createdByUserId = null,
        string $reason = 'Admin removed an active reserve-backing asset.'
    ): PrivateInvestmentInstrument {
        return DB::transaction(function () use (
            $instrument,
            $asset,
            $createdByUserId,
            $reason
        ) {
            $instrument = PrivateInvestmentInstrument::query()
                ->lockForUpdate()
                ->findOrFail($instrument->id);

            $asset = PrivateInvestmentAsset::query()
                ->where('instrument_id', $instrument->id)
                ->lockForUpdate()
                ->findOrFail($asset->id);

            if ($asset->status !== 'active') {
                throw ValidationException::withMessages([
                    'reserve_quantity' => 'This reserve asset is already inactive.',
                ]);
            }

            $projectedReserveValue = (float) PrivateInvestmentAsset::query()
                ->where('instrument_id', $instrument->id)
                ->where('status', 'active')
                ->where('is_reserve_backing', true)
                ->where('id', '!=', $asset->id)
                ->sum('current_valuation');

            $this->assertReserveReductionAllowed(
                $instrument,
                $projectedReserveValue
            );

            $previousQuantity = (float) $asset->reserve_quantity;
            $previousUnitPrice = (float) $asset->current_unit_price;
            $previousValuation = (float) $asset->current_valuation;

            if ((int) $instrument->reference_asset_id === (int) $asset->id) {
                $instrument->update([
                    'reference_asset_id' => null,
                ]);
            }

            $asset->update([
                'status' => 'removed',
                'is_reserve_backing' => false,
                'effective_at' => now(),
            ]);

            $this->recordAdminReserveEvent(
                $instrument,
                $asset,
                'reserve_asset_removed',
                $previousQuantity,
                0,
                $previousUnitPrice,
                0,
                $previousValuation,
                0,
                null,
                $reason,
                $createdByUserId,
                [
                    'source' => 'admin_reserve_management',
                    'projected_reserve_value' => round($projectedReserveValue, 2),
                ]
            );

            $this->refreshReserveWeightsLocked($instrument);

            return $this->synchronizeAdminCapacityLocked(
                $instrument,
                'Synchronize supply after reserve asset removal.',
                $createdByUserId
            );
        });
    }

    public function activateInstrument(
        PrivateInvestmentInstrument $instrument,
        ?int $createdByUserId = null,
        string $reason = 'Admin activated reserve-backed investment.'
    ): PrivateInvestmentInstrument {
        return DB::transaction(function () use (
            $instrument,
            $createdByUserId,
            $reason
        ) {
            $instrument = PrivateInvestmentInstrument::query()
                ->lockForUpdate()
                ->findOrFail($instrument->id);

            $instrument = $this->synchronizeAdminCapacityLocked(
                $instrument,
                'Validate reserve capacity before activation.',
                $createdByUserId
            );

            $reserveValue = (float) PrivateInvestmentAsset::query()
                ->where('instrument_id', $instrument->id)
                ->where('status', 'active')
                ->where('is_reserve_backing', true)
                ->sum('current_valuation');

            if ($reserveValue <= 0 || (float) $instrument->unit_supply <= 0) {
                throw ValidationException::withMessages([
                    'status' => 'An investment cannot be activated until positive reserve backing establishes sellable capacity.',
                ]);
            }

            $previousStatus = (string) $instrument->status;
            $instrument->update(['status' => 'active']);

            $this->recordAdminReserveEvent(
                $instrument,
                null,
                'reserve_instrument_activated',
                (float) $instrument->unit_supply,
                (float) $instrument->unit_supply,
                (float) $instrument->current_price,
                (float) $instrument->current_price,
                round((float) $instrument->unit_supply * (float) $instrument->current_price, 2),
                round((float) $instrument->unit_supply * (float) $instrument->current_price, 2),
                null,
                $reason,
                $createdByUserId,
                [
                    'source' => 'admin_reserve_management',
                    'previous_status' => $previousStatus,
                    'new_status' => 'active',
                    'reserve_value' => round($reserveValue, 2),
                ]
            );

            return $instrument->fresh();
        });
    }

    public function pauseInstrument(
        PrivateInvestmentInstrument $instrument,
        ?int $createdByUserId = null,
        string $reason = 'Admin paused reserve-backed investment.'
    ): PrivateInvestmentInstrument {
        return DB::transaction(function () use (
            $instrument,
            $createdByUserId,
            $reason
        ) {
            $instrument = PrivateInvestmentInstrument::query()
                ->lockForUpdate()
                ->findOrFail($instrument->id);

            $previousStatus = (string) $instrument->status;
            $instrument->update(['status' => 'paused']);

            $this->recordAdminReserveEvent(
                $instrument,
                null,
                'reserve_instrument_paused',
                (float) $instrument->unit_supply,
                (float) $instrument->unit_supply,
                (float) $instrument->current_price,
                (float) $instrument->current_price,
                round((float) $instrument->unit_supply * (float) $instrument->current_price, 2),
                round((float) $instrument->unit_supply * (float) $instrument->current_price, 2),
                null,
                $reason,
                $createdByUserId,
                [
                    'source' => 'admin_reserve_management',
                    'previous_status' => $previousStatus,
                    'new_status' => 'paused',
                ]
            );

            return $instrument->fresh();
        });
    }

    private function synchronizeAdminCapacityLocked(
        PrivateInvestmentInstrument $instrument,
        string $reason,
        ?int $createdByUserId
    ): PrivateInvestmentInstrument {
        $instrument = PrivateInvestmentInstrument::query()
            ->lockForUpdate()
            ->findOrFail($instrument->id);

        $reserveValue = (float) PrivateInvestmentAsset::query()
            ->where('instrument_id', $instrument->id)
            ->where('status', 'active')
            ->where('is_reserve_backing', true)
            ->sum('current_valuation');

        $customerUnits = (float) PrivateInvestmentHolding::query()
            ->where('instrument_id', $instrument->id)
            ->where('status', 'active')
            ->sum('units');

        $price = (float) $instrument->current_price;

        if ($price <= 0) {
            throw ValidationException::withMessages([
                'opening_price' => 'Reserve-backed capacity requires a positive instrument price.',
            ]);
        }

        $previousSupply = (float) $instrument->unit_supply;
        $previousAvailable = (float) $instrument->available_units;
        $previousStatus = (string) $instrument->status;

        if ($reserveValue <= 0) {
            if ($customerUnits > 0.000001) {
                throw ValidationException::withMessages([
                    'reserve_quantity' => 'Reserve authority cannot be reduced to zero while customer units remain outstanding.',
                ]);
            }

            $newSupply = 0.0;
            $newAvailable = 0.0;
            $newStatus = 'paused';
        } else {
            $newSupply = $this->baselineSupplyFor($reserveValue, $price);

            if ($newSupply + 0.000001 < $customerUnits) {
                throw ValidationException::withMessages([
                    'reserve_quantity' => 'The proposed reserve configuration does not cover existing customer units at the current price.',
                ]);
            }

            $newAvailable = $this->floorUnits(
                max(0, $newSupply - $customerUnits)
            );
            $newStatus = $previousStatus;
        }

        $instrument->update([
            'unit_supply' => $newSupply,
            'available_units' => $newAvailable,
            'status' => $newStatus,
            'last_valued_at' => now(),
        ]);

        $this->recordAdminReserveEvent(
            $instrument,
            null,
            'reserve_capacity_synchronized',
            $previousSupply,
            $newSupply,
            $price,
            $price,
            round($previousSupply * $price, 2),
            round($newSupply * $price, 2),
            null,
            $reason,
            $createdByUserId,
            [
                'source' => 'admin_reserve_management',
                'reserve_value' => round($reserveValue, 2),
                'customer_units' => $customerUnits,
                'previous_available_units' => $previousAvailable,
                'new_available_units' => $newAvailable,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
            ]
        );

        return $instrument->fresh();
    }

    private function refreshReserveWeightsLocked(
        PrivateInvestmentInstrument $instrument
    ): void {
        $assets = PrivateInvestmentAsset::query()
            ->where('instrument_id', $instrument->id)
            ->where('status', 'active')
            ->where('is_reserve_backing', true)
            ->lockForUpdate()
            ->get();

        $total = (float) $assets->sum(
            fn ($asset) => (float) $asset->current_valuation
        );

        foreach ($assets as $asset) {
            $weight = $total > 0
                ? ((float) $asset->current_valuation / $total) * 100
                : 0;

            $asset->update([
                'ownership_percentage' => round($weight, 4),
            ]);
        }
    }

    private function resolveReserveAssetValues(array $data): array
    {
        $mode = (string) $data['valuation_mode'];
        $quantity = (float) $data['reserve_quantity'];
        $reserveUnit = trim((string) $data['reserve_unit']);

        if ($quantity <= 0 || $reserveUnit === '') {
            throw ValidationException::withMessages([
                'reserve_quantity' => 'Reserve quantity and unit must explicitly identify the backing held by the platform.',
            ]);
        }

        $market = null;
        $marketPrice = null;

        if ($mode === PrivateInvestmentAsset::VALUATION_MARKET_LINKED) {
            $market = MarketInstrument::query()
                ->active()
                ->find($data['market_instrument_id'] ?? null);

            if (! $market) {
                throw ValidationException::withMessages([
                    'market_instrument_id' => 'Select an active public MarketInstrument for market-linked reserve valuation.',
                ]);
            }

            try {
                $currentUnitPrice = $this->markets->currentPrice($market);
            } catch (RuntimeException $exception) {
                throw ValidationException::withMessages([
                    'market_instrument_id' => $exception->getMessage(),
                ]);
            }
        } else {
            $currentUnitPrice = (float) ($data['current_unit_price'] ?? 0);

            if ($currentUnitPrice <= 0) {
                throw ValidationException::withMessages([
                    'current_unit_price' => 'Manual reserve valuation requires a positive current unit price.',
                ]);
            }
        }

        $acquisitionUnitPrice = (float) ($data['acquisition_unit_price'] ?? 0);
        if ($acquisitionUnitPrice <= 0) {
            $acquisitionUnitPrice = $currentUnitPrice;
        }

        return [
            'market_instrument_id' => $market?->id,
            'valuation_mode' => $mode,
            'reserve_quantity' => $quantity,
            'reserve_unit' => $reserveUnit,
            'acquisition_unit_price' => $acquisitionUnitPrice,
            'current_unit_price' => $currentUnitPrice,
            'acquisition_value' => round($quantity * $acquisitionUnitPrice, 2),
            'current_valuation' => round($quantity * $currentUnitPrice, 2),
            'market_price' => $marketPrice ?? ($market ? $currentUnitPrice : null),
        ];
    }

    private function recordAdminReserveEvent(
        PrivateInvestmentInstrument $instrument,
        ?PrivateInvestmentAsset $asset,
        string $action,
        float $previousQuantity,
        float $newQuantity,
        float $previousUnitPrice,
        float $newUnitPrice,
        float $previousValuation,
        float $newValuation,
        ?float $marketPrice,
        string $reason,
        ?int $createdByUserId,
        array $metadata = []
    ): void {
        PrivateInvestmentReserveEvent::query()->create([
            'instrument_id' => $instrument->id,
            'asset_id' => $asset?->id,
            'action' => $action,
            'valuation_mode' => $asset?->valuation_mode,
            'previous_quantity' => $previousQuantity,
            'new_quantity' => $newQuantity,
            'previous_unit_price' => $previousUnitPrice,
            'new_unit_price' => $newUnitPrice,
            'previous_valuation' => $previousValuation,
            'new_valuation' => $newValuation,
            'market_price' => $marketPrice,
            'reason' => $reason,
            'created_by_user_id' => $createdByUserId,
            'metadata' => $metadata,
            'effective_at' => now(),
        ]);
    }

    public function assertSubscriptionCapacity(
        PrivateInvestmentInstrument $instrument,
        float $additionalUnits
    ): void {
        $summary = $this->summary($instrument);

        $projectedCustomerUnits =
            $summary['customer_units'] + $additionalUnits;

        if (
            $summary['reserve_value'] <= 0
            || $projectedCustomerUnits
                > $summary['backed_unit_capacity'] + 0.000001
        ) {
            throw ValidationException::withMessages([
                'amount' =>
                    'This investment does not currently have sufficient reserve backing for the requested subscription.',
            ]);
        }

        $unitSupply = (float) $instrument->unit_supply;

        if (
            $unitSupply > 0
            && $projectedCustomerUnits > $unitSupply + 0.000001
        ) {
            throw ValidationException::withMessages([
                'amount' =>
                    'This subscription exceeds the reserve-authorized unit supply.',
            ]);
        }
    }

    public function assertReserveReductionAllowed(
        PrivateInvestmentInstrument $instrument,
        float $projectedReserveValue
    ): void {
        $customerUnits = (float) $instrument->holdings()
            ->where('status', 'active')
            ->sum('units');

        $customerLiability =
            $customerUnits * (float) $instrument->current_price;

        if ($projectedReserveValue + 0.01 < $customerLiability) {
            throw ValidationException::withMessages([
                'reserve_quantity' =>
                    'Reserve reduction would leave existing customer holdings under-backed.',
            ]);
        }
    }

    private function floorUnits(float $units): float
    {
        return floor(
            max(0, $units) * 1_000_000
        ) / 1_000_000;
    }
}
