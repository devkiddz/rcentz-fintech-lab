<?php

namespace App\Services;

class SignalAnalysisEngine
{
    private const COMPONENTS = [
        'trend' => 18,
        'momentum' => 16,
        'moving_averages' => 16,
        'structure' => 20,
        'levels' => 12,
        'timeframe_alignment' => 13,
        'analyst' => 3,
        'volume' => 2,
    ];

    public function analyze(array $context, ?string $timeframe = null): array
    {
        [$selectedTimeframe, $series] = $this->selectSeries(
            (array) ($context['timeframes'] ?? []),
            $timeframe ?: ($context['default_timeframe'] ?? null)
        );

        $price = (float) ($context['current_price'] ?? 0);
        $momentum = (float) ($context['momentum_percent'] ?? 0);

        $biases = [
            'trend' => $this->trendBias((string) ($context['trend'] ?? 'Neutral')),
            'momentum' => $this->clamp($momentum / 2.0),
            'moving_averages' => $this->movingAverageBias($price, $context),
            'structure' => $this->structureBias($series),
            'levels' => $this->levelBias($price, $context['support'] ?? null, $context['resistance'] ?? null),
            'timeframe_alignment' => $this->timeframeAlignmentBias((array) ($context['timeframes'] ?? [])),
            'analyst' => $this->analystBias((array) ($context['analyst'] ?? [])),
            'volume' => $this->volumeBias($momentum, $context['volume_vs_average'] ?? null),
        ];

        $components = [];
        $directionalScore = 0.0;

        foreach (self::COMPONENTS as $name => $weight) {
            $bias = $this->clamp((float) ($biases[$name] ?? 0));
            $points = $bias * $weight;
            $directionalScore += $points;
            $components[$name] = [
                'bias' => round($bias, 4),
                'weight' => $weight,
                'points' => round($points, 4),
                'label' => $this->biasLabel($bias),
            ];
        }

        $directionalScore = round($this->clamp($directionalScore / 100.0) * 100.0, 2);
        $direction = $directionalScore >= 18
            ? 'buy'
            : ($directionalScore <= -18 ? 'sell' : 'neutral');

        $dataQuality = $this->dataQuality($context, $series);
        $confluence = round(min(100, (abs($directionalScore) * 0.85) + ($dataQuality * 0.15)), 2);

        return [
            'symbol' => $context['symbol'] ?? null,
            'marketplace' => $context['marketplace'] ?? null,
            'timeframe' => $selectedTimeframe,
            'market_price' => $price,
            'direction' => $direction,
            'directional_score' => $directionalScore,
            'confluence_score' => $confluence,
            'data_quality' => $dataQuality,
            'components' => $components,
            'sample_count' => count($series),
            'atr' => $this->averageTrueRange($series, $price),
            'rationale' => $this->rationale($components, $direction),
        ];
    }

    private function selectSeries(array $timeframes, ?string $preferred): array
    {
        if ($preferred && isset($timeframes[$preferred]) && count((array) $timeframes[$preferred]) >= 2) {
            return [$preferred, array_values((array) $timeframes[$preferred])];
        }

        $selected = null;
        $series = [];

        foreach ($timeframes as $name => $rows) {
            $rows = array_values((array) $rows);
            if (count($rows) > count($series)) {
                $selected = (string) $name;
                $series = $rows;
            }
        }

        return [$selected ?: ($preferred ?: '15m'), $series];
    }

    private function trendBias(string $trend): float
    {
        return match (strtolower($trend)) {
            'bullish' => 1.0,
            'bearish' => -1.0,
            default => 0.0,
        };
    }

    private function movingAverageBias(float $price, array $context): float
    {
        if ($price <= 0) {
            return 0;
        }

        $votes = [];
        foreach (['sma20', 'sma50', 'sma200'] as $key) {
            $value = isset($context[$key]) ? (float) $context[$key] : 0;
            if ($value <= 0) {
                continue;
            }

            $distance = ($price - $value) / $value;
            $votes[] = $this->clamp($distance / 0.03);
        }

        return $votes ? array_sum($votes) / count($votes) : 0;
    }

    private function structureBias(array $series): float
    {
        $series = array_values(array_filter($series, fn ($row) => (float) ($row['close'] ?? 0) > 0));
        $count = count($series);

        if ($count < 3) {
            return 0;
        }

        $window = array_slice($series, -min(18, $count));
        $segment = max(1, (int) floor(count($window) / 3));
        $first = array_slice($window, 0, $segment);
        $last = array_slice($window, -$segment);

        $firstHigh = $this->average(array_column($first, 'high'));
        $lastHigh = $this->average(array_column($last, 'high'));
        $firstLow = $this->average(array_column($first, 'low'));
        $lastLow = $this->average(array_column($last, 'low'));
        $firstClose = $this->average(array_column($first, 'close'));
        $lastClose = $this->average(array_column($last, 'close'));

        $moves = [];
        foreach ([[$firstHigh, $lastHigh], [$firstLow, $lastLow], [$firstClose, $lastClose]] as [$from, $to]) {
            if ($from > 0) {
                $moves[] = ($to - $from) / $from;
            }
        }

        if (! $moves) {
            return 0;
        }

        return $this->clamp((array_sum($moves) / count($moves)) / 0.02);
    }

