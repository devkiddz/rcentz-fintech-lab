<?php

namespace App\Services;

use App\Models\CurrencyRate;
use App\Models\ForexCandle;
use App\Models\ForexPair;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ForexMarketDataService
{
    public function __construct(
        private readonly ForexAlphaVantageService $alphaVantage,
        private readonly ForexSessionService $sessions
    ) {}

    public function refreshDaily(ForexPair $pair, string $outputSize = 'compact'): array
    {
        if (! $pair->external_feed_enabled) {
            throw new RuntimeException("External forex feed is disabled for {$pair->display_symbol}.");
        }

        $payload = $this->alphaVantage->daily(
            $pair->base_currency,
            $pair->quote_currency,
            $outputSize
        );

        if (! $payload || empty($payload['rows'])) {
            throw new RuntimeException("No real forex daily history was returned for {$pair->display_symbol}.");
        }

        $rows = $payload['rows'];
        $last = end($rows);
        $previous = count($rows) >= 2 ? $rows[count($rows) - 2] : null;

        DB::transaction(function () use ($pair, $rows, $last, $previous) {
            foreach ($rows as $row) {
                ForexCandle::updateOrCreate(
                    [
                        'forex_pair_id' => $pair->id,
                        'interval' => '1d',
                        'timestamp' => CarbonImmutable::parse($row['date'])->startOfDay(),
                    ],
                    [
                        'open' => $row['open'],
                        'high' => $row['high'],
                        'low' => $row['low'],
                        'close' => $row['close'],
                        'volume' => null,
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

    public function currentRate(ForexPair $pair): ?float
    {
        if ((float) ($pair->current_rate ?? 0) > 0) {
            return (float) $pair->current_rate;
        }

        $rates = CurrencyRate::query()
            ->whereIn('currency', [$pair->base_currency, $pair->quote_currency])
            ->pluck('rate', 'currency');

        $base = (float) ($rates[$pair->base_currency] ?? 0);
        $quote = (float) ($rates[$pair->quote_currency] ?? 0);

        if ($base <= 0 || $quote <= 0) {
            return null;
        }

        return $quote / $base;
    }

    public function context(ForexPair $pair): array
    {
        $candles = $pair->candles()
            ->where('interval', '1d')
            ->orderByDesc('timestamp')
            ->limit(220)
            ->get()
            ->sortBy('timestamp')
            ->values();

        $series = $candles->map(fn (ForexCandle $candle) => [
            'time' => $candle->timestamp?->toIso8601String(),
            'open' => (float) $candle->open,
            'high' => (float) $candle->high,
            'low' => (float) $candle->low,
            'close' => (float) $candle->close,
            'volume' => 0,
        ])->filter(fn ($row) => $row['time'] && $row['close'] > 0)->values()->all();

        $lastSeries = $series ? $series[count($series) - 1] : null;
        $price = $this->currentRate($pair) ?: (float) ($lastSeries['close'] ?? 0);
        $closes = collect($series)->pluck('close')->map(fn ($value) => (float) $value)->values();
        $first = (float) ($closes->first() ?? $price);
        $last = (float) ($closes->last() ?? $price);
        $momentum = $first > 0 ? (($last - $first) / $first) * 100 : 0;
        $recent = collect($series)->take(-min(30, count($series)));
        $support = (float) ($recent->min('low') ?? 0);
        $resistance = (float) ($recent->max('high') ?? 0);
        $sma20 = $this->sma($closes->all(), 20);
        $sma50 = $this->sma($closes->all(), 50);
        $sma200 = $this->sma($closes->all(), 200);
        $trend = 'Neutral';

        if ($sma20 && $price > $sma20 && (! $sma50 || $sma20 >= $sma50)) {
            $trend = 'Bullish';
        } elseif ($sma20 && $price < $sma20 && (! $sma50 || $sma20 <= $sma50)) {
            $trend = 'Bearish';
        }

        $session = $this->sessions->stateForPair($pair);

        return [
            'asset_class' => 'forex',
            'forex_pair_id' => $pair->id,
            'symbol' => $pair->symbol,
            'display_symbol' => $pair->display_symbol,
            'label' => $pair->name,
            'marketplace' => 'live',
            'current_price' => $price,
            'analysis_source' => count($series) >= 2 ? 'forex_daily_history' : 'forex_spot_only',
            'has_chart' => count($series) >= 1,
            'trend' => $trend,
            'momentum_percent' => $momentum,
            'momentum_label' => $momentum > 0.5 ? 'Bullish' : ($momentum < -0.5 ? 'Bearish' : 'Neutral'),
            'support' => $support ?: null,
            'resistance' => $resistance ?: null,
            'sma20' => $sma20,
            'sma50' => $sma50,
            'sma200' => $sma200,
            'risk_reward' => 'Market-derived',
            'volume_current' => 0,
            'volume_average' => 0,
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
            'market_session' => $session['label'],
            'active_sessions' => $session['active_sessions'],
            'preferred_sessions' => $session['preferred_sessions'],
            'preferred_session_active' => $session['preferred_session_active'],
            'captured_at' => now()->toIso8601String(),
        ];
    }

    private function sma(array $values, int $period): ?float
    {
        $values = array_values(array_filter(array_map('floatval', $values), fn ($value) => $value > 0));
        if (count($values) < $period) {
            return null;
        }

        $slice = array_slice($values, -$period);
        return array_sum($slice) / count($slice);
    }
}
