<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Stock;
use App\Models\BotProduct;
use App\Models\CryptoPair;
use App\Models\CopyStrategy;
use App\Models\ForexPair;
use App\Models\MarketInstrument;
use App\Models\PrivateInvestmentInstrument;
use App\Models\Signal;
use App\Services\BotMarketContextService;
use App\Services\CommodityMarketDataService;
use App\Services\CryptoMarketDataService;
use App\Services\MarketSessionService;
use App\Services\StockAnalysisService;
use Illuminate\Http\Request;

class FrontendController extends Controller
{
    public function index(
        BotMarketContextService $markets,
        StockAnalysisService $stockAnalysis,
        CryptoMarketDataService $cryptoMarketData,
        CommodityMarketDataService $commodityMarketData,
        MarketSessionService $stockSessions
    )
    {
        $featuredStocks = Stock::active()
            ->with('marketInstrument')
            ->where('company_name', 'not like', '%ACCEPTANCE%')
            ->orderByDesc('is_featured')
            ->orderByDesc('volume')
            ->limit(5)
            ->get();

        $forexPairs = ForexPair::active()
            ->with('marketInstrument')
            ->where('name', 'not like', '%ACCEPTANCE%')
            ->orderByDesc('is_featured')
            ->orderBy('symbol')
            ->limit(5)
            ->get();

        $cryptoPairs = CryptoPair::active()
            ->with('marketInstrument')
            ->where('name', 'not like', '%ACCEPTANCE%')
            ->orderByDesc('is_featured')
            ->orderBy('symbol')
            ->limit(5)
            ->get();

        $goldInstrument = MarketInstrument::query()
            ->active()
            ->with('canonicalCommodityInstrument')
            ->where('asset_class', MarketInstrument::ASSET_COMMODITY)
            ->where('symbol', 'XAUUSD')
            ->first();
        $goldCommodity = $goldInstrument?->canonicalCommodityInstrument;
        $goldContext = $goldCommodity ? $commodityMarketData->context($goldCommodity) : null;

        $seriesFor = function (?MarketInstrument $instrument, array $fallback = []) use ($markets): array {
            $quotes = $instrument
                ? collect($markets->priceSeries($instrument, 24))
                    ->pluck('price')
                    ->map(fn ($price) => (float) $price)
                    ->filter(fn ($price) => $price > 0)
                    ->values()
                : collect();

            if ($quotes->count() < 2) {
                $quotes = collect($fallback)
                    ->map(fn ($price) => (float) $price)
                    ->filter(fn ($price) => $price > 0)
                    ->values();
            }

            return $quotes->all();
        };

        $compactNumber = static function (float|int|null $value, string $suffix = ''): string {
            $value = (float) ($value ?? 0);
            if ($value <= 0) return '—';

            if ($value >= 1_000_000_000_000) return number_format($value / 1_000_000_000_000, 1).'T'.$suffix;
            if ($value >= 1_000_000_000) return number_format($value / 1_000_000_000, 1).'B'.$suffix;
            if ($value >= 1_000_000) return number_format($value / 1_000_000, 1).'M'.$suffix;
            if ($value >= 1_000) return number_format($value / 1_000, 1).'K'.$suffix;

            return number_format($value, 2).$suffix;
        };

        $rangePosition = static function (float $current, float $low, float $high): float {
            if ($current <= 0 || $high <= $low) return 50.0;
            return max(0.0, min(100.0, (($current - $low) / ($high - $low)) * 100));
        };

        $marketShowcase = collect();

        foreach ($featuredStocks as $stock) {
            $current = (float) $stock->current_price;
            $previous = (float) ($stock->previous_close ?? 0);
            $marketShowcase->push([
                'market_instrument_id' => $stock->market_instrument_id,
                'asset_class' => 'stock',
                'asset_label' => 'Stock',
                'symbol' => (string) $stock->symbol,
                'name' => (string) ($stock->company_name ?: 'Listed equity'),
                'price_display' => '$'.number_format($current, 2),
                'change' => (float) ($stock->change_percentage ?? 0),
                'icon' => 'chart-no-axes-combined',
                'quotes' => $seriesFor($stock->marketInstrument, [$previous, $current]),
            ]);
        }

        foreach ($forexPairs as $pair) {
            $current = (float) $pair->current_rate;
            $previous = (float) $pair->previous_close;
            $precision = max(2, min(8, (int) ($pair->price_precision ?? 5)));
            $marketShowcase->push([
                'market_instrument_id' => $pair->market_instrument_id,
                'asset_class' => 'forex',
                'asset_label' => 'Forex',
                'symbol' => (string) ($pair->display_symbol ?: $pair->symbol),
                'name' => (string) ($pair->name ?: 'Currency pair'),
                'price_display' => number_format($current, $precision),
                'change' => $previous > 0 ? (($current - $previous) / $previous) * 100 : 0.0,
                'icon' => 'arrow-left-right',
                'quotes' => $seriesFor($pair->marketInstrument, [$previous, $current]),
            ]);
        }

        if ($goldInstrument && $goldCommodity && (float) ($goldCommodity->current_price ?? 0) > 0) {
            $current = (float) $goldCommodity->current_price;
            $previous = (float) ($goldCommodity->previous_close ?? 0);
            $marketShowcase->push([
                'market_instrument_id' => $goldInstrument->id,
                'asset_class' => 'commodity',
                'asset_label' => 'Commodity',
                'symbol' => (string) $goldInstrument->display_symbol,
                'name' => (string) $goldInstrument->name,
                'price_display' => '$'.number_format($current, 2),
                'change' => $previous > 0 ? (($current - $previous) / $previous) * 100 : 0.0,
                'icon' => 'gem',
                'quotes' => $seriesFor($goldInstrument, [$previous, $current]),
            ]);
        }

        foreach ($cryptoPairs as $pair) {
            $current = (float) $pair->current_rate;
            $previous = (float) $pair->previous_close;
            $precision = max(2, min(8, (int) ($pair->price_precision ?? 2)));
            $quote = strtoupper((string) ($pair->quote_asset ?: 'USD'));
            $price = number_format($current, $precision);
            $marketShowcase->push([
                'market_instrument_id' => $pair->market_instrument_id,
                'asset_class' => 'crypto',
                'asset_label' => 'Crypto',
                'symbol' => (string) ($pair->display_symbol ?: $pair->symbol),
                'name' => (string) ($pair->name ?: 'Digital asset pair'),
                'price_display' => $quote === 'USD' ? '$'.$price : $price.' '.$quote,
                'change' => $previous > 0 ? (($current - $previous) / $previous) * 100 : 0.0,
                'icon' => 'bitcoin',
                'quotes' => $seriesFor($pair->marketInstrument, [$previous, $current]),
            ]);
        }

        $marketGroups = $marketShowcase
            ->groupBy('asset_class')
            ->map(fn ($items) => $items->values());

        $marketShowcase = collect(range(0, 4))
            ->flatMap(function (int $index) use ($marketGroups) {
                return collect(['stock', 'forex', 'commodity', 'crypto'])
                    ->map(fn ($assetClass) => $marketGroups->get($assetClass, collect())->get($index))
                    ->filter();
            })
            ->take(12)
            ->values();

        $marketTape = $marketShowcase->take(9)->values();

        // Homepage proof assets are fixed anchors, not movement-ranked picks.
        // All three resolve through the canonical MarketInstrument foundation.
        $heroMarkets = collect();

        $tesla = Stock::active()->with('marketInstrument')->where('symbol', 'TSLA')->first();
        if ($tesla?->marketInstrument) {
            $analysis = $stockAnalysis->forStockInMarketplace($tesla, 'live');
            $quotes = collect($analysis['series'] ?? [])
                ->take(-36)
                ->map(fn ($row) => is_array($row) ? (float) ($row['close'] ?? $row['price'] ?? 0) : (float) $row)
                ->filter(fn ($price) => $price > 0)
                ->values()
                ->all();

            $current = (float) ($analysis['current_price'] ?? $tesla->live_current_price);
            $previous = (float) ($tesla->previous_close ?? 0);
            $low = (float) ($tesla->low ?? 0);
            $high = (float) ($tesla->high ?? 0);
            $changeAmount = $previous > 0 ? $current - $previous : 0.0;
            $changePercentage = $previous > 0 ? ($changeAmount / $previous) * 100 : 0.0;

            $heroMarkets->push([
                'available' => true,
                'asset_class' => 'stock',
                'asset_label' => 'Stock',
                'symbol' => 'TSLA',
                'name' => 'Tesla Inc.',
                'price_display' => '$'.number_format($current, 2),
                'change_amount_display' => ($changeAmount >= 0 ? '+' : '-').'$'.number_format(abs($changeAmount), 2),
                'change' => $changePercentage,
                'previous_display' => $previous > 0 ? '$'.number_format($previous, 2) : '—',
                'open_display' => (float) $tesla->open > 0 ? '$'.number_format((float) $tesla->open, 2) : '—',
                'high_display' => $high > 0 ? '$'.number_format($high, 2) : '—',
                'low_display' => $low > 0 ? '$'.number_format($low, 2) : '—',
                'range_low_display' => $low > 0 ? '$'.number_format($low, 2) : '—',
                'range_high_display' => $high > 0 ? '$'.number_format($high, 2) : '—',
                'range_kind' => 'day',
                'range_position' => $rangePosition($current, $low, $high),
                'trend' => (string) ($analysis['trend'] ?? 'Neutral'),
                'momentum_percent' => (float) ($analysis['momentum_percent'] ?? 0),
                'support_display' => !empty($analysis['support']) ? '$'.number_format((float) $analysis['support'], 2) : '—',
                'resistance_display' => !empty($analysis['resistance']) ? '$'.number_format((float) $analysis['resistance'], 2) : '—',
                'activity_code' => 'volume',
                'activity_value' => $compactNumber((float) ($analysis['volume_current'] ?? $tesla->volume ?? 0)),
                'activity_meta_value' => isset($analysis['volume_vs_average']) && $analysis['volume_vs_average'] !== null
                    ? sprintf('%+.1f%%', (float) $analysis['volume_vs_average'])
                    : null,
                'status' => $stockSessions->status(),
                'updated_at' => optional($tesla->last_updated)?->toIso8601String(),
                'quotes' => $quotes,
            ]);
        } else {
            $heroMarkets->push(['available' => false, 'asset_class' => 'stock', 'asset_label' => 'Stock', 'symbol' => 'TSLA', 'name' => 'Tesla Inc.']);
        }

        if ($goldInstrument && $goldCommodity && (float) ($goldContext['current_price'] ?? 0) > 0) {
            $series = collect($goldContext['series'] ?? [])->values();
            $prices = $series->pluck('price')->map(fn ($value) => (float) $value)->filter(fn ($value) => $value > 0)->values();
            $window = $prices->take(-min(20, $prices->count()));
            $current = (float) $goldContext['current_price'];
            $previous = (float) ($goldContext['previous_close'] ?? 0);
            $rangeLow = (float) ($window->min() ?? 0);
            $rangeHigh = (float) ($window->max() ?? 0);
            $changeAmount = $previous > 0 ? $current - $previous : 0.0;
            $changePercentage = $previous > 0 ? ($changeAmount / $previous) * 100 : 0.0;

            $heroMarkets->push([
                'available' => true,
                'asset_class' => 'commodity',
                'asset_label' => 'Commodity',
                'symbol' => (string) $goldInstrument->display_symbol,
                'name' => (string) $goldInstrument->name,
                'price_display' => '$'.number_format($current, 2),
                'change_amount_display' => ($changeAmount >= 0 ? '+' : '-').'$'.number_format(abs($changeAmount), 2),
                'change' => $changePercentage,
                'previous_display' => $previous > 0 ? '$'.number_format($previous, 2) : '—',
                'open_display' => '—',
                'high_display' => '—',
                'low_display' => '—',
                'range_low_display' => $rangeLow > 0 ? '$'.number_format($rangeLow, 2) : '—',
                'range_high_display' => $rangeHigh > 0 ? '$'.number_format($rangeHigh, 2) : '—',
                'range_kind' => '20d',
                'range_position' => $rangePosition($current, $rangeLow, $rangeHigh),
                'trend' => (string) ($goldContext['trend'] ?? 'Neutral'),
                'momentum_percent' => (float) ($goldContext['momentum_percent'] ?? 0),
                'support_display' => !empty($goldContext['support']) ? '$'.number_format((float) $goldContext['support'], 2) : '—',
                'resistance_display' => !empty($goldContext['resistance']) ? '$'.number_format((float) $goldContext['resistance'], 2) : '—',
                'activity_code' => 'live_spot',
                'activity_value' => $goldCommodity->last_updated ? $goldCommodity->last_updated->diffForHumans() : '—',
                'activity_meta_value' => null,
                'status' => 'global_spot',
                'updated_at' => optional($goldCommodity->last_updated)?->toIso8601String(),
                'quotes' => $series->take(-36)->map(fn ($row) => (float) ($row['price'] ?? 0))->filter(fn ($price) => $price > 0)->values()->all(),
            ]);
        } else {
            $heroMarkets->push([
                'available' => false,
                'asset_class' => 'commodity',
                'asset_label' => 'Commodity',
                'symbol' => 'XAU/USD',
                'name' => 'Gold Spot / US Dollar',
            ]);
        }

        $bitcoin = CryptoPair::active()->with('marketInstrument')->where('symbol', 'BTCUSD')->first();
        if ($bitcoin?->marketInstrument) {
            $analysis = $cryptoMarketData->context($bitcoin);
            $dailySeries = collect($analysis['timeframes']['1d'] ?? [])->values();
            $latestDaily = $dailySeries->last();
            $quotes = $dailySeries
                ->take(-36)
                ->map(fn ($row) => is_array($row) ? (float) ($row['close'] ?? $row['price'] ?? 0) : (float) $row)
                ->filter(fn ($price) => $price > 0)
                ->values()
                ->all();

            $current = (float) ($analysis['current_price'] ?? $bitcoin->current_rate);
            $previous = (float) ($bitcoin->previous_close ?? 0);
            $low = (float) ($latestDaily['low'] ?? 0);
            $high = (float) ($latestDaily['high'] ?? 0);
            $changeAmount = $previous > 0 ? $current - $previous : 0.0;
            $changePercentage = $previous > 0 ? ($changeAmount / $previous) * 100 : 0.0;

            $heroMarkets->push([
                'available' => true,
                'asset_class' => 'crypto',
                'asset_label' => 'Crypto',
                'symbol' => 'BTC/USD',
                'name' => 'Bitcoin / US Dollar',
                'price_display' => '$'.number_format($current, 2),
                'change_amount_display' => ($changeAmount >= 0 ? '+' : '-').'$'.number_format(abs($changeAmount), 2),
                'change' => $changePercentage,
                'previous_display' => $previous > 0 ? '$'.number_format($previous, 2) : '—',
                'open_display' => (float) ($latestDaily['open'] ?? 0) > 0 ? '$'.number_format((float) $latestDaily['open'], 2) : '—',
                'high_display' => $high > 0 ? '$'.number_format($high, 2) : '—',
                'low_display' => $low > 0 ? '$'.number_format($low, 2) : '—',
                'range_low_display' => $low > 0 ? '$'.number_format($low, 2) : '—',
                'range_high_display' => $high > 0 ? '$'.number_format($high, 2) : '—',
                'range_kind' => 'day',
                'range_position' => $rangePosition($current, $low, $high),
                'trend' => (string) ($analysis['trend'] ?? 'Neutral'),
                'momentum_percent' => (float) ($analysis['momentum_percent'] ?? 0),
                'support_display' => !empty($analysis['support']) ? '$'.number_format((float) $analysis['support'], 2) : '—',
                'resistance_display' => !empty($analysis['resistance']) ? '$'.number_format((float) $analysis['resistance'], 2) : '—',
                'activity_code' => 'volume',
                'activity_value' => $compactNumber((float) ($analysis['volume_current'] ?? 0), ' BTC'),
                'activity_meta_value' => null,
                'status' => '24_7',
                'updated_at' => optional($bitcoin->last_updated)?->toIso8601String(),
                'quotes' => $quotes,
            ]);
        } else {
            $heroMarkets->push(['available' => false, 'asset_class' => 'crypto', 'asset_label' => 'Crypto', 'symbol' => 'BTC/USD', 'name' => 'Bitcoin / US Dollar']);
        }

        $featuredCars = Car::query()
            ->where('is_available', true)
            ->latest('created_at')
            ->limit(3)
            ->get();

        $featuredInvestments = PrivateInvestmentInstrument::query()
            ->where('is_visible', true)
            ->whereIn('status', ['active', 'paused'])
            ->where('name', 'not like', '%ACCEPTANCE%')
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->limit(3)
            ->get();

        $platformStats = [
            'instruments' => MarketInstrument::active()->count(),
            'automotive_inventory' => Car::query()->where('is_available', true)->count(),
            'investments' => PrivateInvestmentInstrument::query()
                ->where('is_visible', true)
                ->whereIn('status', ['active', 'paused'])
                ->where('name', 'not like', '%ACCEPTANCE%')
                ->count(),
            'bots' => BotProduct::query()
                ->where('is_active', true)
                ->where('name', 'not like', '%ACCEPTANCE%')
                ->count(),
            'copy_strategies' => CopyStrategy::query()
                ->where('is_public', true)
                ->where('is_active', true)
                ->count(),
            'signals' => Signal::query()
                ->whereIn('status', ['published', 'active'])
                ->count(),
        ];

        return view('frontend.home', compact(
            'marketShowcase',
            'marketTape',
            'heroMarkets',
            'platformStats',
            'featuredCars',
            'featuredInvestments'
        ));
    }

