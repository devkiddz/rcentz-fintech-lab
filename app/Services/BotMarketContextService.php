<?php

namespace App\Services;

use App\Models\BotProduct;
use App\Models\CryptoCandle;
use App\Models\ForexCandle;
use App\Models\MarketInstrument;
use App\Models\StockCandle;
use App\Models\StockNews;
use App\Models\StockQuote;
use Illuminate\Support\Collection;

final class BotMarketContextService
{
    public function __construct(
        private MarketPriceRouter $prices,
        private MarketSessionService $stockSessions,
        private ForexSessionService $forexSessions
    ) {}

    public function forProduct(BotProduct $product, int $limit = 48): array
    {
        $product->loadMissing([
            'marketInstrument.canonicalStock',
            'marketInstrument.canonicalForexPair',
            'marketInstrument.canonicalCryptoPair',
            'stock',
        ]);

        $instrument = $product->marketInstrument ?? $product->stock?->marketInstrument;
        if (! $instrument) {
            return $this->emptyContext();
        }

        return $this->forInstrument($instrument, $limit);
    }

    public function forInstrument(MarketInstrument $instrument, int $limit = 48): array
    {
        $instrument->loadMissing(['canonicalStock', 'canonicalForexPair', 'canonicalCryptoPair', 'stock', 'forexPair']);

        $marketplace = $this->prices->activeMarketplace();
        $assetClass = strtolower((string) $instrument->asset_class);
        $precision = min(10, max(2, (int) ($instrument->price_precision ?? 2)));
        $symbol = $instrument->display_symbol ?: $instrument->symbol;

        $fallback = $this->fallbackSnapshot($instrument);
        $current = $this->safePrice($instrument, $marketplace) ?? $fallback['current'];
        $previous = $fallback['previous_close'];
        $change = $previous > 0 ? $current - $previous : 0.0;
        $changePercent = $previous > 0 ? ($change / $previous) * 100 : 0.0;
        $quotes = $limit > 0 ? $this->series($instrument, $limit) : [];

        return [
            'instrument' => $instrument,
            'asset_class' => $assetClass,
            'asset_class_label' => strtoupper($assetClass),
            'symbol' => $symbol,
            'name' => $instrument->name,
            'quote_asset' => strtoupper((string) ($instrument->quote_asset ?: ($instrument->isStock() ? 'USD' : ''))),
            'price_precision' => $precision,
            'marketplace' => $marketplace,
            'market_status' => $this->marketStatus($instrument, $marketplace),
            'current' => $current,
            'previous_close' => $previous,
            'change_amount' => $change,
            'change_percentage' => $changePercent,
            'open' => $fallback['open'],
            'high' => $fallback['high'],
            'low' => $fallback['low'],
            'current_display' => $this->displayPrice($instrument, $current),
            'previous_close_display' => $this->displayPrice($instrument, $previous),
            'open_display' => $this->displayPrice($instrument, $fallback['open']),
            'high_display' => $this->displayPrice($instrument, $fallback['high']),
            'low_display' => $this->displayPrice($instrument, $fallback['low']),
            'quotes' => $quotes,
            'news' => $this->news($instrument),
        ];
    }

    public function displayPrice(?MarketInstrument $instrument, float $price): string
    {
        if (! $instrument || $price <= 0) {
            return '—';
        }

        if ($instrument->isStock()) {
            return format_currency($price);
        }

        $precision = min(10, max(2, (int) ($instrument->price_precision ?? 5)));
        $quote = strtoupper((string) ($instrument->quote_asset ?: ''));
        $formatted = number_format($price, $precision, '.', ',');

        return $quote !== '' ? $formatted.' '.$quote : $formatted;
    }

    private function fallbackSnapshot(MarketInstrument $instrument): array
    {
        if ($instrument->isStock()) {
            $stock = $instrument->canonicalStock ?? $instrument->stock;
            return [
                'current' => (float) ($stock?->current_price ?? 0),
                'previous_close' => (float) ($stock?->previous_close ?? 0),
                'open' => (float) ($stock?->open ?? 0),
                'high' => (float) ($stock?->high ?? 0),
                'low' => (float) ($stock?->low ?? 0),
            ];
        }

        if ($instrument->isForex()) {
            $pair = $instrument->canonicalForexPair ?? $instrument->forexPair;
            $latest = $pair
                ? ForexCandle::query()->where('forex_pair_id', $pair->id)->latest('timestamp')->first()
                : null;

            return [
                'current' => (float) ($pair?->current_rate ?? $latest?->close ?? 0),
                'previous_close' => (float) ($pair?->previous_close ?? 0),
                'open' => (float) ($latest?->open ?? 0),
                'high' => (float) ($latest?->high ?? 0),
                'low' => (float) ($latest?->low ?? 0),
            ];
        }

        $pair = $instrument->canonicalCryptoPair;
        $latest = $pair
            ? CryptoCandle::query()->where('crypto_pair_id', $pair->id)->latest('timestamp')->first()
            : null;

        return [
            'current' => (float) ($pair?->current_rate ?? $latest?->close ?? 0),
            'previous_close' => (float) ($pair?->previous_close ?? 0),
            'open' => (float) ($latest?->open ?? 0),
            'high' => (float) ($latest?->high ?? 0),
            'low' => (float) ($latest?->low ?? 0),
        ];
    }

