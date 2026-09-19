<?php

namespace App\Services;

use App\Models\CryptoCandle;
use App\Models\CryptoPair;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CryptoMarketDataService
{
    public function __construct(
        private readonly CryptoAlphaVantageService $alphaVantage
    ) {}

    public function refreshDaily(CryptoPair $pair): array
    {
        if (! $pair->external_feed_enabled) {
            throw new RuntimeException("External crypto feed is disabled for {$pair->display_symbol}.");
        }

        $payload = $this->alphaVantage->daily($pair->base_asset, $pair->quote_asset);

        if (! $payload || empty($payload['rows'])) {
            throw new RuntimeException("No real crypto daily history was returned for {$pair->display_symbol}.");
        }

        $rows = $payload['rows'];
        $last = end($rows);
        $previous = count($rows) >= 2 ? $rows[count($rows) - 2] : null;

        DB::transaction(function () use ($pair, $rows, $last, $previous) {
            foreach ($rows as $row) {
                CryptoCandle::updateOrCreate(
                    [
                        'crypto_pair_id' => $pair->id,
                        'interval' => '1d',
                        'timestamp' => CarbonImmutable::parse($row['date'])->startOfDay(),
                    ],
                    [
                        'open' => $row['open'],
                        'high' => $row['high'],
                        'low' => $row['low'],
                        'close' => $row['close'],
                        'volume' => $row['volume'],
                        'source' => 'alpha_vantage',
                    ]
                );
            }

            $pair->update([
                'current_rate' => (float) $last['close'],
                'previous_close' => $previous ? (float) $previous['close'] : null,
                'last_updated' => now(),
            ]);
        });

        return [
            'pair' => $pair->fresh(),
            'rows' => count($rows),
            'latest_date' => $last['date'],
            'latest_close' => (float) $last['close'],
        ];
    }

    public function currentRate(CryptoPair $pair): ?float
    {
        $rate = (float) ($pair->current_rate ?? 0);
        return $rate > 0 ? $rate : null;
    }

    public function context(CryptoPair $pair): array
    {
        $candles = $pair->candles()
            ->where('interval', '1d')
            ->orderByDesc('timestamp')
            ->limit(220)
            ->get()
            ->sortBy('timestamp')
            ->values();

        $series = $candles->map(fn (CryptoCandle $candle) => [
            'time' => $candle->timestamp?->toIso8601String(),
            'open' => (float) $candle->open,
            'high' => (float) $candle->high,
            'low' => (float) $candle->low,
            'close' => (float) $candle->close,
            'volume' => (float) ($candle->volume ?? 0),
        ])->filter(fn ($row) => $row['time'] && $row['close'] > 0)->values()->all();

        $series = $this->withMovingAverages($series);
        $lastSeries = $series ? $series[count($series) - 1] : null;
        $price = $this->currentRate($pair) ?: (float) ($lastSeries['close'] ?? 0);
        $closes = collect($series)->pluck('close')->map(fn ($value) => (float) $value)->values();
        $first = (float) ($closes->first() ?? $price);
        $last = (float) ($closes->last() ?? $price);
        $momentum = $first > 0 ? (($last - $first) / $first) * 100 : 0;
        $recent = collect($series)->take(-min(30, count($series)));
        $support = (float) ($recent->min('low') ?? 0);
        $resistance = (float) ($recent->max('high') ?? 0);

        return [
            'asset_class' => 'crypto',
            'crypto_pair_id' => $pair->id,
            'symbol' => $pair->symbol,
            'display_symbol' => $pair->display_symbol,
            'label' => $pair->name,
            'marketplace' => 'live',
            'current_price' => $price,
            'analysis_source' => count($series) >= 2 ? 'crypto_daily_history' : 'crypto_spot_only',
            'has_chart' => count($series) >= 1,
            'trend' => $momentum > 1.0 ? 'Bullish' : ($momentum < -1.0 ? 'Bearish' : 'Neutral'),
            'momentum_percent' => $momentum,
            'momentum_label' => $momentum > 1.0 ? 'Bullish' : ($momentum < -1.0 ? 'Bearish' : 'Neutral'),
            'support' => $support ?: null,
            'resistance' => $resistance ?: null,
            'sma20' => $this->lastSma($series, 'sma20'),
            'sma50' => $this->lastSma($series, 'sma50'),
            'sma200' => $this->lastSma($series, 'sma200'),
            'risk_reward' => 'Market-derived',
            'volume_current' => (float) ($lastSeries['volume'] ?? 0),
            'volume_average' => collect($series)->avg('volume') ?: 0,
            'volume_vs_average' => null,
            'timeframes' => [
                '5m' => [],
                '15m' => [],
                '1h' => [],
                '4h' => [],
                '1d' => $series,
                '1w' => array_slice($series, -5),
                '1m' => array_slice($series, -22),
                '3m' => array_slice($series, -66),
                '1y' => $series,
            ],
            'default_timeframe' => '1d',
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
            'market_session' => '24/7',
            'active_sessions' => ['24_7'],
            'preferred_sessions' => ['24_7'],
            'preferred_session_active' => true,
            'captured_at' => now()->toIso8601String(),
        ];
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
