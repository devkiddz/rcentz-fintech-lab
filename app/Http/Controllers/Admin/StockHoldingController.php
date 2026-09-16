<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ControlledMarketInstrument;
use App\Models\StockHolding;
use App\Services\MarketPriceRouter;
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

    public function show(StockHolding $holding, MarketPriceRouter $prices)
    {
        $holding->load(['user', 'stock', 'stock.priceHistory' => function($query) {
                $query->orderBy('timestamp', 'desc')->limit(30);
            }]);

        // V5.23.1: render the holding through the market authority that owns it.
        $marketplace = $prices->normalizeMarketplace($holding->marketplace ?: 'live');
        $current = $prices->price($holding->stock, $marketplace);

        if ($marketplace === 'controlled') {
            $instrument = ControlledMarketInstrument::query()
                ->where('stock_id', $holding->stock_id)
                ->first();
            $previous = (float) ($instrument?->previous_price ?? $current);
        } else {
            $previous = (float) ($holding->stock->previous_close ?: $current);
        }

        $change = $current - $previous;
        $changePercent = $previous > 0 ? ($change / $previous) * 100 : 0;

        $marketContext = [
            'marketplace' => $marketplace,
            'source_label' => $marketplace === 'controlled' ? 'Internal Feed' : 'External Feed',
            'current_price' => $current,
            'previous_price' => $previous,
            'change' => $change,
            'change_percent' => $changePercent,
        ];

        return view('admin.stock-holdings.show', compact('holding', 'marketContext'));
    }
}
