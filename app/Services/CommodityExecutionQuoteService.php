<?php
namespace App\Services;

use App\Models\MarketInstrument;
use App\Models\CommodityPricePoint;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class CommodityExecutionQuoteService
{
    public function __construct(private MarketPriceRouter $prices, private CommodityAlphaVantageService $provider) {}

    public function assertInstrument(MarketInstrument $instrument): void
    {
        $metal = $instrument->canonicalCommodityInstrument()->first();
        if (!$instrument->isCommodity() || !$instrument->is_active || !$metal?->is_active
            || !in_array(strtoupper($instrument->base_asset), ['XAU','XAG'], true)
            || strtoupper($instrument->quote_asset) !== 'USD'
            || !in_array(strtolower((string)$metal->unit), ['troy ounce','troy ounces','troy_ounce','troy_ounces','ounce','oz'], true)
            || !in_array(strtoupper($metal->provider_symbol), ['GOLD','XAU','SILVER','XAG'], true)) {
            throw new RuntimeException('Commodity trading requires an active USD Gold/Silver instrument priced per troy ounce.');
        }
        $expected = strtoupper($instrument->base_asset) === 'XAU' ? ['GOLD','XAU'] : ['SILVER','XAG'];
        if (!in_array(strtoupper($metal->provider_symbol), $expected, true)) {
            throw new RuntimeException('Commodity provider symbol does not match the instrument.');
        }
    }

    public function quote(MarketInstrument $instrument, string $side, string $marketplace): float
    {
        $this->assertInstrument($instrument);
        if (!in_array($side,['buy','sell'],true)) { throw new RuntimeException('Invalid commodity order side.'); }
        $marketplace = $this->prices->normalizeMarketplace($marketplace);
        if ($marketplace === 'controlled') { return $this->positive($this->prices->price($instrument, $marketplace)); }
        $ny = now()->setTimezone('America/New_York');
        if (!app(ForexSessionService::class)->isMarketOpen() || $ny->hour === 17) {
            throw new RuntimeException('Live precious-metal market is closed or in its daily break.');
        }
        $metal = $instrument->canonicalCommodityInstrument()->firstOrFail();
        if (!$metal->external_feed_enabled) { throw new RuntimeException('Live commodity feed is disabled.'); }
        $shared = app(SharedLiveFeed::class);
        if ($shared->applies($instrument)) return $shared->execution($instrument)['price'];
        $key = 'commodity:execution:'.$instrument->id;
        $payload = Cache::remember($key, 60, fn () => $this->provider->metalExecutionPayload($metal->provider_symbol));
        try { $price = $this->validatePayload($payload, strtoupper($instrument->base_asset).'USD'); }
        catch (\Throwable $e) { Cache::forget($key); throw $e; }
        // A successful spot quote updates the persisted display mark, never a controlled price.
        $at=CarbonImmutable::parse($payload['timestamp'], 'UTC');
        DB::transaction(function () use ($metal, $price, $at) {
            $metal->update(['current_price'=>$price, 'last_updated'=>$at]);
            CommodityPricePoint::query()->updateOrCreate(
                ['commodity_instrument_id'=>$metal->id,'interval'=>'spot','timestamp'=>$at],
                ['price'=>$price,'source'=>'alpha_vantage_spot']
            );
        });
        return $price;
    }

    public function validatePayload(array $payload, ?string $expectedNominal = null): float
    {
        foreach (['Note','Information','Error Message'] as $key) {
            if (!empty($payload[$key])) { throw new RuntimeException('Commodity provider returned no executable quote.'); }
        }
        $timestamp = $payload['timestamp'] ?? null;
        if (!is_string($timestamp) || !preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}/', $timestamp)) { throw new RuntimeException('Live commodity quote timestamp is missing.'); }
        $at = CarbonImmutable::parse($timestamp, 'UTC');
        $age = now()->utc()->timestamp - $at->utc()->timestamp;
        if ($age < -30 || $age > 300) { throw new RuntimeException('Live commodity quote is stale or future-dated.'); }
        // This endpoint reports the metal/currency pair as nominal, not unit/currency.
        // Nominal-only quotes are accepted only in a matching, already-validated
        // USD/troy-ounce instrument context; explicit conflicting metadata still fails.
        $nominal = $payload['nominal'] ?? null;
        $nominalMatches = false;
        if ($nominal !== null) {
            if (!is_string($nominal) || !in_array(strtoupper(trim($nominal)), ['XAUUSD','XAGUSD'], true)
                || ($expectedNominal !== null && strtoupper(trim($nominal)) !== $expectedNominal)) {
                throw new RuntimeException('Live commodity quote does not match the requested metal/USD pair.');
            }
            $nominalMatches = $expectedNominal !== null && strtoupper(trim($nominal)) === $expectedNominal;
        }
        $unit = $payload['unit'] ?? null;
        if ($unit !== null) {
            $normalizedUnit = strtolower(trim((string)$unit));
            if (!in_array($normalizedUnit, ['usd per troy ounce','usd/troy ounce','usd per ounce','usd/oz',
                'troy ounce','troy ounces','troy_ounce','troy_ounces','ounce','oz'], true)) {
                throw new RuntimeException('Live commodity quote unit is unsupported.');
            }
        } elseif (!$nominalMatches) {
            throw new RuntimeException('Live commodity quote unit is unsupported.');
        }
        if (array_key_exists('currency', $payload) && strtoupper((string)$payload['currency']) !== 'USD') {
            throw new RuntimeException('Live commodity quote must be in USD per troy ounce.');
        }
        if (!$nominalMatches && !str_contains(strtoupper((string)$unit), 'USD')
            && strtoupper((string)($payload['currency'] ?? '')) !== 'USD') {
            throw new RuntimeException('Live commodity quote must be in USD per troy ounce.');
        }
        if (!is_numeric($payload['price'] ?? null)) { throw new RuntimeException('Live commodity quote price must be numeric.'); }
        return $this->positive((float)$payload['price']);
    }

    private function positive(float $price): float
    {
        if (!is_finite($price) || $price <= 0) { throw new RuntimeException('Commodity quote is invalid.'); }
        return $price;
    }
}