    public function markets(BotMarketContextService $markets)
    {
        $seriesFor = function (?MarketInstrument $instrument, array $fallback = []) use ($markets): array {
            $quotes = $instrument
                ? collect($markets->priceSeries($instrument, 24))
                    ->pluck('price')
                    ->map(fn ($price) => (float) $price)
                    ->filter(fn ($price) => $price > 0)
                    ->values()
                : collect();

            if ($quotes->count() < 2) {
                $quotes = collect($fallback)
                    ->map(fn ($price) => (float) $price)
                    ->filter(fn ($price) => $price > 0)
                    ->values();
            }

            return $quotes->all();
        };

        $marketCards = collect();

        $stocks = Stock::active()
            ->with('marketInstrument')
            ->where('company_name', 'not like', '%ACCEPTANCE%')
            ->orderByDesc('is_featured')
            ->orderByDesc('volume')
            ->limit(12)
            ->get();

        foreach ($stocks as $stock) {
            $current = (float) $stock->current_price;
            $previous = (float) ($stock->previous_close ?? 0);

            $marketCards->push([
                'asset_class' => 'stock',
                'asset_label' => 'Stock',
                'symbol' => (string) $stock->symbol,
                'name' => (string) ($stock->company_name ?: 'Listed equity'),
                'price_display' => '$'.number_format($current, 2),
                'change' => (float) ($stock->change_percentage ?? ($previous > 0 ? (($current - $previous) / $previous) * 100 : 0)),
                'icon' => 'chart-no-axes-combined',
                'quotes' => $seriesFor($stock->marketInstrument, [$previous, $current]),
                'action_url' => auth()->check()
                    ? route('instruments.stocks.show', ['stock' => $stock->symbol])
                    : route('login'),
            ]);
        }

        $forexPairs = ForexPair::active()
            ->with('marketInstrument')
            ->where('name', 'not like', '%ACCEPTANCE%')
            ->orderByDesc('is_featured')
            ->orderBy('symbol')
            ->limit(12)
            ->get();

        foreach ($forexPairs as $pair) {
            $current = (float) $pair->current_rate;
            $previous = (float) ($pair->previous_close ?? 0);
            $precision = max(2, min(8, (int) ($pair->price_precision ?? 5)));
            $symbol = (string) ($pair->display_symbol ?: $pair->symbol);

            $marketCards->push([
                'asset_class' => 'forex',
                'asset_label' => 'Forex',
                'symbol' => $symbol,
                'name' => (string) ($pair->name ?: 'Currency pair'),
                'price_display' => number_format($current, $precision),
                'change' => $previous > 0 ? (($current - $previous) / $previous) * 100 : 0.0,
                'icon' => 'arrow-left-right',
                'quotes' => $seriesFor($pair->marketInstrument, [$previous, $current]),
                'action_url' => auth()->check()
                    ? route('instruments.forex.show', ['symbol' => $pair->symbol])
                    : route('login'),
            ]);
        }

        $cryptoPairs = CryptoPair::active()
            ->with('marketInstrument')
            ->where('name', 'not like', '%ACCEPTANCE%')
            ->orderByDesc('is_featured')
            ->orderBy('symbol')
            ->limit(12)
            ->get();

        foreach ($cryptoPairs as $pair) {
            $current = (float) $pair->current_rate;
            $previous = (float) ($pair->previous_close ?? 0);
            $precision = max(2, min(8, (int) ($pair->price_precision ?? 2)));
            $quote = strtoupper((string) ($pair->quote_asset ?: 'USD'));
            $displayPrice = number_format($current, $precision);
            $symbol = (string) ($pair->display_symbol ?: $pair->symbol);

            $marketCards->push([
                'asset_class' => 'crypto',
                'asset_label' => 'Crypto',
                'symbol' => $symbol,
                'name' => (string) ($pair->name ?: 'Digital asset pair'),
                'price_display' => $quote === 'USD' ? '$'.$displayPrice : $displayPrice.' '.$quote,
                'change' => $previous > 0 ? (($current - $previous) / $previous) * 100 : 0.0,
                'icon' => 'bitcoin',
                'quotes' => $seriesFor($pair->marketInstrument, [$previous, $current]),
                'action_url' => auth()->check()
                    ? route('instruments.crypto.show', ['symbol' => $pair->symbol])
                    : route('login'),
            ]);
        }

        $commodities = MarketInstrument::query()
            ->active()
            ->with('canonicalCommodityInstrument')
            ->where('asset_class', MarketInstrument::ASSET_COMMODITY)
            ->orderBy('symbol')
            ->limit(12)
            ->get();

        foreach ($commodities as $instrument) {
            $commodity = $instrument->canonicalCommodityInstrument;
            if (!$commodity) continue;

            $current = (float) ($commodity->current_price ?? 0);
            $previous = (float) ($commodity->previous_close ?? 0);

            $marketCards->push([
                'asset_class' => 'commodity',
                'asset_label' => 'Commodity',
                'symbol' => (string) ($instrument->display_symbol ?: $instrument->symbol),
                'name' => (string) ($instrument->name ?: 'Commodity market'),
                'price_display' => $current > 0 ? '$'.number_format($current, 2) : '—',
                'change' => $previous > 0 && $current > 0 ? (($current - $previous) / $previous) * 100 : 0.0,
                'icon' => 'gem',
                'quotes' => $seriesFor($instrument, [$previous, $current]),
                'action_url' => auth()->check()
                    ? route('instruments.commodities.show', ['symbol' => $instrument->symbol])
                    : route('login'),
            ]);
        }

        $marketCards = $marketCards
            ->sortBy([
                ['asset_class', 'asc'],
                ['symbol', 'asc'],
            ])
            ->values();

        $marketStats = [
            'total' => $marketCards->count(),
            'stock' => $marketCards->where('asset_class', 'stock')->count(),
            'forex' => $marketCards->where('asset_class', 'forex')->count(),
            'crypto' => $marketCards->where('asset_class', 'crypto')->count(),
            'commodity' => $marketCards->where('asset_class', 'commodity')->count(),
        ];

        return view('frontend.markets', compact('marketCards', 'marketStats'));
    }

