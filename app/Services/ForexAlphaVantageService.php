<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ForexAlphaVantageService
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

    public function daily(string $baseCurrency, string $quoteCurrency, string $outputSize = 'compact'): ?array
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $baseCurrency = strtoupper(trim($baseCurrency));
        $quoteCurrency = strtoupper(trim($quoteCurrency));
        $outputSize = in_array($outputSize, ['compact', 'full'], true) ? $outputSize : 'compact';

        try {
            $response = Http::timeout(20)
                ->retry(2, 750)
                ->get($this->baseUrl, [
                    'function' => 'FX_DAILY',
                    'from_symbol' => $baseCurrency,
                    'to_symbol' => $quoteCurrency,
                    'outputsize' => $outputSize,
                    'apikey' => $this->apiKey,
                ]);

            if (! $response->successful()) {
                Log::warning('Alpha Vantage forex daily request failed', [
                    'pair' => $baseCurrency.$quoteCurrency,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $data = $response->json();

            if (isset($data['Note']) || isset($data['Information']) || isset($data['Error Message'])) {
                Log::warning('Alpha Vantage forex daily unavailable', [
                    'pair' => $baseCurrency.$quoteCurrency,
                    'message' => $data['Note']
                        ?? $data['Information']
                        ?? $data['Error Message']
                        ?? 'Unknown Alpha Vantage response',
                ]);
                return null;
            }

            $series = $data['Time Series FX (Daily)'] ?? null;
            if (! is_array($series) || empty($series)) {
                return null;
            }

            $rows = collect($series)
                ->map(function (array $row, string $date) {
                    return [
                        'date' => $date,
                        'open' => (float) ($row['1. open'] ?? 0),
                        'high' => (float) ($row['2. high'] ?? 0),
                        'low' => (float) ($row['3. low'] ?? 0),
                        'close' => (float) ($row['4. close'] ?? 0),
                    ];
                })
                ->filter(fn ($row) =>
                    $row['open'] > 0
                    && $row['high'] > 0
                    && $row['low'] > 0
                    && $row['close'] > 0
                )
                ->sortBy('date')
                ->values()
                ->all();

            if (count($rows) < 2) {
                return null;
            }

            return [
                'symbol' => $baseCurrency.$quoteCurrency,
                'base_currency' => $baseCurrency,
                'quote_currency' => $quoteCurrency,
                'rows' => $rows,
                'meta' => $data['Meta Data'] ?? [],
            ];
        } catch (\Throwable $e) {
            Log::error('Alpha Vantage forex daily exception', [
                'pair' => $baseCurrency.$quoteCurrency,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
