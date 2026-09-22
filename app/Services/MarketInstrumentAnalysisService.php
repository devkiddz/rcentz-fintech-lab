<?php

namespace App\Services;

use App\Models\ControlledMarketInstrument;
use App\Models\MarketInstrument;
use Illuminate\Support\Collection;
use RuntimeException;

final class MarketInstrumentAnalysisService
{
    public function __construct(
        private MarketPriceRouter $prices,
        private StockAnalysisService $stocks,
        private ForexMarketDataService $forex,
        private CryptoMarketDataService $crypto,
        private CommodityMarketDataService $commodities
    ) {}

    public function forInstrument(MarketInstrument $instrument, ?string $marketplace = null): array
    {
        $marketplace = $this->prices->normalizeMarketplace($marketplace ?: $this->prices->activeMarketplace());
        $instrument->loadMissing([
            'stock',
            'forexPair',
            'canonicalStock',
            'canonicalForexPair',
            'canonicalCryptoPair',
            'canonicalCommodityInstrument',
            'controlledMarketInstrument',
        ]);

        if ($marketplace === 'controlled') {
            return $this->forControlled($instrument);
        }

        if ($instrument->isStock()) {
            $stock = $instrument->canonicalStock ?: $instrument->stock;
            if (! $stock) {
                throw new RuntimeException('Stock adapter is missing for '.$instrument->display_symbol.'.');
            }

            return $this->decorate(
                $instrument,
                'live',
                $this->stocks->forStockInMarketplace($stock, 'live')
            );
        }

        if ($instrument->isForex()) {
            $pair = $instrument->canonicalForexPair ?: $instrument->forexPair;
            if (! $pair) {
                throw new RuntimeException('Forex adapter is missing for '.$instrument->display_symbol.'.');
            }

            $analysis = $this->forex->context($pair);
            $analysis['source'] = $analysis['analysis_source'] ?? 'forex_market_history';
            $analysis['series'] = $analysis['timeframes']['1d'] ?? [];
            $analysis['previous_close'] = (float) ($pair->previous_close ?: ($analysis['current_price'] ?? 0));

            return $this->decorate($instrument, 'live', $analysis);
        }

        if ($instrument->isCrypto()) {
            $pair = $instrument->canonicalCryptoPair;
            if (! $pair) {
                throw new RuntimeException('Crypto adapter is missing for '.$instrument->display_symbol.'.');
            }

            $analysis = $this->crypto->context($pair);
            $analysis['source'] = $analysis['analysis_source'] ?? 'crypto_daily_history';
            $analysis['series'] = $analysis['timeframes']['1d'] ?? [];
            $analysis['previous_close'] = (float) ($pair->previous_close ?: ($analysis['current_price'] ?? 0));

            return $this->decorate($instrument, 'live', $analysis);
        }

        if ($instrument->isCommodity()) {
            $commodity = $instrument->canonicalCommodityInstrument;
            if (! $commodity) {
                throw new RuntimeException('Commodity adapter is missing for '.$instrument->display_symbol.'.');
            }

            $analysis = $this->commodities->context($commodity);
            $analysis['source'] = $analysis['analysis_source'] ?? 'commodity_market_history';
            $analysis['series'] = $analysis['series'] ?? ($analysis['timeframes']['1d'] ?? []);
            $analysis['previous_close'] = (float) ($commodity->previous_close ?: ($analysis['current_price'] ?? 0));

            return $this->decorate($instrument, 'live', $analysis);
        }

        throw new RuntimeException(
            'No analysis adapter exists for '.strtoupper($instrument->asset_class).' '.$instrument->display_symbol.'.'
        );
    }

