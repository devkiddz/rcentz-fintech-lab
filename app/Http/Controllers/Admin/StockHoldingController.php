<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockHolding;
use Illuminate\Http\Request;

class StockHoldingController extends Controller
{
    public function index(Request $request)
    {
        $query = StockHolding::with(['user', 'stock']);

        // Filter by stock if provided
        if ($request->has('stock') && $request->stock) {
            $query->where('stock_id', $request->stock);
        }

        $holdings = $query->orderBy('created_at', 'desc')->paginate(20);

        $stats = [
            'total_holdings' => StockHolding::count(),
            'unique_investors' => StockHolding::distinct('user_id')->count(),
            'unique_stocks' => StockHolding::distinct('stock_id')->count(),
            'total_value' => StockHolding::sum('current_value'),
            'total_invested' => StockHolding::sum('total_invested'),
            'total_gain_loss' => StockHolding::sum('unrealized_gain_loss'),
        ];

        return view('admin.stock-holdings.index', compact('holdings', 'stats'));
    }

    public function show(StockHolding $holding)
    {
        $holding->load(['user', 'stock', 'stock.priceHistory' => function($query) {
                $query->orderBy('timestamp', 'desc')->limit(30);
            }]);

        return view('admin.stock-holdings.show', compact('holding'));
    }
}