    public function show($id)
    {
        $car = Car::findOrFail($id);
        
        // If car is not available, check if current user has purchased it or has pending purchase
        if (!$car->is_available) {
            // If user is not authenticated, deny access
            if (!auth()->check()) {
                abort(404);
            }
            
            // Check if the authenticated user has purchased this car or has a pending purchase
            $userHasPurchaseOrPending = $car->purchases()
                ->where('user_id', auth()->id())
                ->whereIn('status', ['completed', 'pending', 'processing'])
                ->exists();
            
            // If user hasn't purchased this car or doesn't have pending purchase, deny access
            if (!$userHasPurchaseOrPending) {
                abort(404);
            }
        }
        
        return view('frontend.car_detail', compact('car'));
    }

    public function browse(Request $request)
    {
        $query = Car::query();
        
        // Filter by availability (default: show all)
        if ($request->has('available') && $request->available == 'true') {
            $query->where('is_available', true);
        }
        
        // Filter by make
        if ($request->has('make') && !empty($request->make)) {
            $query->where('make', $request->make);
        }
        
        // Filter by model
        if ($request->has('model') && !empty($request->model)) {
            $query->where('model', $request->model);
        }
        
        // Filter by year
        if ($request->has('year') && !empty($request->year)) {
            $query->where('year', $request->year);
        }
        
        // Sort options
        $sort = $request->sort ?? 'newest';
        
        switch ($sort) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }
        
