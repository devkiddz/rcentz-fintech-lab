<?php

namespace App\Services;

use App\Models\PrivateMarketReference;
use App\Models\PublicInvestmentBaseAsset;
use Carbon\Carbon;

final class InvestmentBaseAssetPerformanceService
{
    public function __construct(
        private readonly PrivateInvestmentReserveMarketService $publicMarkets
    ) {}

    public function forPublic(
        PublicInvestmentBaseAsset $baseAsset
    ): array {
        $baseAsset->loadMissing('marketInstrument');

        $market = $baseAsset->marketInstrument;

        if (! $market) {
            return $this->emptyAnalysis(
                'public_market_history',
                2,
                'USD'
            );
        }

        $history = $this->publicMarkets->dailyHistory(
            $market,
            365
        );

        $series = collect($history)
            ->filter(fn ($row) =>
                ! empty($row['time'])
                && (float) ($row['close'] ?? 0) > 0
            )
            ->map(fn ($row) => [
                'time' => $row['time'] instanceof \DateTimeInterface
                    ? $row['time']->format(DATE_ATOM)
                    : Carbon::parse($row['time'])->format(DATE_ATOM),
                'open' => (float) ($row['open'] ?? $row['close']),
                'high' => (float) ($row['high'] ?? $row['close']),
                'low' => (float) ($row['low'] ?? $row['close']),
                'close' => (float) $row['close'],
                'volume' => 0,
            ])
            ->sortBy('time')
            ->values()
            ->all();

        try {
            $current = $this->publicMarkets->currentPrice($market);
        } catch (\Throwable) {
            $current = (float) (
                collect($series)->last()['close']
                ?? 0
            );
        }

        if ($current > 0) {
            $last = collect($series)->last();
            $lastClose = (float) ($last['close'] ?? 0);
            $lastDate = ! empty($last['time'])
                ? Carbon::parse($last['time'])->toDateString()
                : null;

            if (
                ! $last
                || $lastDate !== now()->toDateString()
                || abs($lastClose - $current) > 0.00000001
            ) {
                $series[] = [
                    'time' => now()->format(DATE_ATOM),
                    'open' => $current,
                    'high' => $current,
                    'low' => $current,
                    'close' => $current,
                    'volume' => 0,
                ];
            }
        }

        $previous = 0.0;

        if (count($series) >= 2) {
            $previous = (float) $series[count($series) - 2]['close'];
        } elseif (count($series) === 1) {
            $previous = (float) $series[0]['close'];
        }

        return $this->buildAnalysis(
            $series,
            $current,
            $previous,
            'public_market_history',
            max(0, min(8, (int) $market->price_precision)),
            (string) ($market->quote_asset ?: 'USD'),
            true
        );
    }

    public function forPrivate(
        PrivateMarketReference $reference
    ): array {
        $rows = $reference->prices()
            ->oldest('recorded_at')
            ->limit(1000)
            ->get();

        $series = $rows
            ->filter(fn ($row) =>
                $row->recorded_at
                && (float) $row->price > 0
            )
            ->map(function ($row) {
                $price = (float) $row->price;

                return [
                    'time' => $row->recorded_at->format(DATE_ATOM),
                    'open' => $price,
                    'high' => $price,
                    'low' => $price,
                    'close' => $price,
                    'volume' => 0,
                ];
            })
            ->values()
            ->all();

        return $this->buildAnalysis(
            $series,
            (float) $reference->current_price,
            (float) (
                $reference->previous_price
                ?? $reference->current_price
            ),
            'private_base_asset_valuation',
            2,
            (string) $reference->currency,
            false
        );
    }

    private function buildAnalysis(
        array $series,
        float $current,
        float $previous,
        string $source,
        int $precision,
        string $currency,
        bool $supportsCandles
    ): array {
        $series = collect($series)
            ->sortBy('time')
            ->values()
            ->all();

        $closes = collect($series)
            ->pluck('close')
            ->map(fn ($value) => (float) $value);

        $first = (float) ($closes->first() ?? $current);
        $last = (float) ($closes->last() ?? $current);

        if ($current <= 0) {
            $current = $last;
        }

        if ($previous <= 0) {
            $previous = count($series) >= 2
                ? (float) $series[count($series) - 2]['close']
                : $current;
        }

        $change = $current - $previous;
        $changePercent = $previous > 0
            ? ($change / $previous) * 100
            : 0;

        $periodChange = $first > 0
            ? (($current - $first) / $first) * 100
            : 0;

        $highs = collect($series)
            ->pluck('high')
            ->map(fn ($value) => (float) $value);

        $lows = collect($series)
            ->pluck('low')
            ->map(fn ($value) => (float) $value);

        $timeframes = [
            '1w' => $this->since($series, 7),
            '1m' => $this->since($series, 30),
            '3m' => $this->since($series, 90),
            '1y' => $this->since($series, 365),
            'all' => $series,
        ];

        $default = collect(['1m', '3m', '1y', 'all'])
            ->first(
                fn ($frame) =>
                    count($timeframes[$frame] ?? []) >= 2
            ) ?? 'all';

        return [
            'source' => $source,
            'series' => $series,
            'timeframes' => $timeframes,
            'default_timeframe' => $default,
            'has_chart' => count($series) >= 2,
            'supports_candles' => $supportsCandles,
            'current_price' => $current,
            'previous_close' => $previous,
            'change_amount' => $change,
            'change_percent' => $changePercent,
            'period_change_percent' => $periodChange,
            'direction' => $change > 0
                ? 'UP'
                : ($change < 0 ? 'DOWN' : 'FLAT'),
            'period_high' => $highs->isNotEmpty()
                ? (float) $highs->max()
                : $current,
            'period_low' => $lows->isNotEmpty()
                ? (float) $lows->min()
                : $current,
            'history_points' => count($series),
            'price_precision' => $precision,
            'quote_asset' => strtoupper($currency),
        ];
    }

    private function since(
        array $series,
        int $days
    ): array {
        $cutoff = now()->subDays($days);

        return collect($series)
            ->filter(fn ($row) =>
                Carbon::parse($row['time'])->gte($cutoff)
            )
            ->values()
            ->all();
    }

    private function emptyAnalysis(
        string $source,
        int $precision,
        string $currency
    ): array {
        return [
            'source' => $source,
            'series' => [],
            'timeframes' => [
                '1w' => [],
                '1m' => [],
                '3m' => [],
                '1y' => [],
                'all' => [],
            ],
            'default_timeframe' => 'all',
            'has_chart' => false,
            'supports_candles' => false,
            'current_price' => 0,
            'previous_close' => 0,
            'change_amount' => 0,
            'change_percent' => 0,
            'period_change_percent' => 0,
            'direction' => 'FLAT',
            'period_high' => 0,
            'period_low' => 0,
            'history_points' => 0,
            'price_precision' => $precision,
            'quote_asset' => strtoupper($currency),
        ];
    }
}
