<?php

namespace App\Services;

use App\Contracts\StockApiInterface;
use App\Models\CommodityInstrument;
use App\Models\CryptoPair;
use App\Models\ForexPair;
use App\Models\MarketInstrument;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PublicBaseReferenceRegistryService
{
    public function __construct(
        private StockApiInterface $stockApi,
        private StockHistoryBackfillService $stockHistory,
        private ForexMarketDataService $forexMarket,
        private CryptoMarketDataService $cryptoMarket,
        private CommodityMarketDataService $commodityMarket
    ) {}

    public function register(string $assetClass, string $input): array
    {
        $assetClass = strtolower(trim($assetClass));

        return match ($assetClass) {
            MarketInstrument::ASSET_STOCK => $this->registerStock($input),
            MarketInstrument::ASSET_FOREX => $this->registerForex($input),
            MarketInstrument::ASSET_CRYPTO => $this->registerCrypto($input),
            MarketInstrument::ASSET_COMMODITY => $this->registerCommodity($input),
            default => throw ValidationException::withMessages([
                'public_asset_class' => 'Unsupported public asset class.',
            ]),
        };
    }

    private function registerStock(string $input): array
    {
        $symbol = strtoupper(trim($input));

        if (! preg_match('/^[A-Z0-9.\-]{1,10}$/', $symbol)) {
            throw ValidationException::withMessages([
                'public_symbol' => 'Enter a valid public stock symbol such as AAPL, NVDA or MSFT.',
            ]);
        }

        if ($existing = $this->existing(MarketInstrument::ASSET_STOCK, $symbol)) {
            return ['instrument' => $existing, 'created' => false];
        }

        $quote = $this->stockApi->getQuote($symbol);
        $price = (float) ($quote['c'] ?? 0);

        if ($price <= 0) {
            throw ValidationException::withMessages([
                'public_symbol' => $symbol.' was not verified by the live stock feed.',
            ]);
        }

        $profile = $this->stockApi->getCompanyProfile($symbol) ?: [];
        $name = trim((string) ($profile['name'] ?? $profile['companyName'] ?? $symbol));

        $instrument = DB::transaction(function () use ($symbol, $name, $price, $quote, $profile) {
            $previous = (float) ($quote['pc'] ?? $price);
            $high = (float) ($quote['h'] ?? $price);
            $low = (float) ($quote['l'] ?? $price);
            $open = (float) ($quote['o'] ?? $price);

            $stock = Stock::query()->firstOrNew(['symbol' => $symbol]);
            $stock->fill([
                'company_name' => $name,
                'sector' => $profile['finnhubIndustry'] ?? $stock->sector,
                'industry' => $profile['finnhubIndustry'] ?? $stock->industry,
                'logo_url' => $profile['logo'] ?? $stock->logo_url,
                'current_price' => $price,
                'previous_close' => $previous > 0 ? $previous : $price,
                'change_amount' => $price - ($previous > 0 ? $previous : $price),
                'change_percentage' => $previous > 0 ? (($price - $previous) / $previous) * 100 : 0,
                'volume' => $quote['v'] ?? $stock->volume,
                'high' => $high > 0 ? $high : $price,
                'low' => $low > 0 ? $low : $price,
                'open' => $open > 0 ? $open : $price,
                'market_cap' => $profile['marketCapitalization'] ?? $stock->market_cap,
                'is_active' => true,
                'is_featured' => false,
                'external_feed_enabled' => true,
                'last_updated' => now(),
            ]);
            $stock->save();

            $instrument = MarketInstrument::query()->create([
                'symbol' => $symbol,
                'display_symbol' => $symbol,
                'name' => $name,
                'asset_class' => MarketInstrument::ASSET_STOCK,
                'market' => 'us_equity',
                'base_asset' => $symbol,
                'quote_asset' => 'USD',
                'price_precision' => 2,
                'pip_size' => null,
                'stock_id' => $stock->id,
                'forex_pair_id' => null,
                'is_active' => true,
                'is_featured' => false,
                'metadata' => ['source' => 'public_base_reference_registry'],
            ]);

            $stock->update(['market_instrument_id' => $instrument->id]);

            return $instrument;
        });

        try {
            $stock = $instrument->canonicalStock ?: $instrument->stock;
            if ($stock) {
                $this->stockHistory->backfill($stock, false);
            }
        } catch (\Throwable) {
            // Current live quote already established the public reference.
        }

        return ['instrument' => $instrument->fresh(), 'created' => true];
    }

    private function registerForex(string $input): array
    {
        [$base, $quote] = $this->parseForexPair($input);
        $symbol = $base.$quote;

        if ($existing = $this->existing(MarketInstrument::ASSET_FOREX, $symbol)) {
            return ['instrument' => $existing, 'created' => false];
        }

        $instrument = DB::transaction(function () use ($base, $quote, $symbol) {
            $instrument = MarketInstrument::query()->create([
                'symbol' => $symbol,
                'display_symbol' => $base.'/'.$quote,
                'name' => $base.' / '.$quote,
                'asset_class' => MarketInstrument::ASSET_FOREX,
                'market' => 'global_fx',
                'base_asset' => $base,
                'quote_asset' => $quote,
                'price_precision' => 5,
                'pip_size' => 0.0001,
                'stock_id' => null,
                'forex_pair_id' => null,
                'is_active' => true,
                'is_featured' => false,
                'metadata' => ['source' => 'public_base_reference_registry'],
            ]);

            $pair = ForexPair::query()->create([
                'market_instrument_id' => $instrument->id,
                'symbol' => $symbol,
                'display_symbol' => $base.'/'.$quote,
                'name' => $base.' / '.$quote,
                'base_currency' => $base,
                'quote_currency' => $quote,
                'pip_size' => 0.0001,
                'price_precision' => 5,
                'current_rate' => null,
                'previous_close' => null,
                'is_active' => true,
                'is_featured' => false,
                'external_feed_enabled' => true,
                'preferred_sessions' => [],
                'last_updated' => null,
            ]);

            $instrument->update(['forex_pair_id' => $pair->id]);

            $this->forexMarket->refreshDaily($pair, 'compact');

            return $instrument;
        });

        return ['instrument' => $instrument->fresh(), 'created' => true];
    }

    private function registerCrypto(string $input): array
    {
        [$base, $quote] = $this->parseCryptoPair($input);
        $symbol = $base.$quote;

        if ($existing = $this->existing(MarketInstrument::ASSET_CRYPTO, $symbol)) {
            return ['instrument' => $existing, 'created' => false];
        }

        $instrument = DB::transaction(function () use ($base, $quote, $symbol) {
            $precision = 8;
            $tick = 0.00000001;

            $instrument = MarketInstrument::query()->create([
                'symbol' => $symbol,
                'display_symbol' => $base.'/'.$quote,
                'name' => $base.' / '.$quote,
                'asset_class' => MarketInstrument::ASSET_CRYPTO,
                'market' => 'global_crypto',
                'base_asset' => $base,
                'quote_asset' => $quote,
                'price_precision' => $precision,
                'pip_size' => $tick,
                'stock_id' => null,
                'forex_pair_id' => null,
                'is_active' => true,
                'is_featured' => false,
                'metadata' => ['source' => 'public_base_reference_registry'],
            ]);

            $pair = CryptoPair::query()->create([
                'market_instrument_id' => $instrument->id,
                'symbol' => $symbol,
                'display_symbol' => $base.'/'.$quote,
                'name' => $base.' / '.$quote,
                'base_asset' => $base,
                'quote_asset' => $quote,
                'current_rate' => null,
                'previous_close' => null,
                'price_precision' => $precision,
                'minimum_tick' => $tick,
                'is_active' => true,
                'is_featured' => false,
                'external_feed_enabled' => true,
                'last_updated' => null,
                'metadata' => ['source' => 'public_base_reference_registry'],
            ]);

            $this->cryptoMarket->refreshDaily($pair);

            return $instrument;
        });

        return ['instrument' => $instrument->fresh(), 'created' => true];
    }

    private function registerCommodity(string $input): array
    {
        $normalized = strtoupper(preg_replace('/[^A-Z]/i', '', trim($input)));

        if (in_array($normalized, ['GOLD', 'XAU', 'XAUUSD'], true)) {
            $code = 'XAU';
            $name = 'Gold Spot / US Dollar';
        } elseif (in_array($normalized, ['SILVER', 'XAG', 'XAGUSD'], true)) {
            $code = 'XAG';
            $name = 'Silver Spot / US Dollar';
        } else {
            throw ValidationException::withMessages([
                'public_symbol' => 'The current live commodity adapter supports XAU/USD (Gold) and XAG/USD (Silver).',
            ]);
        }

        $symbol = $code.'USD';

        if ($existing = $this->existing(MarketInstrument::ASSET_COMMODITY, $symbol)) {
            return ['instrument' => $existing, 'created' => false];
        }

        $instrument = DB::transaction(function () use ($code, $name, $symbol) {
            $instrument = MarketInstrument::query()->create([
                'symbol' => $symbol,
                'display_symbol' => $code.'/USD',
                'name' => $name,
                'asset_class' => MarketInstrument::ASSET_COMMODITY,
                'market' => 'global_spot_metals',
                'base_asset' => $code,
                'quote_asset' => 'USD',
                'price_precision' => 2,
                'pip_size' => 0.01,
                'stock_id' => null,
                'forex_pair_id' => null,
                'is_active' => true,
                'is_featured' => false,
                'metadata' => ['source' => 'public_base_reference_registry'],
            ]);

            $commodity = CommodityInstrument::query()->create([
                'market_instrument_id' => $instrument->id,
                'symbol' => $symbol,
                'display_symbol' => $code.'/USD',
                'name' => $name,
                'commodity_code' => $code,
                'quote_asset' => 'USD',
                'provider_symbol' => $code,
                'provider_family' => 'precious_metal',
                'unit' => 'oz',
                'current_price' => null,
                'previous_close' => null,
                'price_precision' => 2,
                'minimum_tick' => 0.01,
                'is_active' => true,
                'is_featured' => false,
                'external_feed_enabled' => true,
                'last_updated' => null,
                'metadata' => ['source' => 'public_base_reference_registry'],
            ]);

            $this->commodityMarket->refresh($commodity);

            return $instrument;
        });

        return ['instrument' => $instrument->fresh(), 'created' => true];
    }

    private function existing(string $assetClass, string $symbol): ?MarketInstrument
    {
        return MarketInstrument::query()
            ->where('asset_class', $assetClass)
            ->where('symbol', strtoupper($symbol))
            ->first();
    }

    private function parseForexPair(string $input): array
    {
        $letters = strtoupper(preg_replace('/[^A-Z]/i', '', trim($input)));

        if (strlen($letters) !== 6) {
            throw ValidationException::withMessages([
                'public_symbol' => 'Enter a forex pair such as EUR/USD or EURUSD.',
            ]);
        }

        return [substr($letters, 0, 3), substr($letters, 3, 3)];
    }

    private function parseCryptoPair(string $input): array
    {
        $raw = strtoupper(trim($input));
        $parts = preg_split('/[\/\-_:\s]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);

        if (count($parts) === 2) {
            [$base, $quote] = $parts;
        } else {
            $compact = preg_replace('/[^A-Z0-9]/', '', $raw);
            $quote = null;
            $base = null;

            foreach (['USDT', 'USD', 'EUR', 'GBP', 'BTC', 'ETH'] as $candidate) {
                if (str_ends_with($compact, $candidate) && strlen($compact) > strlen($candidate)) {
                    $quote = $candidate;
                    $base = substr($compact, 0, -strlen($candidate));
                    break;
                }
            }
        }

        if (! $base || ! $quote || ! preg_match('/^[A-Z0-9]{2,12}$/', $base) || ! preg_match('/^[A-Z0-9]{2,6}$/', $quote)) {
            throw ValidationException::withMessages([
                'public_symbol' => 'Enter a crypto pair such as BTC/USD, ETH/USD or BTCUSD.',
            ]);
        }

        return [$base, $quote];
    }
}
