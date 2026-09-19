<?php

namespace App\Services\Execution;

use App\Contracts\CryptoExecutionQuoteProvider;
use App\Models\CryptoPair;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class AlphaVantageCryptoExecutionQuoteProvider implements CryptoExecutionQuoteProvider
{
    private string $baseUrl = 'https://www.alphavantage.co/query';
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.alpha_vantage.api_key');
    }

    public function key(): string
    {
        return 'alpha_vantage_realtime_crypto';
    }

    public function isAvailable(): bool
    {
        return filled($this->apiKey);
    }

    public function quote(CryptoPair $pair): array
    {
        if (! $this->isAvailable()) {
            throw new RuntimeException('Live Crypto execution quote provider is not configured.');
        }

        $response = Http::timeout(12)
            ->retry(1, 500)
            ->get($this->baseUrl, [
                'function' => 'CURRENCY_EXCHANGE_RATE',
                'from_currency' => strtoupper($pair->base_asset),
                'to_currency' => strtoupper($pair->quote_asset),
                'apikey' => $this->apiKey,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Live Crypto execution quote request failed.');
        }

        $data = $response->json();
        $message = $data['Note'] ?? $data['Information'] ?? $data['Error Message'] ?? null;
        if ($message) {
            throw new RuntimeException('Live Crypto execution quote provider is temporarily unavailable.');
        }

        $quote = $data['Realtime Currency Exchange Rate'] ?? null;
        if (! is_array($quote)) {
            throw new RuntimeException('Live Crypto execution quote payload is unavailable.');
        }

        $rate = (float) ($quote['5. Exchange Rate'] ?? 0);
        $bid = (float) ($quote['8. Bid Price'] ?? 0);
        $ask = (float) ($quote['9. Ask Price'] ?? 0);
        $lastRefreshed = trim((string) ($quote['6. Last Refreshed'] ?? ''));
        $timezone = trim((string) ($quote['7. Time Zone'] ?? 'UTC')) ?: 'UTC';

        if ($rate <= 0 || $lastRefreshed === '') {
            throw new RuntimeException('Live Crypto execution quote is incomplete.');
        }

        try {
            $capturedAt = CarbonImmutable::parse($lastRefreshed, $timezone)->utc();
        } catch (\Throwable) {
            throw new RuntimeException('Live Crypto execution quote timestamp is invalid.');
        }

        return [
            'rate' => $rate,
            'bid' => $bid > 0 ? $bid : $rate,
            'ask' => $ask > 0 ? $ask : $rate,
            'captured_at' => $capturedAt,
            'source' => $this->key(),
            'provider_timezone' => $timezone,
        ];
    }
}