    private function levelBias(float $price, mixed $support, mixed $resistance): float
    {
        $support = (float) ($support ?? 0);
        $resistance = (float) ($resistance ?? 0);

        if ($price <= 0) {
            return 0;
        }

        if ($resistance > 0 && $price > $resistance * 1.001) {
            return 1;
        }

        if ($support > 0 && $price < $support * 0.999) {
            return -1;
        }

        if ($support > 0 && $resistance > $support && $price >= $support && $price <= $resistance) {
            $position = ($price - $support) / ($resistance - $support);
            return $this->clamp(($position - 0.5) * 2);
        }

        return 0;
    }

    private function timeframeAlignmentBias(array $timeframes): float
    {
        $biases = [];

        foreach (['5m', '15m', '1h', '4h', '1d'] as $name) {
            $rows = array_values((array) ($timeframes[$name] ?? []));
            if (count($rows) < 3) {
                continue;
            }

            $slice = array_slice($rows, -min(12, count($rows)));
            $first = (float) ($slice[0]['close'] ?? 0);
            $last = (float) ($slice[count($slice) - 1]['close'] ?? 0);

            if ($first <= 0 || $last <= 0) {
                continue;
            }

            $move = ($last - $first) / $first;
            $biases[] = $this->clamp($move / 0.01);
        }

        return $biases ? array_sum($biases) / count($biases) : 0;
    }

    private function analystBias(array $analyst): float
    {
        $total = (int) ($analyst['total'] ?? 0);
        if ($total <= 0) {
            return 0;
        }

        $percentage = (float) ($analyst['percentage'] ?? 50);
        return $this->clamp(($percentage - 50) / 35);
    }

    private function volumeBias(float $momentum, mixed $volumeVsAverage): float
    {
        if ($volumeVsAverage === null || abs($momentum) < 0.05) {
            return 0;
        }

        $volume = (float) $volumeVsAverage;
        $strength = $this->clamp(abs($volume) / 50);
        return ($momentum >= 0 ? 1 : -1) * $strength;
    }

    private function dataQuality(array $context, array $series): float
    {
        $seriesScore = min(50, count($series) * 2.5);
        $checks = [
            (float) ($context['current_price'] ?? 0) > 0,
            (bool) ($context['has_chart'] ?? false),
            ! empty($context['analysis_source']),
            ! empty($context['default_timeframe']),
            ($context['support'] ?? null) !== null,
            ($context['resistance'] ?? null) !== null,
            ($context['sma20'] ?? null) !== null,
            count((array) ($context['timeframes'] ?? [])) >= 3,
            isset($context['trend']),
            isset($context['momentum_percent']),
        ];

        $metricScore = count(array_filter($checks)) * 5;
        return round(min(100, $seriesScore + $metricScore), 2);
    }

    private function averageTrueRange(array $series, float $price): float
    {
        $rows = array_slice(array_values($series), -14);
        $ranges = [];
        $previousClose = null;

        foreach ($rows as $row) {
            $high = (float) ($row['high'] ?? 0);
            $low = (float) ($row['low'] ?? 0);
            $close = (float) ($row['close'] ?? 0);

            if ($high <= 0 || $low <= 0 || $close <= 0) {
                continue;
            }

            $range = $high - $low;
            if ($previousClose !== null && $previousClose > 0) {
                $range = max($range, abs($high - $previousClose), abs($low - $previousClose));
            }

            if ($range > 0) {
                $ranges[] = $range;
            }
            $previousClose = $close;
        }

        if ($ranges) {
            return round(array_sum($ranges) / count($ranges), 8);
        }

        return round(max($price * 0.005, 0.000001), 8);
    }

    private function rationale(array $components, string $direction): array
    {
        if (! in_array($direction, ['buy', 'sell'], true)) {
            return ['Directional confluence is not yet strong enough for a trade setup.'];
        }

        $sign = $direction === 'buy' ? 1 : -1;
        $labels = [
            'trend' => 'trend',
            'momentum' => 'momentum',
            'moving_averages' => 'moving-average alignment',
            'structure' => 'market structure',
            'levels' => 'support/resistance position',
            'timeframe_alignment' => 'multi-timeframe alignment',
            'analyst' => 'external analyst context',
            'volume' => 'volume confirmation',
        ];

        $reasons = [];
        foreach ($components as $name => $component) {
            $bias = (float) ($component['bias'] ?? 0);
            if (($bias * $sign) >= 0.25) {
                $reasons[] = ucfirst($labels[$name] ?? $name).' supports the '.$direction.' bias.';
            }
        }

        return $reasons ?: ['Directional evidence exists but confirmation remains limited.'];
    }

    private function biasLabel(float $bias): string
    {
        if ($bias >= 0.6) return 'bullish';
        if ($bias >= 0.2) return 'lean_bullish';
        if ($bias <= -0.6) return 'bearish';
        if ($bias <= -0.2) return 'lean_bearish';
        return 'neutral';
    }

    private function average(array $values): float
    {
        $values = array_values(array_filter(array_map('floatval', $values), fn ($value) => $value > 0));
        return $values ? array_sum($values) / count($values) : 0;
    }

    private function clamp(float $value, float $min = -1, float $max = 1): float
    {
        return max($min, min($max, $value));
    }
}
