<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class CommodityAlphaVantageService
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
     * Current precious-metal spot price. Alpha Vantage accepts XAU/GOLD and XAG/SILVER.
     */
    public function metalSpot(string $providerSymbol): ?float
    {
        if (! $this->isAvailable()) {
            return null;
        }

        $payload = $this->request([
            'function' => 'GOLD_SILVER_SPOT',
            'symbol' => strtoupper($providerSymbol),
            'apikey' => $this->apiKey,
        ]);

        foreach (['price', 'spot_price', 'current_price', 'value'] as $key) {
            $value = $this->findScalarByKey($payload, $key);
            if (is_numeric($value) && (float) $value > 0) {
                return (float) $value;
            }
        }

        $bid = $this->findScalarByKey($payload, 'bid');
        $ask = $this->findScalarByKey($payload, 'ask');
        if (is_numeric($bid) && is_numeric($ask) && (float) $bid > 0 && (float) $ask > 0) {
            return ((float) $bid + (float) $ask) / 2;
        }

        return null;
    }

    /**
     * Historical precious-metal reference prices. The provider exposes price points,
     * not OHLC candles, so this service preserves them as date + price only.
     */
    public function metalHistory(string $providerSymbol, string $interval = 'daily'): array
    {
        if (! $this->isAvailable()) {
            return [];
        }

        $interval = in_array($interval, ['daily', 'weekly', 'monthly'], true) ? $interval : 'daily';
        $payload = $this->request([
            'function' => 'GOLD_SILVER_HISTORY',
            'symbol' => strtoupper($providerSymbol),
            'interval' => $interval,
            'apikey' => $this->apiKey,
        ]);

        $candidate = $payload['data'] ?? $payload['history'] ?? null;
        if (! is_array($candidate)) {
            foreach ($payload as $value) {
                if (is_array($value) && array_is_list($value)) {
                    $candidate = $value;
                    break;
                }
            }
        }

        if (! is_array($candidate)) {
            return [];
        }

        return collect($candidate)
            ->map(function ($row) {
                if (! is_array($row)) return null;

                $date = $row['date'] ?? $row['timestamp'] ?? $row['time'] ?? null;
                $value = $row['value'] ?? $row['price'] ?? $row['close'] ?? null;
                if (! $date || ! is_numeric($value) || (float) $value <= 0) return null;

                return ['date' => (string) $date, 'price' => (float) $value];
            })
            ->filter()
            ->sortBy('date')
            ->values()
            ->all();
    }

    private function request(array $query): array
    {
        try {
            $response = Http::timeout(20)
                ->retry(2, 750)
                ->get($this->baseUrl, $query);

            if (! $response->successful()) {
                throw new RuntimeException('Alpha Vantage commodity request failed with HTTP '.$response->status().'.');
            }

            $payload = $response->json();
            if (! is_array($payload)) {
                throw new RuntimeException('Alpha Vantage returned a non-JSON commodity response.');
            }

            $message = $payload['Note'] ?? $payload['Information'] ?? $payload['Error Message'] ?? null;
            if ($message) {
                throw new RuntimeException((string) $message);
            }

            return $payload;
        } catch (\Throwable $e) {
            Log::warning('Commodity Alpha Vantage request failed', [
                'function' => $query['function'] ?? null,
                'symbol' => $query['symbol'] ?? null,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function findScalarByKey(array $payload, string $needle): mixed
    {
        foreach ($payload as $key => $value) {
            if (strtolower((string) $key) === strtolower($needle) && ! is_array($value)) {
                return $value;
            }

            if (is_array($value)) {
                $found = $this->findScalarByKey($value, $needle);
                if ($found !== null) return $found;
            }
        }

        return null;
    }
}