    private function series(MarketInstrument $instrument, int $limit): array
    {
        if ($instrument->isStock()) {
            $stock = $instrument->canonicalStock ?? $instrument->stock;
            if (! $stock) return [];

            $candles = StockCandle::query()
                ->where('symbol', $stock->symbol)
                ->where('interval', '15m')
                ->orderByDesc('started_at')
                ->limit($limit)
                ->get(['open','high','low','close','started_at'])
                ->sortBy('started_at')
                ->values();

            if ($candles->isNotEmpty()) {
                return $candles->map(fn ($candle) => [
                    'open' => (float) $candle->open,
                    'high' => (float) $candle->high,
                    'low' => (float) $candle->low,
                    'close' => (float) $candle->close,
                    'price' => (float) $candle->close,
                    'time' => optional($candle->started_at)->toIso8601String(),
                    'label' => optional($candle->started_at)?->copy()->setTimezone(MarketSessionService::TIMEZONE)->format('H:i'),
                ])->all();
            }

            $recentQuotes = StockQuote::query()
                ->where('symbol', $stock->symbol)
                ->where('fetched_at', '>=', now()->subDay())
                ->orderByDesc('fetched_at')
                ->limit($limit)
                ->get(['current_price','fetched_at'])
                ->sortBy('fetched_at')
                ->values();

            return $recentQuotes->count() < 2 ? [] : $recentQuotes->map(fn ($quote) => [
                'price' => (float) $quote->current_price,
                'time' => optional($quote->fetched_at)->toIso8601String(),
                'label' => optional($quote->fetched_at)?->copy()->setTimezone(MarketSessionService::TIMEZONE)->format('H:i'),
            ])->all();
        }

        if ($instrument->isForex()) {
            $pair = $instrument->canonicalForexPair ?? $instrument->forexPair;
            if (! $pair) return [];

            return ForexCandle::query()
                ->where('forex_pair_id', $pair->id)
                ->orderByDesc('timestamp')
                ->limit($limit)
                ->get(['open','high','low','close','timestamp'])
                ->sortBy('timestamp')
                ->values()
                ->map(fn ($candle) => [
                    'open' => (float) $candle->open,
                    'high' => (float) $candle->high,
                    'low' => (float) $candle->low,
                    'close' => (float) $candle->close,
                    'price' => (float) $candle->close,
                    'time' => optional($candle->timestamp)->toIso8601String(),
                    'label' => optional($candle->timestamp)?->format('M d'),
                ])->all();
        }

        $pair = $instrument->canonicalCryptoPair;
        if (! $pair) return [];

        return CryptoCandle::query()
            ->where('crypto_pair_id', $pair->id)
            ->orderByDesc('timestamp')
            ->limit($limit)
            ->get(['open','high','low','close','timestamp'])
            ->sortBy('timestamp')
            ->values()
            ->map(fn ($candle) => [
                'open' => (float) $candle->open,
                'high' => (float) $candle->high,
                'low' => (float) $candle->low,
                'close' => (float) $candle->close,
                'price' => (float) $candle->close,
                'time' => optional($candle->timestamp)->toIso8601String(),
                'label' => optional($candle->timestamp)?->format('M d'),
            ])->all();
    }

    private function marketStatus(MarketInstrument $instrument, string $marketplace): string
    {
        if ($marketplace === 'controlled') {
            return 'controlled';
        }

        if ($instrument->isCrypto()) {
            return '24_7';
        }

        if ($instrument->isForex()) {
            return $this->forexSessions->isMarketOpen() ? 'open' : 'closed';
        }

        return $this->stockSessions->status();
    }

    private function news(MarketInstrument $instrument): Collection
    {
        if (! $instrument->isStock()) {
            return collect();
        }

        $stock = $instrument->canonicalStock ?? $instrument->stock;
        if (! $stock) {
            return collect();
        }

        return StockNews::query()
            ->where('symbol', $stock->symbol)
            ->latest('published_at')
            ->limit(4)
            ->get();
    }

    private function safePrice(MarketInstrument $instrument, string $marketplace): ?float
    {
        try {
            $price = (float) $this->prices->price($instrument, $marketplace);
            return $price > 0 ? $price : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function emptyContext(): array
    {
        return [
            'instrument' => null,
            'asset_class' => null,
            'asset_class_label' => '—',
            'symbol' => '—',
            'name' => 'Unavailable instrument',
            'quote_asset' => '',
            'price_precision' => 2,
            'marketplace' => $this->prices->activeMarketplace(),
            'market_status' => 'unavailable',
            'current' => 0.0,
            'previous_close' => 0.0,
            'change_amount' => 0.0,
            'change_percentage' => 0.0,
            'open' => 0.0,
            'high' => 0.0,
            'low' => 0.0,
            'current_display' => '—',
            'previous_close_display' => '—',
            'open_display' => '—',
            'high_display' => '—',
            'low_display' => '—',
            'quotes' => [],
            'news' => collect(),
        ];
    }
}
