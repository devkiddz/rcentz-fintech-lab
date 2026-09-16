<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\ControlledMarketInstrument;
use App\Models\StockCandle;
use App\Models\StockPriceHistory;
use App\Models\StockQuote;
use Illuminate\Support\Collection;

class StockAnalysisService
{
    public function forStock(Stock $stock): array
    {
        return $this->forStockInMarketplace(
            $stock,
            app(MarketPriceRouter::class)->activeMarketplace()
        );
    }

    public function forStockInMarketplace(Stock $stock, string $marketplace): array
    {
        $marketplace = app(MarketPriceRouter::class)->normalizeMarketplace($marketplace);

        if ($marketplace === 'controlled') {
            return $this->forControlledStock($stock);
        }

        // Read the raw persisted Live price. Stock::current_price intentionally
        // follows the active display marketplace and is therefore not suitable
        // when rendering a historical/non-active Live contract.
        $currentPrice = (float)($stock->live_current_price ?? 0);

        $dailyRows = StockPriceHistory::query()
            ->where('symbol', $stock->symbol)
            ->where('interval', '1D')
            ->orderByDesc('timestamp')
            ->limit(220)
            ->get()
            ->sortBy('timestamp')
            ->values();

        $dailySeries = $this->buildSeries($dailyRows, 'daily_history');

        // Historical data can become structurally stale when an old seed/history
        // no longer belongs to the same live price regime. Never allow a stale
        // 190-range series to compress a live 349-range chart.
        $dailySeries = $this->filterForPriceContinuity($dailySeries, $currentPrice, 0.20);

        $candleRows = StockCandle::query()
            ->where('symbol', $stock->symbol)
            ->where('interval', '15m')
            ->orderByDesc('started_at')
            ->limit(120)
            ->get()
            ->sortBy('started_at')
            ->values();

        $candleSeries = $this->buildSeries($candleRows, 'live_15m_candles');
        $candleSeries = $this->filterForPriceContinuity($candleSeries, $currentPrice, 0.12);

        $quoteSeries = StockQuote::query()
            ->where('symbol', $stock->symbol)
            ->orderByDesc('fetched_at')
            ->limit(120)
            ->get(['current_price','fetched_at'])
            ->sortBy('fetched_at')
            ->values()
            ->map(fn ($quote) => [
                'time' => optional($quote->fetched_at)?->toIso8601String(),
                'open' => (float)$quote->current_price,
                'high' => (float)$quote->current_price,
                'low' => (float)$quote->current_price,
                'close' => (float)$quote->current_price,
                'volume' => 0,
            ])
            ->filter(fn ($row) => $row['time'])
            ->values()
            ->all();

        $quoteSeries = $this->filterForPriceContinuity($quoteSeries, $currentPrice, 0.08);

        // Prefer the richest continuity-safe source. If historical daily data is
        // stale, fall forward to live candles/quotes rather than rendering a
        // visually misleading compressed chart.
        $series = [];
        $source = 'session_snapshot';

        if (count($dailySeries) >= 10) {
            $series = $dailySeries;
            $source = 'daily_history';
        } elseif (count($candleSeries) >= 2) {
            $series = $candleSeries;
            $source = 'live_15m_candles';
        } elseif (count($quoteSeries) >= 2) {
            $series = $quoteSeries;
            $source = 'recent_quotes';
        } else {
            $series = $this->sessionSnapshotSeries($stock, $quoteSeries);
            $source = 'session_snapshot';
        }

        $series = $this->withMovingAverages($series);

        $closes = collect($series)->pluck('close')->map(fn ($v) => (float)$v)->values();
        $volumes = collect($series)->pluck('volume')->map(fn ($v) => (float)$v)->filter(fn ($v) => $v > 0)->values();

        $last = (float)($closes->last() ?? $currentPrice);
        $first = (float)($closes->first() ?? $last);
        $momentum = $first > 0 ? (($last - $first) / $first) * 100 : 0;

        $recent = collect($series)->take(-min(30, count($series)));
        $support = (float)($recent->min('low') ?? 0);
        $resistance = (float)($recent->max('high') ?? 0);

        $sma20 = $this->lastSma($series, 'sma20');
        $sma50 = $this->lastSma($series, 'sma50');
        $sma200 = $this->lastSma($series, 'sma200');

        $trend = 'Neutral';
        if ($sma20 && $last > $sma20 && (!$sma50 || $sma20 >= $sma50)) $trend = 'Bullish';
        if ($sma20 && $last < $sma20 && (!$sma50 || $sma20 <= $sma50)) $trend = 'Bearish';

        // When we do not yet have enough MA history, use today's stored market
        // snapshot rather than pretending there is no directional context.
        if (! $sma20 && $stock->previous_close) {
            $previousClose = (float)$stock->previous_close;
            if ($previousClose > 0) {
                $dayMove = (($currentPrice - $previousClose) / $previousClose) * 100;
                $trend = $dayMove > 0.75 ? 'Bullish' : ($dayMove < -0.75 ? 'Bearish' : 'Neutral');
            }
        }

        $riskReward = 'Balanced';
        if ($support > 0 && $resistance > $last && $last > $support) {
            $upside = $resistance - $last;
            $downside = $last - $support;
            if ($upside > $downside * 1.25) $riskReward = 'Favorable';
            elseif ($downside > $upside * 1.25) $riskReward = 'Cautious';
        }

        $volumeAvg = $volumes->count() ? (float)$volumes->take(-20)->avg() : 0;
        $volumeCurrent = $volumes->count() ? (float)$volumes->last() : (float)($stock->volume ?? 0);
        $volumeVsAvg = $volumeAvg > 0 ? (($volumeCurrent - $volumeAvg) / $volumeAvg) * 100 : null;

        $latestQuote = StockQuote::getLatestQuote($stock->symbol);

        return [
            'source' => $source,
            'series' => $series,
            'has_chart' => count($series) >= 1,
            'current_price' => $currentPrice ?: $last,
            'previous_close' => (float)($stock->previous_close ?? 0),
            'support' => $support ?: null,
            'resistance' => $resistance ?: null,
            'sma20' => $sma20,
            'sma50' => $sma50,
            'sma200' => $sma200,
            'momentum_percent' => $momentum,
            'momentum_label' => $momentum > 1 ? 'Bullish' : ($momentum < -1 ? 'Bearish' : 'Neutral'),
            'trend' => $trend,
            'risk_reward' => $riskReward,
            'volume_current' => $volumeCurrent,
            'volume_average' => $volumeAvg,
            'volume_vs_average' => $volumeVsAvg,
            'timeframes' => $this->timeframeSeries($stock, $dailySeries, $candleSeries, $quoteSeries),
            'default_timeframe' => $this->defaultTimeframe($dailySeries, $candleSeries, $quoteSeries),
            'analyst' => [
                'label' => $latestQuote?->recommendation_label,
                'percentage' => $latestQuote?->recommendation_percentage,
                'strong_buy' => (int)($latestQuote?->strong_buy ?? 0),
                'buy' => (int)($latestQuote?->buy ?? 0),
                'hold' => (int)($latestQuote?->hold ?? 0),
                'sell' => (int)($latestQuote?->sell ?? 0),
                'strong_sell' => (int)($latestQuote?->strong_sell ?? 0),
                'total' => (int)($latestQuote?->total_recommendations ?? 0),
            ],
        ];
    }