        // Get unique makes, models and years for filters
        $makes = Car::distinct()->pluck('make')->filter();
        $models = Car::distinct()->pluck('model')->filter();
        $years = Car::distinct()->pluck('year')->filter()->sort()->reverse();
        
        $cars = $query->paginate(12);
        
        return view('frontend.browse', compact('cars', 'makes', 'models', 'years', 'sort'));
    }

    public function about()
    {
        return view('frontend.about');
    }

    public function contact(Request $request)
    {
        if ($request->isMethod('post')) {
            // Validate the form data
            $validated = $request->validate([
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'email' => 'required|email|max:255',
                'subject' => 'required|string|in:general,support,billing,partnership,press',
                'message' => 'required|string|min:10|max:2000',
            ], [
                'first_name.required' => 'Please enter your first name.',
                'last_name.required' => 'Please enter your last name.',
                'email.required' => 'Please enter your email address.',
                'email.email' => 'Please enter a valid email address.',
                'subject.required' => 'Please select a subject.',
                'subject.in' => 'Please select a valid subject.',
                'message.required' => 'Please enter your message.',
                'message.min' => 'Your message must be at least 10 characters long.',
                'message.max' => 'Your message cannot exceed 2000 characters.',
            ]);

            try {
                // Send email to site email
                $siteEmail = site_email();
                $subject = "Contact Form: " . ucfirst($validated['subject']);
                
                $emailContent = "
                New contact form submission from {$validated['first_name']} {$validated['last_name']}
                
                Email: {$validated['email']}
                Subject: " . ucfirst($validated['subject']) . "
                
                Message:
                {$validated['message']}
                
                ---
                This message was sent from the contact form on " . site_name() . "
                ";

                // Use Laravel's Mail facade to send the email
                \Mail::raw($emailContent, function($message) use ($siteEmail, $subject, $validated) {
                    $message->to($siteEmail)
                            ->subject($subject)
                            ->replyTo($validated['email'], $validated['first_name'] . ' ' . $validated['last_name']);
                });

                return redirect()->route('contact')->with('success', 'Thank you for your message! We will get back to you soon.');
                
            } catch (\Exception $e) {
                return redirect()->route('contact')
                    ->withErrors(['message' => 'Sorry, there was an error sending your message. Please try again later.'])
                    ->withInput();
            }
        }

        return view('frontend.contact');
    }

    public function helpCenter()
    {
        return view('frontend.help-center');
    }

    public function terms()
    {
        return view('frontend.terms');
    }

    public function privacy()
    {
        return view('frontend.privacy');
    }
}
