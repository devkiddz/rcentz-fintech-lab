<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index()
    {
        $stocks = Stock::with(['holdings' => function($query) {
            $query->with('user');
        }])
        ->withCount('holdings')
        ->withCount('transactions')
        ->orderBy('symbol')
        ->get();

        $stats = [
            'total_stocks' => $stocks->count(),
            'active_stocks' => $stocks->where('is_active', true)->count(),
            'stocks_with_holdings' => $stocks->where('holdings_count', '>', 0)->count(),
            'total_holdings' => $stocks->sum('holdings_count'),
            'total_transactions' => $stocks->sum('transactions_count'),
        ];

        return view('admin.stocks.index', compact('stocks', 'stats'));
    }

    public function show(Stock $stock)
    {
        $stock->load([
            'holdings.user',
            'holdings' => function($query) {
                $query->orderBy('created_at', 'desc');
            },
            'transactions.user',
            'transactions' => function($query) {
                $query->orderBy('created_at', 'desc');
            },
            'priceHistory' => function($query) {
                $query->orderBy('timestamp', 'desc')->limit(30);
            }
        ]);

        $stats = [
            'total_holdings' => $stock->holdings->count(),
            'total_transactions' => $stock->transactions->count(),
            'total_volume' => $stock->transactions->sum('total_amount'),
            'total_shares' => $stock->holdings->sum('quantity'),
            'total_value' => $stock->holdings->sum('current_value'),
            'total_invested' => $stock->holdings->sum('total_invested'),
            'total_gain_loss' => $stock->holdings->sum('unrealized_gain_loss'),
        ];

        return view('admin.stocks.show', compact('stock', 'stats'));
    }
}