    private function forControlledStock(Stock $stock): array
    {
        $instrument = ControlledMarketInstrument::query()
            ->where('stock_id', $stock->id)
            ->first();

        if (! $instrument) {
            return [
                'source' => 'controlled_market_unavailable',
                'series' => [],
                'has_chart' => false,
                'current_price' => 0,
                'previous_close' => 0,
                'support' => null,
                'resistance' => null,
                'sma20' => null,
                'sma50' => null,
                'sma200' => null,
                'momentum_percent' => 0,
                'momentum_label' => 'Neutral',
                'trend' => 'Neutral',
                'risk_reward' => 'Balanced',
                'volume_current' => 0,
                'volume_average' => 0,
                'volume_vs_average' => null,
                'timeframes' => ['5m'=>[],'15m'=>[],'1h'=>[],'4h'=>[],'1d'=>[],'1w'=>[],'1m'=>[],'3m'=>[],'1y'=>[]],
                'default_timeframe' => '5m',
                'analyst' => ['label'=>null,'percentage'=>null,'strong_buy'=>0,'buy'=>0,'hold'=>0,'sell'=>0,'strong_sell'=>0,'total'=>0],
            ];
        }

        $rows = $instrument->ticks()
            ->orderByDesc('ticked_at')
            ->limit(240)
            ->get()
            ->sortBy('ticked_at')
            ->values();

        $series = $rows->map(fn ($tick) => [
            'time' => optional($tick->ticked_at)?->toIso8601String(),
            'open' => (float)$tick->open,
            'high' => (float)$tick->high,
            'low' => (float)$tick->low,
            'close' => (float)$tick->close,
            'volume' => 0,
        ])->filter(fn ($row) => $row['time'] && $row['close'] > 0)->values()->all();

        if (! count($series)) {
            $current = (float)$instrument->current_price;
            $series = [[
                'time' => now()->toIso8601String(),
                'open' => $current,
                'high' => $current,
                'low' => $current,
                'close' => $current,
                'volume' => 0,
            ]];
        }

        $series = $this->withMovingAverages($series);
        $closes = collect($series)->pluck('close')->map(fn ($v) => (float)$v)->values();
        $last = (float)($closes->last() ?? $instrument->current_price);
        $first = (float)($closes->first() ?? $last);
        $momentum = $first > 0 ? (($last - $first) / $first) * 100 : 0;
        $recent = collect($series)->take(-min(30, count($series)));
        $support = (float)($recent->min('low') ?? 0);
        $resistance = (float)($recent->max('high') ?? 0);
        $sma20 = $this->lastSma($series, 'sma20');
        $sma50 = $this->lastSma($series, 'sma50');
        $sma200 = $this->lastSma($series, 'sma200');

        $trend = 'Neutral';
        if ($momentum > 0.10) $trend = 'Bullish';
        elseif ($momentum < -0.10) $trend = 'Bearish';

        $fiveMinute = $this->normalizePointSeries($series);
        $fifteenMinute = $this->aggregateCandles($fiveMinute, 15);
        $oneHour = $this->aggregateCandles($fiveMinute, 60);
        $fourHour = $this->aggregateCandles($fiveMinute, 240);

        return [
            'source' => 'controlled_market_ticks',
            'series' => $series,
            'has_chart' => count($series) >= 1,
            'current_price' => $last,
            'previous_close' => (float)$instrument->previous_price,
            'support' => $support ?: null,
            'resistance' => $resistance ?: null,
            'sma20' => $sma20,
            'sma50' => $sma50,
            'sma200' => $sma200,
            'momentum_percent' => $momentum,
            'momentum_label' => $momentum > 0.10 ? 'Bullish' : ($momentum < -0.10 ? 'Bearish' : 'Neutral'),
            'trend' => $trend,
            'risk_reward' => 'Controlled',
            'volume_current' => 0,
            'volume_average' => 0,
            'volume_vs_average' => null,
            'timeframes' => [
                '5m' => $fiveMinute,
                '15m' => $fifteenMinute ?: $fiveMinute,
                '1h' => $oneHour ?: $fiveMinute,
                '4h' => $fourHour ?: $fiveMinute,
                '1d' => $fiveMinute,
                '1w' => $fiveMinute,
                '1m' => $fiveMinute,
                '3m' => $fiveMinute,
                '1y' => $fiveMinute,
            ],
            'default_timeframe' => '5m',
            'analyst' => ['label'=>null,'percentage'=>null,'strong_buy'=>0,'buy'=>0,'hold'=>0,'sell'=>0,'strong_sell'=>0,'total'=>0],
        ];
    }

