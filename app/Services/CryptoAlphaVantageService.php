<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CryptoAlphaVantageService
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

    public function daily(string $baseAsset, string $quoteAsset = 'USD'): ?array
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $baseAsset = strtoupper(trim($baseAsset));
        $quoteAsset = strtoupper(trim($quoteAsset));

        try {
            $response = Http::timeout(25)
                ->retry(2, 750)
                ->get($this->baseUrl, [
                    'function' => 'DIGITAL_CURRENCY_DAILY',
                    'symbol' => $baseAsset,
                    'market' => $quoteAsset,
                    'apikey' => $this->apiKey,
                ]);

            if (! $response->successful()) {
                Log::warning('Alpha Vantage crypto daily request failed', [
                    'pair' => $baseAsset.$quoteAsset,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $data = $response->json();

            if (isset($data['Note']) || isset($data['Information']) || isset($data['Error Message'])) {
                Log::warning('Alpha Vantage crypto daily unavailable', [
                    'pair' => $baseAsset.$quoteAsset,
                    'message' => $data['Note']
                        ?? $data['Information']
                        ?? $data['Error Message']
                        ?? 'Unknown Alpha Vantage response',
                ]);
                return null;
            }

            $series = $data['Time Series (Digital Currency Daily)'] ?? null;
            if (! is_array($series) || empty($series)) {
                return null;
            }

            $rows = collect($series)
                ->map(function (array $row, string $date) use ($quoteAsset) {
                    $open = $this->field($row, 'open', $quoteAsset);
                    $high = $this->field($row, 'high', $quoteAsset);
                    $low = $this->field($row, 'low', $quoteAsset);
                    $close = $this->field($row, 'close', $quoteAsset);
                    $volume = $this->field($row, 'volume', null, false);

                    return [
                        'date' => $date,
                        'open' => $open,
                        'high' => $high,
                        'low' => $low,
                        'close' => $close,
                        'volume' => $volume > 0 ? $volume : null,
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

            // Keep the market footprint aligned with the current Stock/Forex
            // analysis authority: latest 100 real daily observations.
            $rows = array_slice($rows, -100);

            return [
                'symbol' => $baseAsset.$quoteAsset,
                'base_asset' => $baseAsset,
                'quote_asset' => $quoteAsset,
                'rows' => $rows,
                'meta' => $data['Meta Data'] ?? [],
            ];
        } catch (\Throwable $e) {
            Log::error('Alpha Vantage crypto daily exception', [
                'pair' => $baseAsset.$quoteAsset,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function field(array $row, string $needle, ?string $quoteAsset = null, bool $required = true): float
    {
        $needle = strtolower($needle);
        $quoteAsset = $quoteAsset ? strtolower($quoteAsset) : null;
        $matches = [];

        foreach ($row as $key => $value) {
            $normalized = strtolower((string) $key);
            if (! str_contains($normalized, $needle)) {
                continue;
            }

            $score = 0;
            if ($quoteAsset && str_contains($normalized, '('.$quoteAsset.')')) {
                $score += 10;
            }
            if (preg_match('/^\\d+\\.\\s*'.preg_quote($needle, '/').'$/i', trim((string) $key))) {
                $score += 5;
            }

            $numeric = (float) $value;
            if ($numeric > 0 || ! $required) {
                $matches[] = [$score, $numeric];
            }
        }

        if (! $matches) {
            return 0.0;
        }

        usort($matches, fn ($a, $b) => $b[0] <=> $a[0]);
        return (float) $matches[0][1];
    }
}
