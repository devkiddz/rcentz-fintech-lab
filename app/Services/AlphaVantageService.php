<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AlphaVantageService
{
    private string $baseUrl = 'https://www.alphavantage.co/query';
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.alpha_vantage.api_key');
    }

    public function isAvailable(): bool
    {
        return filled($this->apiKey);
    }

    /**
     * Fetch daily OHLCV history.
     *
     * Returns:
     * [
     *   'symbol' => 'AAPL',
     *   'rows' => [
     *      ['date'=>'2026-09-15','open'=>...,'high'=>...,'low'=>...,'close'=>...,'volume'=>...],
     *   ],
     *   'meta' => [...]
     * ]
     */
    public function daily(string $symbol, string $outputSize = 'compact'): ?array
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $outputSize = in_array($outputSize, ['compact','full'], true) ? $outputSize : 'compact';

        try {
            $response = Http::timeout(20)
                ->retry(2, 750)
                ->get($this->baseUrl, [
                    'function' => 'TIME_SERIES_DAILY',
                    'symbol' => strtoupper($symbol),
                    'outputsize' => $outputSize,
                    'apikey' => $this->apiKey,
                ]);

            if (! $response->successful()) {
                Log::warning('Alpha Vantage daily history request failed', [
                    'symbol' => $symbol,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $data = $response->json();

            if (isset($data['Note']) || isset($data['Information']) || isset($data['Error Message'])) {
                Log::warning('Alpha Vantage daily history unavailable', [
                    'symbol' => $symbol,
                    'message' => $data['Note']
                        ?? $data['Information']
                        ?? $data['Error Message']
                        ?? 'Unknown Alpha Vantage response',
                ]);
                return null;
            }

            $series = $data['Time Series (Daily)'] ?? null;

            if (! is_array($series) || empty($series)) {
                return null;
            }

            $rows = collect($series)
                ->map(function (array $row, string $date) {
                    return [
                        'date' => $date,
                        'open' => (float)($row['1. open'] ?? 0),
                        'high' => (float)($row['2. high'] ?? 0),
                        'low' => (float)($row['3. low'] ?? 0),
                        'close' => (float)($row['4. close'] ?? 0),
                        'volume' => (int)($row['5. volume'] ?? 0),
                    ];
                })
                ->filter(fn ($row) =>
                    $row['open'] > 0 &&
                    $row['high'] > 0 &&
                    $row['low'] > 0 &&
                    $row['close'] > 0
                )
                ->sortBy('date')
                ->values()
                ->all();

            if (count($rows) < 2) {
                return null;
            }

            return [
                'symbol' => strtoupper($symbol),
                'rows' => $rows,
                'meta' => $data['Meta Data'] ?? [],
            ];
        } catch (\Throwable $e) {
            Log::error('Alpha Vantage daily history exception', [
                'symbol' => $symbol,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