    private function filterForPriceContinuity(array $series, float $currentPrice, float $maxDeviation): array
    {
        if ($currentPrice <= 0) return $series;

        return collect($series)
            ->filter(function ($row) use ($currentPrice, $maxDeviation) {
                $close = (float)($row['close'] ?? 0);
                if ($close <= 0) return false;

                return abs($close - $currentPrice) / $currentPrice <= $maxDeviation;
            })
            ->values()
            ->all();
    }

    private function sessionSnapshotSeries(Stock $stock, array $compatibleQuotes = []): array
    {
        // V5.23.1: this helper is part of the explicit Live analysis path.
        // Never consult Stock::current_price here because that accessor follows
        // the globally active desk and can therefore leak Internal Feed context.
        $current = (float)($stock->live_current_price ?? 0);
        $open = (float)($stock->open ?? $stock->previous_close ?? $current);
        $high = (float)($stock->high ?? max($open, $current));
        $low = (float)($stock->low ?? min($open, $current));

        if ($current <= 0) return [];

        $high = max($high, $open, $current);
        $low = min(array_filter([$low, $open, $current], fn ($v) => $v > 0)) ?: min($open, $current);

        $baseTime = optional($stock->last_updated)?->copy()->startOfDay()->addHours(13)->addMinutes(30)
            ?? now()->startOfDay()->addHours(13)->addMinutes(30);

        $series = [[
            'time' => $baseTime->toIso8601String(),
            'open' => $open ?: $current,
            'high' => $high,
            'low' => $low,
            'close' => $current,
            'volume' => (float)($stock->volume ?? 0),
        ]];

        foreach ($compatibleQuotes as $quote) {
            if (!isset($quote['time'])) continue;
            if ($quote['time'] === $series[0]['time']) continue;
            $series[] = $quote;
        }

        usort($series, fn ($a,$b) => strcmp($a['time'],$b['time']));

        return $series;
    }

