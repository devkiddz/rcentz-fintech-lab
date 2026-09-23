<?php

namespace App\Console\Commands;

use App\Models\PrivateInvestmentAsset;
use App\Models\PrivateInvestmentInstrument;
use App\Services\PrivateInvestmentReserveMarketService;
use App\Services\PrivateInvestmentReserveService;
use Illuminate\Console\Command;

class BackfillPrivateInvestmentReserves extends Command
{
    protected $signature = 'private-investments:backfill-reserves
        {--apply : Write the reviewed reserve baseline and normalize supply}
        {--sync-market-history : Rebuild eligible market-linked NAV history after applying}';

    protected $description =
        'Plan or apply reserve authority without inventing reserve value from catalogue supply.';

    public function handle(
        PrivateInvestmentReserveMarketService $markets,
        PrivateInvestmentReserveService $reserves
    ): int {
        $apply = (bool) $this->option('apply');
        $syncHistory =
            (bool) $this->option('sync-market-history');

        $assetRows = [];
        $supplyRows = [];
        $blocked = 0;
        $applied = 0;

        $instruments = PrivateInvestmentInstrument::query()
            ->with([
                'assets' => fn ($query) =>
                    $query->where('status', 'active'),
                'assets.marketInstrument',
                'holdings' => fn ($query) =>
                    $query->where('status', 'active'),
            ])
            ->orderBy('id')
            ->get();

        foreach ($instruments as $instrument) {
            $assets = $instrument->assets
                ->where('status', 'active')
                ->values();

            $currentPrice = (float) $instrument->current_price;
            $customerUnits = (float) $instrument->holdings
                ->sum(fn ($holding) => (float) $holding->units);

            if ($assets->isEmpty() || $currentPrice <= 0) {
                $blocked++;

                $supplyRows[] = [
                    $instrument->symbol,
                    number_format($currentPrice, 6, '.', ''),
                    '0.00',
                    number_format($customerUnits, 6, '.', ''),
                    number_format((float) $instrument->unit_supply, 6, '.', ''),
                    '0.000000',
                    '0.000000',
                    'BLOCKED_NO_RESERVE',
                ];

                if ($apply) {
                    $reserves->quarantineInstrumentSupply(
                        $instrument,
                        'Reserve baseline blocked because no valid reserve asset/current price authority is available.'
                    );
                }

                continue;
            }

            $plans = [];
            $totalReserve = 0.0;
            $instrumentBlocked = false;

            foreach ($assets as $asset) {
                $plan = null;
                $status = 'READY';

                if (
                    $asset->market_instrument_id
                    && $asset->marketInstrument
                ) {
                    $quantity = (float) $asset->reserve_quantity;
                    $reserveUnit = trim((string) $asset->reserve_unit);

                    // A quantity of "1" was introduced as a migration default.
                    // It must not be treated as proof that the platform owns
                    // one market unit. Market-linked reserves require explicit
                    // quantity metadata before they can become authority.
                    if ($quantity <= 0 || $reserveUnit === '') {
                        $status = 'BLOCKED_EXPLICIT_QUANTITY_REQUIRED';
                        $instrumentBlocked = true;
                        $blocked++;

                        $assetRows[] = [
                            $instrument->symbol,
                            $asset->name,
                            'MARKET',
                            $asset->marketInstrument->symbol,
                            $quantity > 0
                                ? number_format($quantity, 8, '.', '')
                                : '-',
                            number_format(
                                (float) $asset->current_valuation,
                                2,
                                '.',
                                ''
                            ),
                            $status,
                        ];

                        continue;
                    }

                    try {
                        $marketPrice = $markets->currentPrice(
                            $asset->marketInstrument
                        );
                    } catch (\Throwable $exception) {
                        $status = 'BLOCKED_MARKET_PRICE';
                        $instrumentBlocked = true;
                        $blocked++;

                        $assetRows[] = [
                            $instrument->symbol,
                            $asset->name,
                            'MARKET',
                            $asset->marketInstrument->symbol,
                            number_format($quantity, 8, '.', ''),
                            number_format(
                                (float) $asset->current_valuation,
                                2,
                                '.',
                                ''
                            ),
                            $status,
                        ];

                        continue;
                    }

                    $currentValuation = round(
                        $quantity * $marketPrice,
                        2
                    );

                    $acquisitionValue =
                        (float) $asset->acquisition_value;

                    $acquisitionUnitPrice =
                        (float) ($asset->acquisition_unit_price ?? 0);

                    if (
                        $acquisitionUnitPrice <= 0
                        && $acquisitionValue > 0
                    ) {
                        $acquisitionUnitPrice =
                            $acquisitionValue / $quantity;
                    }

                    if ($acquisitionUnitPrice <= 0) {
                        $acquisitionUnitPrice = $marketPrice;
                        $acquisitionValue = round(
                            $quantity * $acquisitionUnitPrice,
                            2
                        );
                    }

                    $plan = [
                        'valuation_mode' =>
                            PrivateInvestmentAsset::VALUATION_MARKET_LINKED,

                        'reserve_quantity' => $quantity,

                        'reserve_unit' => $reserveUnit,

                        'acquisition_unit_price' =>
                            $acquisitionUnitPrice,

                        'current_unit_price' => $marketPrice,

                        'acquisition_value' =>
                            $acquisitionValue,

                        'current_valuation' =>
                            $currentValuation,

                        'market_price' => $marketPrice,
                    ];

                    $assetRows[] = [
                        $instrument->symbol,
                        $asset->name,
                        'MARKET',
                        $asset->marketInstrument->symbol,
                        number_format($quantity, 8, '.', ''),
                        number_format(
                            $currentValuation,
                            2,
                            '.',
                            ''
                        ),
                        $status,
                    ];
                } else {
                    $quantity =
                        max(1, (float) $asset->reserve_quantity);

                    $currentValuation =
                        (float) $asset->current_valuation;

                    $acquisitionValue =
                        (float) $asset->acquisition_value;

                    if ($currentValuation <= 0) {
                        $status = 'BLOCKED_ZERO_DECLARED_RESERVE';
                        $instrumentBlocked = true;
                        $blocked++;

                        $assetRows[] = [
                            $instrument->symbol,
                            $asset->name,
                            'MANUAL',
                            '-',
                            number_format($quantity, 8, '.', ''),
                            number_format(
                                $currentValuation,
                                2,
                                '.',
                                ''
                            ),
                            $status,
                        ];

                        continue;
                    }

                    $plan = [
                        'valuation_mode' =>
                            PrivateInvestmentAsset::VALUATION_MANUAL,

                        'reserve_quantity' => $quantity,

                        'reserve_unit' =>
                            trim((string) $asset->reserve_unit) !== ''
                                ? (string) $asset->reserve_unit
                                : 'allocation',

                        'acquisition_unit_price' =>
                            $acquisitionValue > 0
                                ? $acquisitionValue / $quantity
                                : null,

                        'current_unit_price' =>
                            $currentValuation / $quantity,

                        // Preserve existing declared reserve authority.
                        'acquisition_value' =>
                            $acquisitionValue,

                        'current_valuation' =>
                            $currentValuation,

                        'market_price' => null,
                    ];

                    $assetRows[] = [
                        $instrument->symbol,
                        $asset->name,
                        'MANUAL',
                        '-',
                        number_format($quantity, 8, '.', ''),
                        number_format(
                            $currentValuation,
                            2,
                            '.',
                            ''
                        ),
                        $status,
                    ];
                }

                if ($plan !== null) {
                    $totalReserve +=
                        (float) $plan['current_valuation'];

                    $plans[] = [
                        'asset' => $asset,
                        'values' => $plan,
                    ];
                }
            }

            $backedSupply = $reserves->baselineSupplyFor(
                $totalReserve,
                $currentPrice
            );

            $newAvailable = max(
                0,
                $this->floorUnits(
                    $backedSupply - $customerUnits
                )
            );

            $supplyStatus = 'READY';

            if ($instrumentBlocked || $totalReserve <= 0) {
                $supplyStatus = 'BLOCKED_RESERVE_CONFIGURATION';
            } elseif (
                $backedSupply + 0.000001 < $customerUnits
            ) {
                $supplyStatus = 'BLOCKED_EXISTING_EXPOSURE';
                $instrumentBlocked = true;
                $blocked++;
            }

            $supplyRows[] = [
                $instrument->symbol,
                number_format($currentPrice, 6, '.', ''),
                number_format($totalReserve, 2, '.', ''),
                number_format($customerUnits, 6, '.', ''),
                number_format(
                    (float) $instrument->unit_supply,
                    6,
                    '.',
                    ''
                ),
                number_format($backedSupply, 6, '.', ''),
                number_format($newAvailable, 6, '.', ''),
                $supplyStatus,
            ];

            if (! $apply || $instrumentBlocked) {
                if ($apply && $instrumentBlocked) {
                    $reserves->quarantineInstrumentSupply(
                        $instrument,
                        'Reserve baseline blocked because reserve configuration is incomplete. Existing customer units are preserved while unbacked catalogue availability is quarantined.'
                    );
                }

                continue;
            }

            foreach ($plans as $plan) {
                $reserves->bootstrapAsset(
                    $plan['asset'],
                    $plan['values'],
                    'Preserve declared reserve value while establishing reserve authority baseline.'
                );
            }

            $instrument = $reserves->normalizeInstrumentSupply(
                $instrument,
                'Normalize unit supply to existing declared reserve capacity while preserving the current customer unit price.'
            );

            $hasMarketLinked = collect($plans)->contains(
                fn ($plan) =>
                    $plan['values']['valuation_mode']
                        === PrivateInvestmentAsset::VALUATION_MARKET_LINKED
            );

            if ($syncHistory && $hasMarketLinked) {
                $reserves->rebuildSingleMarketLinkedHistory(
                    $instrument
                );
            }

            $applied++;
        }

        $this->line('RESERVE ASSET BASELINE');
        $this->table(
            [
                'Investment',
                'Reserve asset',
                'Mode',
                'Market',
                'Quantity',
                'Reserve value',
                'Status',
            ],
            $assetRows
        );

        $this->newLine();
        $this->line('RESERVE-BACKED SUPPLY NORMALIZATION');
        $this->table(
            [
                'Investment',
                'Price',
                'Reserve value',
                'Customer units',
                'Current supply',
                'Backed supply',
                'New available',
                'Status',
            ],
            $supplyRows
        );

        $this->newLine();

        if (! $apply) {
            $this->warn(
                'DRY RUN ONLY - no reserve allocation or supply data was changed.'
            );

            $this->line(
                'Existing declared reserve values were preserved in this plan.'
            );

            $this->line(
                'Catalogue supply is proposed from reserve value / current price; reserves are never inflated to satisfy catalogue supply.'
            );

            if ($syncHistory) {
                $this->warn(
                    '--sync-market-history has no effect without --apply.'
                );
            }
        } else {
            $this->info(
                "PRIVATE_INVESTMENT_RESERVE_BASELINE_APPLIED={$applied}"
            );
        }

        if ($blocked > 0) {
            $this->warn(
                "RESERVE_BASELINE_BLOCKED={$blocked}"
            );
        }

        return self::SUCCESS;
    }

    private function floorUnits(float $units): float
    {
        return floor(
            max(0, $units) * 1_000_000
        ) / 1_000_000;
    }
}
