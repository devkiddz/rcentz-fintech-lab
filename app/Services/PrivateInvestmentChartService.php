<?php

namespace App\Services;

use App\Models\PrivateInvestmentInstrument;
use Illuminate\Support\Collection;

class PrivateInvestmentChartService
{
    public function forInstrument(PrivateInvestmentInstrument $instrument): array
    {
        $rows = $instrument->prices()
            ->where('timeframe', '1d')
            ->orderBy('recorded_at')
            ->get();

        $series = $this->normalize($rows);

        $timeframes = [
            '1w' => array_slice($series, -7),
            '1m' => array_slice($series, -30),
            '3m' => $series,
            'all' => $series,
        ];

        return [
            'source' => 'private_investment_price_history',
            'has_chart' => count($series) >= 2,
            'current_price' => (float) $instrument->current_price,
            'previous_close' => (float) $instrument->previous_price,
            'series' => $series,
            'timeframes' => $timeframes,
            'default_timeframe' => count($timeframes['1m']) >= 2 ? '1m' : 'all',
        ];
    }

    private function normalize(Collection $rows): array
    {
        return $rows->map(fn ($row) => [
            'time' => optional($row->recorded_at)?->toIso8601String(),
            'open' => (float) $row->open,
            'high' => (float) $row->high,
            'low' => (float) $row->low,
            'close' => (float) $row->close,
            'volume' => 0,
        ])->filter(fn ($row) => $row['time'] && $row['close'] > 0)
          ->values()
          ->all();
    }
}