    private function defaultTimeframe(array $dailySeries, array $candleSeries, array $quoteSeries): string
    {
        // Once a real historical story exists, open on 1D instead of a sparse
        // intraday snapshot. Intraday remains available whenever enough live
        // observations have accumulated.
        if (count($dailySeries) >= 20) return '1d';
        if (count($candleSeries) >= 2) return '15m';
        if (count($quoteSeries) >= 2) return '5m';
        if (count($dailySeries) >= 2) return '1d';
        return '15m';
    }

    private function timeframeSeries(Stock $stock, array $dailySeries, array $candleSeries, array $quoteSeries): array
    {
        $fiveMinute = $this->normalizePointSeries($quoteSeries);
        $fifteenMinute = $this->normalizePointSeries($candleSeries);

        $oneHour = $this->aggregateCandles($fifteenMinute, 60);
        $fourHour = $this->aggregateCandles($fifteenMinute, 240);

        // Daily-and-above views come from the stored Alpha Vantage daily OHLCV history.
        // 1D shows the full daily candle story. Higher buttons change the visible
        // historical range, not the candle size, so users still see a proper story
        // instead of one giant aggregated candle.
        $oneDay = $this->normalizePointSeries($dailySeries);
        $oneWeek = array_slice($oneDay, -5);
        $oneMonth = array_slice($oneDay, -22);
        $threeMonth = array_slice($oneDay, -66);
        $oneYear = $oneDay;

        if (count($fifteenMinute) < 1) {
            $fifteenMinute = $this->sessionSnapshotSeries($stock, $quoteSeries);
        }

        return [
            '5m' => $fiveMinute,
            '15m' => $fifteenMinute,
            '1h' => $oneHour,
            '4h' => $fourHour,
            '1d' => $oneDay,
            '1w' => $oneWeek,
            '1m' => $oneMonth,
            '3m' => $threeMonth,
            '1y' => $oneYear,
        ];
    }

