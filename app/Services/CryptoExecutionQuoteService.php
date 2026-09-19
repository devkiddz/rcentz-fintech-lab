<?php

namespace App\Services;

use App\Contracts\CryptoExecutionQuoteProvider;
use App\Models\CryptoPair;
use App\Models\MarketInstrument;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use RuntimeException;

final class CryptoExecutionQuoteService
{
    public function __construct(
        private CryptoExecutionQuoteProvider $provider,
        private MarketPriceRouter $prices
    ) {}

    public function providerAvailable(): bool
    {
        return $this->provider->isAvailable();
    }

    public function quote(
        MarketInstrument $instrument,
        string $side,
        ?string $marketplace = null
    ): array {
        if (! $instrument->isCrypto()) {
            throw new InvalidArgumentException('Crypto execution quotes require a Crypto MarketInstrument.');
        }

        $side = strtolower(trim($side));
        if (! in_array($side, ['buy', 'sell'], true)) {
            throw new InvalidArgumentException('Crypto execution quote side must be buy or sell.');
        }

        $marketplace = $this->prices->normalizeMarketplace($marketplace ?: $this->prices->activeMarketplace());

        if ($marketplace === 'controlled') {
            $price = $this->prices->price($instrument, 'controlled');
            if ($price <= 0) {
                throw new RuntimeException('Controlled Crypto execution price is unavailable.');
            }

            return [
                'price' => $price,
                'rate' => $price,
                'bid' => $price,
                'ask' => $price,
                'captured_at' => now()->utc(),
                'source' => 'controlled_market',
                'marketplace' => 'controlled',
                'age_seconds' => 0,
            ];
        }

        $pair = $this->pairFor($instrument);
        if (! $pair || ! $pair->is_active) {
            throw new RuntimeException('Crypto pair is unavailable for execution.');
        }

        if (! $pair->external_feed_enabled) {
            throw new RuntimeException('External Crypto execution feed is disabled for this instrument.');
        }

        if (! $this->provider->isAvailable()) {
            throw new RuntimeException('Live Crypto execution quote provider is not configured.');
        }

        $cacheKey = 'crypto:execution-quote:'.strtoupper((string) $pair->symbol);
        $payload = Cache::get($cacheKey);

        if (! is_array($payload)) {
            $payload = $this->provider->quote($pair);
            Cache::put($cacheKey, $payload, now()->addSeconds(15));
        }

        $capturedAt = $payload['captured_at'] ?? null;
        if (! $capturedAt instanceof CarbonInterface) {
            throw new RuntimeException('Live Crypto execution quote timestamp is unavailable.');
        }

        $ageSeconds = abs(now()->utc()->timestamp - $capturedAt->copy()->utc()->timestamp);
        $maxAge = max(30, (int) config('services.alpha_vantage.crypto_execution_max_quote_age_seconds', 300));
        if ($ageSeconds > $maxAge) {
            Cache::forget($cacheKey);
            throw new RuntimeException('Live Crypto execution quote is stale. Execution was blocked.');
        }

        $raw = $side === 'buy'
            ? (float) ($payload['ask'] ?? $payload['rate'] ?? 0)
            : (float) ($payload['bid'] ?? $payload['rate'] ?? 0);

        if ($raw <= 0) {
            throw new RuntimeException('Live Crypto execution price is invalid.');
        }

        $precision = min(12, max(2, (int) ($instrument->price_precision ?? 2)));

        return array_merge($payload, [
            'price' => round($raw, $precision),
            'marketplace' => 'live',
            'age_seconds' => $ageSeconds,
        ]);
    }

    private function pairFor(MarketInstrument $instrument): ?CryptoPair
    {
        if ($instrument->relationLoaded('canonicalCryptoPair') && $instrument->canonicalCryptoPair) {
            return $instrument->canonicalCryptoPair;
        }

        return $instrument->canonicalCryptoPair()->first();
    }
}
