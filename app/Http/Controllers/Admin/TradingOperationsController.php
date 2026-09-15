<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\StockTransaction;
use App\Models\TradePosition;

class TradingOperationsController extends Controller
{
    public function index()
    {
        $openPositions = TradePosition::whereIn('status', ['open', 'exit_queued'])
            ->where('open_quantity', '>', 0)
            ->count();

        $closedPositions = TradePosition::where('status', 'closed')->count();
        $transactions = StockTransaction::count();
        $activeStocks = Stock::where('is_active', true)->count();

        $recentPositions = TradePosition::with(['user', 'stock'])
            ->latest('opened_at')
            ->limit(8)
            ->get();

        return view('admin.trading.index', compact(
            'openPositions',
            'closedPositions',
            'transactions',
            'activeStocks',
            'recentPositions'
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
        $transactions = StockTransaction::with(['user', 'stock'])
            ->latest('executed_at')
            ->latest('id')
            ->paginate(50);

        return view('admin.trading.history', compact('transactions'));
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
