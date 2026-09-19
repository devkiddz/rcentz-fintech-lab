<?php

namespace App\Services;

use App\Events\StockPriceUpdated;
use App\Models\ControlledMarketInstrument;
use App\Models\ControlledMarketTick;
use App\Models\MarketEnvironment;
use App\Models\MarketInstrument;
use App\Models\Stock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ControlledMarketEngine
{
    public function __construct(
        private PortfolioValuationService $valuation
    ) {}

    public function registerInstrument(Stock $stock, string $label, float $startingPrice): ControlledMarketInstrument
    {
        $parentId = (int) ($stock->market_instrument_id ?? 0);
        $parent = $parentId > 0
            ? MarketInstrument::query()->find($parentId)
            : MarketInstrument::query()->where('stock_id', $stock->id)->first();

        if (! $parent) {
            throw new RuntimeException('MarketInstrument parent is unavailable for '.$stock->symbol.'.');
        }

        return $this->registerMarketInstrument($parent, $label, $startingPrice);
    }

    public function registerMarketInstrument(
        MarketInstrument $parent,
        string $label,
        float $startingPrice
    ): ControlledMarketInstrument {
        if ($startingPrice <= 0) {
            throw new RuntimeException('Starting market price must be greater than zero.');
        }

        $defaults = $this->defaultsForMarketInstrument($parent, $startingPrice);

        return DB::transaction(function () use ($parent, $label, $startingPrice, $defaults) {
            $instrument = ControlledMarketInstrument::query()->updateOrCreate(
                ['market_instrument_id' => $parent->id],
                [
                    'stock_id' => $parent->stock_id,
                    'symbol' => strtoupper($parent->symbol),
                    'label' => $label,
                    'asset_class' => $parent->asset_class,
                    'current_price' => round($startingPrice, $defaults['decimal_precision']),
                    'previous_price' => round($startingPrice, $defaults['decimal_precision']),
                    'opening_price' => round($startingPrice, $defaults['decimal_precision']),
                    'high' => round($startingPrice, $defaults['decimal_precision']),
                    'low' => round($startingPrice, $defaults['decimal_precision']),
                    'decimal_precision' => $defaults['decimal_precision'],
                    'minimum_tick' => $defaults['minimum_tick'],
                    'minimum_price' => $defaults['minimum_price'],
                    'volatility_percent' => $defaults['volatility_percent'],
                    'individual_bias' => 0,
                    'is_active' => true,
                    'last_moved_at' => now(),
                ]
            );

            if (! $instrument->ticks()->exists()) {
                ControlledMarketTick::create([
                    'controlled_market_instrument_id' => $instrument->id,
                    'drive_mode' => 'seed',
                    'open' => $instrument->current_price,
                    'high' => $instrument->current_price,
                    'low' => $instrument->current_price,
                    'close' => $instrument->current_price,
                    'change_amount' => 0,
                    'change_percent' => 0,
                    'ticked_at' => now(),
                ]);
            }

            return $instrument->refresh();
        });
    }

    public function resetPrice(ControlledMarketInstrument $instrument, float $price): ControlledMarketInstrument
    {
        if ($price <= 0) {
            throw new RuntimeException('Controlled market price must be greater than zero.');
        }

        $instrument->loadMissing('marketInstrument');
        $defaults = $instrument->marketInstrument
            ? $this->defaultsForMarketInstrument($instrument->marketInstrument, $price)
            : $this->defaultsForPrice($price);

        $old = (float) $instrument->current_price;
        $precision = $defaults['decimal_precision'];
        $price = round($price, $precision);

        $instrument->update([
            'previous_price' => $old ?: $price,
            'current_price' => $price,
            'opening_price' => $price,
            'high' => $price,
            'low' => $price,
            'decimal_precision' => $precision,
            'minimum_tick' => $defaults['minimum_tick'],
            'minimum_price' => $defaults['minimum_price'],
            'volatility_percent' => $defaults['volatility_percent'],
            'last_moved_at' => now(),
        ]);

        ControlledMarketTick::create([
            'controlled_market_instrument_id' => $instrument->id,
            'drive_mode' => 'reset',
            'open' => $old ?: $price,
            'high' => max($old ?: $price, $price),
            'low' => min($old ?: $price, $price),
            'close' => $price,
            'change_amount' => $price - ($old ?: $price),
            'change_percent' => $old > 0 ? (($price - $old) / $old) * 100 : 0,
            'ticked_at' => now(),
        ]);

        $this->syncStockCompatibility($instrument);

        return $instrument->refresh();
    }

    public function tickIfDue(): array
    {
        $environment = MarketEnvironment::current();
        $interval = max(5, min(300, (int) ($environment->controlled_tick_seconds ?: 60)));

        $lock = Cache::lock('rcentz-controlled-market-auto-tick', max(10, $interval));

        if (! $lock->get()) {
            return [
                'ticked' => false,
                'mode' => $environment->controlled_drive_mode ?: 'range',
                'updated' => 0,
                'failed' => 0,
                'interval_seconds' => $interval,
                'reason' => 'tick_locked',
            ];
        }

        try {
            $latest = ControlledMarketInstrument::query()
                ->where('is_active', true)
                ->max('last_moved_at');

            if ($latest && \Carbon\Carbon::parse($latest)->gt(now()->subSeconds($interval))) {
                return [
                    'ticked' => false,
                    'mode' => $environment->controlled_drive_mode ?: 'range',
                    'updated' => 0,
                    'failed' => 0,
                    'interval_seconds' => $interval,
                    'reason' => 'not_due',
                ];
            }

            $result = $this->tickAll();

            return array_merge($result, [
                'ticked' => true,
                'interval_seconds' => $interval,
                'reason' => 'due',
            ]);
        } finally {
            $lock->release();
        }
    }

    public function tickAll(?string $mode = null): array
    {
        $environment = MarketEnvironment::current();
        $mode = $mode ?: $environment->controlled_drive_mode ?: 'range';

        if (! in_array($mode, ['up', 'down', 'range'], true)) {
            throw new RuntimeException('Controlled market drive must be up, down or range.');
        }

        $strength = max(0.10, min(3.00, (float) $environment->controlled_drive_strength));
        $result = ['mode' => $mode, 'updated' => 0, 'failed' => 0];

        ControlledMarketInstrument::query()
            ->where('is_active', true)
            ->with(['marketInstrument.stock'])
            ->orderBy('id')
            ->chunkById(100, function ($instruments) use ($mode, $strength, &$result) {
                foreach ($instruments as $instrument) {
                    try {
                        $this->tick($instrument, $mode, $strength);
                        $result['updated']++;
                    } catch (\Throwable $e) {
                        \Log::warning('Controlled market tick failed', [
                            'instrument_id' => $instrument->id,
                            'market_instrument_id' => $instrument->market_instrument_id,
                            'symbol' => $instrument->symbol,
                            'error' => $e->getMessage(),
                        ]);
                        $result['failed']++;
                    }
                }
            });

        return $result;
    }

    public function tick(ControlledMarketInstrument $instrument, string $mode, float $strength = 1): ControlledMarketInstrument
    {
        return DB::transaction(function () use ($instrument, $mode, $strength) {
            $instrument = ControlledMarketInstrument::query()
                ->with(['marketInstrument.stock'])
                ->lockForUpdate()
                ->findOrFail($instrument->id);

            $open = (float) $instrument->current_price;
            if ($open <= 0) {
                throw new RuntimeException('Controlled instrument has no valid market price.');
            }

            $volatility = max(0.0001, (float) $instrument->volatility_percent) * max(0.10, min(3.00, $strength));
            $bias = max(-1, min(1, (float) $instrument->individual_bias));
            $noise = random_int(0, 1000000) / 1000000;
            $magnitude = $volatility * (0.25 + ($noise * 0.75));

            if ($mode === 'range') {
                $direction = random_int(0, 1) === 1 ? 1 : -1;
                $anchor = (float) $instrument->opening_price ?: $open;
                $distance = $anchor > 0 ? (($open - $anchor) / $anchor) * 100 : 0;
                if ($distance > $volatility * 2) $direction = -1;
                if ($distance < -$volatility * 2) $direction = 1;
                $magnitude *= 0.45;
            } else {
                $upProbability = $mode === 'up' ? 0.68 : 0.32;
                $upProbability += $bias * 0.15;
                $upProbability = max(0.08, min(0.92, $upProbability));
                $direction = (random_int(0, 10000) / 10000) <= $upProbability ? 1 : -1;
            }

            $movePercent = $magnitude * $direction;
            $rawClose = $open * (1 + ($movePercent / 100));
            $minimum = max((float) $instrument->minimum_price, (float) $instrument->minimum_tick);
            $precision = max(0, min(8, (int) $instrument->decimal_precision));
            $close = round(max($minimum, $rawClose), $precision);

            if ($close === $open) {
                $tick = (float) $instrument->minimum_tick;
                $close = round(max($minimum, $open + ($direction * $tick)), $precision);
            }

            $changeAmount = $close - $open;
            $changePercent = $open > 0 ? ($changeAmount / $open) * 100 : 0;
            $wick = abs($changeAmount) * (random_int(5, 25) / 100);
            $high = max($open, $close) + $wick;
            $low = max($minimum, min($open, $close) - $wick);

            $instrument->update([
                'previous_price' => $open,
                'current_price' => $close,
                'high' => max((float) $instrument->high, $high),
                'low' => min((float) $instrument->low ?: $low, $low),
                'last_moved_at' => now(),
            ]);

            ControlledMarketTick::create([
                'controlled_market_instrument_id' => $instrument->id,
                'drive_mode' => $mode,
                'open' => $open,
                'high' => round($high, $precision),
                'low' => round($low, $precision),
                'close' => $close,
                'change_amount' => $changeAmount,
                'change_percent' => $changePercent,
                'ticked_at' => now(),
            ]);

            $this->syncStockCompatibility($instrument, $close, $open, $changeAmount, $changePercent, $high, $low);

            return $instrument->refresh();
        });
    }

    private function syncStockCompatibility(
        ControlledMarketInstrument $instrument,
        ?float $close = null,
        ?float $open = null,
        ?float $changeAmount = null,
        ?float $changePercent = null,
        ?float $high = null,
        ?float $low = null
    ): void {
        $instrument->loadMissing(['marketInstrument.stock']);
        $stock = $instrument->marketInstrument?->stock ?: $instrument->stock;

        if (! $stock) {
            return;
        }

        $this->valuation->syncStock($stock, 'controlled');

        if ($close === null || MarketEnvironment::current()->active_marketplace !== 'controlled') {
            return;
        }

        try {
            broadcast(new StockPriceUpdated($stock, [
                'current_price' => $close,
                'previous_close' => $open,
                'change' => $changeAmount,
                'change_percent' => $changePercent,
                'volume' => 0,
                'high' => round((float) $high, $instrument->decimal_precision),
                'low' => round((float) $low, $instrument->decimal_precision),
                'open' => $open,
            ]));
        } catch (\Throwable $e) {
            \Log::debug('Controlled market stock broadcast skipped', ['error' => $e->getMessage()]);
        }
    }

    public function defaultsForMarketInstrument(MarketInstrument $instrument, float $price): array
    {
        $defaults = $this->defaultsForPrice($price);
        $precision = max(0, min(8, (int) ($instrument->price_precision ?? $defaults['decimal_precision'])));
        $tick = (float) ($instrument->pip_size ?? 0);

        if ($tick <= 0) {
            $tick = $defaults['minimum_tick'];
        }

        return [
            'decimal_precision' => $precision,
            'minimum_tick' => $tick,
            'minimum_price' => $tick,
            'volatility_percent' => $defaults['volatility_percent'],
        ];
    }

    public function defaultsForPrice(float $price): array
    {
        if ($price >= 1) {
            $precision = 2;
            $tick = 0.01;
        } elseif ($price >= 0.01) {
            $precision = 4;
            $tick = 0.0001;
        } else {
            $precision = 6;
            $tick = 0.000001;
        }

        $volatility = match (true) {
            $price >= 1000 => 0.08,
            $price >= 100 => 0.12,
            $price >= 10 => 0.18,
            $price >= 1 => 0.25,
            default => 0.40,
        };

        return [
            'decimal_precision' => $precision,
            'minimum_tick' => $tick,
            'minimum_price' => $tick,
            'volatility_percent' => $volatility,
        ];
    }
}
