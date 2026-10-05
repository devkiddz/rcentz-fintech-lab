<?php

namespace App\Http\Controllers;

use App\Models\ControlledMarketInstrument;
use App\Models\MarketInstrument;
use App\Models\Stock;
use App\Models\TradePosition;
use App\Services\ControlledMarketEngine;
use App\Services\MarketInstrumentAnalysisService;
use App\Services\MarketPriceRouter;
use App\Services\StockAnalysisService;
use Illuminate\Http\Request;

class MarketRuntimeController extends Controller
{
    public function snapshot(
        Request $request,
        MarketPriceRouter $prices,
        ControlledMarketEngine $controlled,
        StockAnalysisService $stockAnalysis,
        MarketInstrumentAnalysisService $instrumentAnalysis
    ) {
        $activeMarketplace = $prices->activeMarketplace();
        // Polling must not advance the market or run trading workers.
        $tick = ['ticked' => false];

        $instrumentIds = collect(explode(',', (string) $request->query('instruments', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->take(50)
            ->values();

        $parents = MarketInstrument::query()
            ->with([
                'stock',
                'forexPair',
                'canonicalStock',
                'canonicalForexPair',
                'canonicalCryptoPair',
                'controlledMarketInstrument',
            ])
            ->whereIn('id', $instrumentIds)
            ->get()
            ->keyBy('id');

        $instrumentPayload = [];

        foreach ($instrumentIds as $id) {
            $instrument = $parents->get($id);
            if (! $instrument) {
                continue;
            }

            foreach (['live', 'controlled'] as $marketplace) {
                try {
                    $current = $prices->price($instrument, $marketplace);
                    $previous = $this->previousInstrumentPrice($instrument, $marketplace, $current);
                    $instrumentPayload[$instrument->id][$marketplace] = $this->instrumentMarketRow(
                        $instrument,
                        $current,
                        $previous
                    );
                } catch (\Throwable $e) {
                    $instrumentPayload[$instrument->id][$marketplace] = [
                        'unavailable' => true,
                        'message' => 'Market mark is temporarily unavailable.',
                    ];
                }
            }
        }

        // Legacy symbol payload remains intact for stock-specific screens while
        // MarketInstrument-aware screens use the parent-ID payload above.
        $symbols = collect(explode(',', (string) $request->query('symbols', '')))
            ->map(fn ($symbol) => strtoupper(trim($symbol)))
            ->filter(fn ($symbol) => $symbol !== '' && preg_match('/^[A-Z0-9.\\-]+$/', $symbol))
            ->unique()
            ->take(30)
            ->values();

        $stocks = Stock::query()
            ->whereIn('symbol', $symbols)
            ->get()
            ->keyBy('symbol');

        $controlledRows = ControlledMarketInstrument::query()
            ->whereIn('stock_id', $stocks->pluck('id'))
            ->get()
            ->keyBy('stock_id');

        $stockPayload = [];

        foreach ($symbols as $symbol) {
            $stock = $stocks->get($symbol);
            if (! $stock) {
                continue;
            }

            $liveCurrent = (float) $stock->getRawOriginal('current_price');
            $livePrevious = (float) ($stock->previous_close ?: $liveCurrent);
            $stockPayload[$symbol]['live'] = $this->stockMarketRow($liveCurrent, $livePrevious);

            $controlledRow = $controlledRows->get($stock->id);
            if ($controlledRow) {
                $stockPayload[$symbol]['controlled'] = $this->stockMarketRow(
                    (float) $controlledRow->current_price,
                    (float) ($controlledRow->previous_price ?: $controlledRow->current_price)
                );
            }
        }

        $positionIds = collect(explode(',', (string) $request->query('positions', '')))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->take(50)
            ->values();

        $positionQuery = TradePosition::query()->with(['stock.marketInstrument', 'marketInstrument', 'user.wallet'])->whereIn('id', $positionIds);
        $viewer = $request->user();
        if (! $viewer?->isAdmin()) {
            $positionQuery->where('user_id', $viewer?->id);
        }

        $positionPayload = [];
        foreach ($positionQuery->get() as $position) {
            $positionPayload[$position->id] = app(\App\Services\PaperTrading\PositionPresentationService::class)
                ->describe($position);
        }

        $instrumentAnalysisRequests = collect(explode(',', (string) $request->query('instrument_analysis', '')))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->unique()
            ->take(10);

        $instrumentAnalysisPayload = [];
        foreach ($instrumentAnalysisRequests as $item) {
            [$id, $marketplace] = array_pad(explode(':', $item, 2), 2, $activeMarketplace);
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }

            try {
                $marketplace = $prices->normalizeMarketplace($marketplace);
            } catch (\Throwable) {
                continue;
            }

            $instrument = $parents->get($id)
                ?: MarketInstrument::query()->find($id);
            if (! $instrument) {
                continue;
            }

            try {
                $instrumentAnalysisPayload[$id.':'.$marketplace] = $instrumentAnalysis->forInstrument($instrument, $marketplace);
            } catch (\Throwable $e) {
                \Log::debug('Runtime MarketInstrument analysis refresh skipped', [
                    'market_instrument_id' => $id,
                    'marketplace' => $marketplace,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Legacy stock analysis requests are preserved for old screens.
        $analysisRequests = collect(explode(',', (string) $request->query('analysis', '')))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->unique()
            ->take(6);

        $analysisPayload = [];
        foreach ($analysisRequests as $item) {
            [$symbol, $marketplace] = array_pad(explode(':', $item, 2), 2, $activeMarketplace);
            $symbol = strtoupper(trim($symbol));
            try {
                $marketplace = $prices->normalizeMarketplace($marketplace);
            } catch (\Throwable) {
                continue;
            }

            $stock = $stocks->get($symbol) ?: Stock::query()->where('symbol', $symbol)->first();
            if (! $stock) {
                continue;
            }

            try {
                $parent = $stock->marketInstrument ?: $stock->legacyMarketInstrument;
                $analysisPayload[$symbol.':'.$marketplace] = $parent
                    ? $instrumentAnalysis->forInstrument($parent, $marketplace)
                    : $stockAnalysis->forStockInMarketplace($stock, $marketplace);
            } catch (\Throwable $e) {
                \Log::debug('Runtime stock analysis refresh skipped', [
                    'symbol' => $symbol,
                    'marketplace' => $marketplace,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'active_marketplace' => $activeMarketplace,
            'controlled_tick' => $tick,
            'instruments' => $instrumentPayload,
            'stocks' => $stockPayload,
            'positions' => $positionPayload,
            'instrument_analysis' => $instrumentAnalysisPayload,
            'analysis' => $analysisPayload,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    private function previousInstrumentPrice(MarketInstrument $instrument, string $marketplace, float $current): float
    {
        if ($marketplace === 'controlled') {
            $row = $instrument->controlledMarketInstrument
                ?: ControlledMarketInstrument::query()->where('market_instrument_id', $instrument->id)->first();

            return (float) ($row?->previous_price ?: $row?->current_price ?: $current);
        }

        if ($instrument->isStock()) {
            $stock = $instrument->canonicalStock ?: $instrument->stock;
            return (float) ($stock?->previous_close ?: $stock?->getRawOriginal('current_price') ?: $current);
        }

        if ($instrument->isForex()) {
            $pair = $instrument->canonicalForexPair ?: $instrument->forexPair;
            return (float) ($pair?->previous_close ?: $pair?->current_rate ?: $current);
        }

        if ($instrument->isCrypto()) {
            $pair = $instrument->canonicalCryptoPair;
            return (float) ($pair?->previous_close ?: $pair?->current_rate ?: $current);
        }

        return $current;
    }

    private function instrumentMarketRow(MarketInstrument $instrument, float $current, float $previous): array
    {
        $change = $current - $previous;
        $percent = $previous > 0 ? ($change / $previous) * 100 : 0;
        $precision = max(0, min(8, (int) $instrument->price_precision));
        $prefix = $instrument->isStock() ? 'USD ' : '';

        return [
            'price' => $current,
            'previous' => $previous,
            'change' => $change,
            'change_percent' => $percent,
            'formatted_price' => $prefix.number_format($current, $precision),
            'formatted_previous' => $prefix.number_format($previous, $precision),
            'formatted_change' => ($change >= 0 ? '+' : '-').$prefix.number_format(abs($change), $precision),
            'formatted_change_percent' => ($percent >= 0 ? '+' : '').number_format($percent, 2).'%',
        ];
    }

    private function stockMarketRow(float $current, float $previous): array
    {
        $change = $current - $previous;
        $percent = $previous > 0 ? ($change / $previous) * 100 : 0;

        return [
            'price' => $current,
            'previous' => $previous,
            'change' => $change,
            'change_percent' => $percent,
            'formatted_price' => 'USD '.number_format($current, 2),
            'formatted_previous' => 'USD '.number_format($previous, 2),
            'formatted_change' => ($change >= 0 ? '+' : '-').'USD '.number_format(abs($change), 2),
            'formatted_change_percent' => ($percent >= 0 ? '+' : '').number_format($percent, 2).'%',
        ];
    }
}