    private function normalizePointSeries(array $series): array
    {
        return collect($series)
            ->filter(fn ($row) => !empty($row['time']) && (float)($row['close'] ?? 0) > 0)
            ->sortBy('time')
            ->values()
            ->all();
    }

    private function aggregateCandles(array $series, int $minutes): array
    {
        if (count($series) < 2) return [];

        return collect($series)
            ->groupBy(function ($row) use ($minutes) {
                $timestamp = \Carbon\Carbon::parse($row['time']);
                $minuteOfDay = ($timestamp->hour * 60) + $timestamp->minute;
                $bucketMinute = intdiv($minuteOfDay, $minutes) * $minutes;

                return $timestamp->copy()
                    ->startOfDay()
                    ->addMinutes($bucketMinute)
                    ->toIso8601String();
            })
            ->map(function ($rows, $time) {
                $rows = $rows->sortBy('time')->values();

                return [
                    'time' => $time,
                    'open' => (float)$rows->first()['open'],
                    'high' => (float)$rows->max('high'),
                    'low' => (float)$rows->min('low'),
                    'close' => (float)$rows->last()['close'],
                    'volume' => (float)$rows->sum('volume'),
                ];
            })
            ->values()
            ->all();
    }

    private function aggregateDaily(array $series, int $daysPerBucket): array
    {
        if (count($series) < 2) return [];

        return collect($series)
            ->values()
            ->chunk($daysPerBucket)
            ->map(function ($rows) {
                $rows = $rows->values();
                if ($rows->isEmpty()) return null;

                return [
                    'time' => $rows->first()['time'],
                    'open' => (float)$rows->first()['open'],
                    'high' => (float)$rows->max('high'),
                    'low' => (float)$rows->min('low'),
                    'close' => (float)$rows->last()['close'],
                    'volume' => (float)$rows->sum('volume'),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function buildSeries(Collection $rows, string $source): array
    {
        return $rows->map(function ($row) use ($source) {
            if ($source === 'daily_history') {
                return [
                    'time' => optional($row->timestamp)?->toIso8601String(),
                    'open' => (float)$row->open,
                    'high' => (float)$row->high,
                    'low' => (float)$row->low,
                    'close' => (float)$row->close,
                    'volume' => (float)($row->volume ?? 0),
                ];
            }

            return [
                'time' => optional($row->started_at)?->toIso8601String(),
                'open' => (float)$row->open,
                'high' => (float)$row->high,
                'low' => (float)$row->low,
                'close' => (float)$row->close,
                'volume' => 0,
            ];
        })
        ->filter(fn ($row) => $row['time'] && $row['close'] > 0)
        ->values()
        ->all();
    }

    private function withMovingAverages(array $series): array
    {
        $closes = [];
        foreach ($series as $index => $row) {
            $closes[] = (float)$row['close'];
            $series[$index]['sma20'] = $this->averageTail($closes, 20);
            $series[$index]['sma50'] = $this->averageTail($closes, 50);
            $series[$index]['sma200'] = $this->averageTail($closes, 200);
        }

        return $series;
    }

    private function averageTail(array $values, int $period): ?float
    {
        if (count($values) < $period) return null;
        $slice = array_slice($values, -$period);
        return array_sum($slice) / $period;
    }

    private function lastSma(array $series, string $key): ?float
    {
        for ($i = count($series) - 1; $i >= 0; $i--) {
            if (isset($series[$i][$key]) && $series[$i][$key] !== null) {
                return (float)$series[$i][$key];
            }
        }
        return null;
    }
}
