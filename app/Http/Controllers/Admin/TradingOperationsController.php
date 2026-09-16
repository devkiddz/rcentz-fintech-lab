<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\StockTransaction;
use App\Models\TradePosition;
use App\Services\MarketPriceRouter;
use App\Services\StockAnalysisService;

class TradingOperationsController extends Controller
{
    public function index(StockAnalysisService $analysisService)
    {
        $openPositionRows = TradePosition::with('stock')
            ->whereIn('status', ['open', 'exit_queued'])
            ->where('open_quantity', '>', 0)
            ->get();

        $openPositions = $openPositionRows->count();
        $openProfitLoss = (float) $openPositionRows
            ->sum(fn (TradePosition $position) => (float) $position->current_profit_loss);

        $closedPositions = TradePosition::where('status', 'closed')->count();
        $transactions = StockTransaction::count();
        $activeStocks = Stock::where('is_active', true)->count();

        $totalRealizedProfitLoss = (float) TradePosition::where('status', 'closed')
            ->sum('realized_profit_loss');

        $winningPositions = TradePosition::where('status', 'closed')
            ->where('realized_profit_loss', '>', 0)
            ->count();

        $losingPositions = TradePosition::where('status', 'closed')
            ->where('realized_profit_loss', '<', 0)
            ->count();

        $winRate = $closedPositions > 0
            ? ($winningPositions / $closedPositions) * 100
            : 0;

        $recentPositions = TradePosition::with([
                'user',
                'stock',
                'entryTransaction',
                'lastExitTransaction',
            ])
            ->latest('opened_at')
            ->limit(8)
            ->get();

        $focusPosition = TradePosition::with([
                'user',
                'stock',
                'entryTransaction',
                'lastExitTransaction',
                'events' => fn ($query) => $query->latest()->limit(6),
            ])
            ->latest('opened_at')
            ->first();

        $focusAnalysis = [];
        if ($focusPosition?->stock) {
            try {
                $focusAnalysis = $analysisService->forStockInMarketplace(
                    $focusPosition->stock,
                    $focusPosition->marketplace ?: 'live'
                );
            } catch (\Throwable $e) {
                \Log::warning('Trading overview analysis unavailable', [
                    'position_id' => $focusPosition->id,
                    'stock_id' => $focusPosition->stock_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('admin.trading.index', compact(
            'openPositions',
            'closedPositions',
            'transactions',
            'activeStocks',
            'totalRealizedProfitLoss',
            'openProfitLoss',
            'winningPositions',
            'losingPositions',
            'winRate',
            'recentPositions',
            'focusPosition',
            'focusAnalysis'
        ));
    }

    public function positions()
    {
        $positions = TradePosition::with(['user', 'stock'])
            ->latest('opened_at')
            ->paginate(40);

        return view('admin.trading.positions', compact('positions'));
    }

    public function history()
    {
        $positions = TradePosition::with([
                'user',
                'stock',
                'entryTransaction',
                'lastExitTransaction',
            ])
            ->latest('opened_at')
            ->paginate(50);

        $historyStats = [
            'total' => TradePosition::count(),
            'open' => TradePosition::whereIn('status', ['open', 'exit_queued'])
                ->where('open_quantity', '>', 0)
                ->count(),
            'closed' => TradePosition::where('status', 'closed')->count(),
            'realized_profit_loss' => (float) TradePosition::where('status', 'closed')
                ->sum('realized_profit_loss'),
        ];

        return view('admin.trading.history', compact('positions', 'historyStats'));
    }

    public function show(TradePosition $position, StockAnalysisService $analysisService, MarketPriceRouter $prices)
    {
        $position->load([
            'user',
            'stock',
            'entryTransaction',
            'lastExitTransaction',
            'events' => fn ($query) => $query
                ->with(['actor', 'stockTransaction'])
                ->oldest('created_at'),
        ]);

        $analysis = [];
        if ($position->stock) {
            try {
                $analysis = $analysisService->forStockInMarketplace(
                    $position->stock,
                    $position->marketplace ?: 'live'
                );
            } catch (\Throwable $e) {
                \Log::warning('Trade record analysis unavailable', [
                    'position_id' => $position->id,
                    'stock_id' => $position->stock_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $isOpen = $position->is_open;

        $displayExitPrice = $isOpen && $position->stock
            ? $prices->price($position->stock, $position->marketplace ?: 'live')
            : (float) (
                $position->average_exit_price
                ?: $position->lastExitTransaction?->price_per_share
                ?: 0
            );

        $profitLoss = $isOpen
            ? (float) $position->current_profit_loss
            : (float) $position->realized_profit_loss;

        $returnPercent = $isOpen
            ? (float) $position->current_return_percent
            : (float) $position->realized_return_percent;

        return view('admin.trading.show', compact(
            'position',
            'analysis',
            'displayExitPrice',
            'profitLoss',
            'returnPercent',
            'isOpen'
        ));
    }

    public function manual()
    {
        $stocks = Stock::query()
            ->where('is_active', true)
            ->orderBy('symbol')
            ->paginate(30);

        return view('admin.trading.manual', compact('stocks'));
    }
}