    private function forControlled(MarketInstrument $instrument): array
    {
        $controlled = $instrument->controlledMarketInstrument
            ?: ControlledMarketInstrument::query()
                ->where('market_instrument_id', $instrument->id)
                ->first();

        if (! $controlled) {
            throw new RuntimeException('Controlled market history is unavailable for '.$instrument->display_symbol.'.');
        }

        $rows = $controlled->ticks()
            ->orderByDesc('ticked_at')
            ->limit(240)
            ->get()
            ->sortBy('ticked_at')
            ->values();

        $series = $rows->map(fn ($tick) => [
            'time' => optional($tick->ticked_at)?->toIso8601String(),
            'open' => (float) $tick->open,
            'high' => (float) $tick->high,
            'low' => (float) $tick->low,
            'close' => (float) $tick->close,
            'volume' => 0,
        ])->filter(fn ($row) => $row['time'] && $row['close'] > 0)->values()->all();

        if (! count($series)) {
            $current = (float) $controlled->current_price;
            if ($current > 0) {
                $series[] = [
                    'time' => optional($controlled->last_moved_at)?->toIso8601String() ?: now()->toIso8601String(),
                    'open' => $current,
                    'high' => $current,
                    'low' => $current,
                    'close' => $current,
                    'volume' => 0,
                ];
            }
        }

        $series = $this->withMovingAverages($series);
        $closes = collect($series)->pluck('close')->map(fn ($value) => (float) $value)->values();
        $last = (float) ($closes->last() ?? $controlled->current_price);
        $first = (float) ($closes->first() ?? $last);
        $momentum = $first > 0 ? (($last - $first) / $first) * 100 : 0;
        $recent = collect($series)->take(-min(30, count($series)));
        $support = (float) ($recent->min('low') ?? 0);
        $resistance = (float) ($recent->max('high') ?? 0);

        $raw = $this->normalizeSeries($series);
        $fifteen = $this->aggregateCandles($raw, 15);
        $hour = $this->aggregateCandles($raw, 60);
        $fourHour = $this->aggregateCandles($raw, 240);

        $analysis = [
            'source' => 'controlled_market_ticks',
            'series' => $series,
            'has_chart' => count($series) >= 2,
            'current_price' => $last,
            'previous_close' => (float) ($controlled->previous_price ?: $last),
            'support' => $support ?: null,
            'resistance' => $resistance ?: null,
            'sma20' => $this->lastSma($series, 'sma20'),
            'sma50' => $this->lastSma($series, 'sma50'),
            'sma200' => $this->lastSma($series, 'sma200'),
            'momentum_percent' => $momentum,
            'momentum_label' => $momentum > 0.10 ? 'Bullish' : ($momentum < -0.10 ? 'Bearish' : 'Neutral'),
            'trend' => $momentum > 0.10 ? 'Bullish' : ($momentum < -0.10 ? 'Bearish' : 'Neutral'),
            'risk_reward' => 'Controlled',
            'volume_current' => 0,
            'volume_average' => 0,
            'volume_vs_average' => null,
            'timeframes' => [
                '5m' => $raw,
                '15m' => $fifteen ?: $raw,
                '1h' => $hour ?: $raw,
                '4h' => $fourHour ?: $raw,
                '1d' => $raw,
                '1w' => $raw,
                '1m' => $raw,
                '3m' => $raw,
                '1y' => $raw,
            ],
            'default_timeframe' => '5m',
            'analyst' => [
                'label' => null,
                'percentage' => null,
                'strong_buy' => 0,
                'buy' => 0,
                'hold' => 0,
                'sell' => 0,
                'strong_sell' => 0,
                'total' => 0,
            ],
        ];

        return $this->decorate($instrument, 'controlled', $analysis);
    }

    private function decorate(MarketInstrument $instrument, string $marketplace, array $analysis): array
    {
        $analysis['market_instrument_id'] = $instrument->id;
        $analysis['asset_class'] = $instrument->asset_class;
        $analysis['symbol'] = $instrument->symbol;
        $analysis['display_symbol'] = $instrument->display_symbol;
        $analysis['label'] = $instrument->name;
        $analysis['marketplace'] = $marketplace;
        $analysis['price_precision'] = (int) $instrument->price_precision;
        $analysis['quote_asset'] = $instrument->quote_asset;
        $analysis['source'] = $analysis['source'] ?? $analysis['analysis_source'] ?? 'market_history';
        $analysis['series'] = $analysis['series'] ?? [];
        $analysis['timeframes'] = $analysis['timeframes'] ?? [];
        $analysis['current_price'] = (float) ($analysis['current_price'] ?? $this->prices->price($instrument, $marketplace));
        $analysis['previous_close'] = (float) ($analysis['previous_close'] ?? $analysis['current_price']);
        $analysis['has_chart'] = (bool) ($analysis['has_chart'] ?? count($analysis['series']) >= 2);

        return $analysis;
    }

    private function normalizeSeries(array $series): array
    {
        return collect($series)
            ->filter(fn ($row) => ! empty($row['time']) && (float) ($row['close'] ?? 0) > 0)
            ->sortBy('time')
            ->values()
            ->all();
    }

    private function aggregateCandles(array $series, int $minutes): array
    {
        if (count($series) < 2) {
            return [];
        }

        return collect($series)
            ->groupBy(function ($row) use ($minutes) {
                $timestamp = \Carbon\Carbon::parse($row['time']);
                $minuteOfDay = ($timestamp->hour * 60) + $timestamp->minute;
                $bucketMinute = intdiv($minuteOfDay, $minutes) * $minutes;

                return $timestamp->copy()->startOfDay()->addMinutes($bucketMinute)->toIso8601String();
            })
            ->map(function (Collection $rows, string $time) {
                $rows = $rows->sortBy('time')->values();

                return [
                    'time' => $time,
                    'open' => (float) $rows->first()['open'],
                    'high' => (float) $rows->max('high'),
                    'low' => (float) $rows->min('low'),
                    'close' => (float) $rows->last()['close'],
                    'volume' => (float) $rows->sum('volume'),
                ];
            })
            ->values()
            ->all();
    }

    private function withMovingAverages(array $series): array
    {
        $closes = [];

        foreach ($series as $index => $row) {
            $closes[] = (float) $row['close'];
            $series[$index]['sma20'] = $this->averageTail($closes, 20);
            $series[$index]['sma50'] = $this->averageTail($closes, 50);
            $series[$index]['sma200'] = $this->averageTail($closes, 200);
        }

        return $series;
    }

    private function averageTail(array $values, int $period): ?float
    {
        if (count($values) < $period) {
            return null;
        }

        $slice = array_slice($values, -$period);
        return array_sum($slice) / count($slice);
    }

    private function lastSma(array $series, string $key): ?float
    {
        foreach (array_reverse($series) as $row) {
            if (isset($row[$key]) && $row[$key] !== null) {
                return (float) $row[$key];
            }
        }

        return null;
    }
}
