<?php

namespace App\Http\Controllers;

use App\Models\ControlledMarketInstrument;
use App\Models\Stock;
use App\Models\TradePosition;
use App\Services\ControlledMarketEngine;
use App\Services\MarketPriceRouter;
use App\Services\StockAnalysisService;
use Illuminate\Http\Request;

class MarketRuntimeController extends Controller
{
    public function snapshot(
        Request $request,
        MarketPriceRouter $prices,
        ControlledMarketEngine $controlled,
        StockAnalysisService $analysisService
    ) {
        $activeMarketplace = $prices->activeMarketplace();
        $tick = $controlled->tickIfDue();

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
            if (! $stock) continue;

            $liveCurrent = (float) $stock->getRawOriginal('current_price');
            $livePrevious = (float) ($stock->previous_close ?: $liveCurrent);
            $stockPayload[$symbol]['live'] = $this->marketRow($liveCurrent, $livePrevious);

            $instrument = $controlledRows->get($stock->id);
            if ($instrument) {
                $stockPayload[$symbol]['controlled'] = $this->marketRow(
                    (float) $instrument->current_price,
                    (float) ($instrument->previous_price ?: $instrument->current_price)
                );
            }
        }

        $positionIds = collect(explode(',', (string) $request->query('positions', '')))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->take(50)
            ->values();

        $positionQuery = TradePosition::query()->with('stock')->whereIn('id', $positionIds);
        $viewer = $request->user();
        if (! $viewer?->isAdmin()) {
            $positionQuery->where('user_id', $viewer?->id);
        }

        $positionPayload = [];
        foreach ($positionQuery->get() as $position) {
            $isOpen = $position->is_open;
            $cmp = $isOpen && $position->stock
                ? $prices->price($position->stock, $position->marketplace ?: 'live')
                : (float) ($position->average_exit_price ?: $position->lastExitTransaction?->price_per_share ?: $position->entry_price);
            $pnl = $isOpen ? (float) $position->current_profit_loss : (float) $position->realized_profit_loss;
            $return = $isOpen ? (float) $position->current_return_percent : (float) $position->realized_return_percent;
            $difference = $cmp - (float) $position->entry_price;

            $positionPayload[$position->id] = [
                'label' => $isOpen ? 'Current Trade P/L' : 'Realized P/L',
                'marketplace' => $position->marketplace ?: 'live',
                'cmp' => $cmp,
                'difference' => $difference,
                'pnl' => $pnl,
                'return_percent' => $return,
                'formatted_cmp' => currency_symbol().number_format($cmp, 2),
                'formatted_difference' => ($difference >= 0 ? '+' : '-').currency_symbol().number_format(abs($difference), 2),
                'formatted_pnl' => ($pnl >= 0 ? '+' : '-').currency_symbol().number_format(abs($pnl), 2),
                'formatted_return' => ($return >= 0 ? '+' : '').number_format($return, 2).'%',
            ];
        }

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
            if (! $stock) continue;

            try {
                $analysisPayload[$symbol.':'.$marketplace] = $analysisService->forStockInMarketplace($stock, $marketplace);
            } catch (\Throwable $e) {
                \Log::debug('Runtime analysis refresh skipped', [
                    'symbol' => $symbol,
                    'marketplace' => $marketplace,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'active_marketplace' => $activeMarketplace,
            'controlled_tick' => $tick,
            'stocks' => $stockPayload,
            'positions' => $positionPayload,
            'analysis' => $analysisPayload,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    private function marketRow(float $current, float $previous): array
    {
        $change = $current - $previous;
        $percent = $previous > 0 ? ($change / $previous) * 100 : 0;

        return [
            'price' => $current,
            'previous' => $previous,
            'change' => $change,
            'change_percent' => $percent,
            'formatted_price' => currency_symbol().number_format($current, 2),
            'formatted_previous' => currency_symbol().number_format($previous, 2),
            'formatted_change' => ($change >= 0 ? '+' : '-').currency_symbol().number_format(abs($change), 2),
            'formatted_change_percent' => ($percent >= 0 ? '+' : '').number_format($percent, 2).'%',
        ];
    }
}
