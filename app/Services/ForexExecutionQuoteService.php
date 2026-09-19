<?php

namespace App\Services;

use App\Contracts\ForexExecutionQuoteProvider;
use App\Models\ForexPair;
use App\Models\MarketInstrument;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use RuntimeException;

final class ForexExecutionQuoteService
{
    public function __construct(
        private ForexExecutionQuoteProvider $provider,
        private MarketPriceRouter $prices,
        private ForexSessionService $sessions
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
        if (! $instrument->isForex()) {
            throw new InvalidArgumentException('Forex execution quotes require a Forex MarketInstrument.');
        }

        $side = strtolower(trim($side));
        if (! in_array($side, ['buy', 'sell'], true)) {
            throw new InvalidArgumentException('Forex execution quote side must be buy or sell.');
        }

        $marketplace = $this->prices->normalizeMarketplace($marketplace ?: $this->prices->activeMarketplace());

        if ($marketplace === 'controlled') {
            $price = $this->prices->price($instrument, 'controlled');
            if ($price <= 0) {
                throw new RuntimeException('Controlled Forex execution price is unavailable.');
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
            throw new RuntimeException('Forex pair is unavailable for execution.');
        }

        if (! $this->sessions->isMarketOpen()) {
            throw new RuntimeException('Forex market is closed. Live Forex execution is unavailable until the 24/5 market reopens.');
        }

        if (! $this->provider->isAvailable()) {
            throw new RuntimeException('Live Forex execution quote provider is not configured.');
        }

        $cacheKey = 'forex:execution-quote:'.strtoupper((string) $pair->symbol);
        $payload = Cache::get($cacheKey);

        if (! is_array($payload)) {
            $payload = $this->provider->quote($pair);
            Cache::put($cacheKey, $payload, now()->addSeconds(15));
        }

        $capturedAt = $payload['captured_at'] ?? null;
        if (! $capturedAt instanceof CarbonInterface) {
            throw new RuntimeException('Live Forex execution quote timestamp is unavailable.');
        }

        $ageSeconds = abs(now()->utc()->timestamp - $capturedAt->copy()->utc()->timestamp);
        $maxAge = max(30, (int) config('services.alpha_vantage.forex_execution_max_quote_age_seconds', 300));
        if ($ageSeconds > $maxAge) {
            Cache::forget($cacheKey);
            throw new RuntimeException('Live Forex execution quote is stale. Execution was blocked.');
        }

        $raw = $side === 'buy'
            ? (float) ($payload['ask'] ?? $payload['rate'] ?? 0)
            : (float) ($payload['bid'] ?? $payload['rate'] ?? 0);

        if ($raw <= 0) {
            throw new RuntimeException('Live Forex execution price is invalid.');
        }

        $precision = min(10, max(2, (int) ($instrument->price_precision ?? 5)));

        return array_merge($payload, [
            'price' => round($raw, $precision),
            'marketplace' => 'live',
            'age_seconds' => $ageSeconds,
        ]);
    }

    private function pairFor(MarketInstrument $instrument): ?ForexPair
    {
        if ($instrument->relationLoaded('canonicalForexPair') && $instrument->canonicalForexPair) {
            return $instrument->canonicalForexPair;
        }

        $pair = $instrument->canonicalForexPair()->first();
        if ($pair) {
            return $pair;
        }

        return $instrument->forex_pair_id
            ? ForexPair::query()->find($instrument->forex_pair_id)
            : null;
    }
}
