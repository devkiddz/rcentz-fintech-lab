<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockTransaction;
use App\Models\ControlledMarketInstrument;
use App\Services\MarketPriceRouter;
use Illuminate\Http\Request;

class StockTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = StockTransaction::with(['user', 'stock']);

        // Filter by stock if provided
        if ($request->has('stock') && $request->stock) {
            $query->where('stock_id', $request->stock);
        }

        // Filter by type if provided
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

        $stats = [
            'total_transactions' => StockTransaction::count(),
            'unique_users' => StockTransaction::distinct('user_id')->count(),
            'unique_stocks' => StockTransaction::distinct('stock_id')->count(),
            'total_volume' => StockTransaction::sum('total_amount'),
            'buy_transactions' => StockTransaction::where('type', 'buy')->count(),
            'sell_transactions' => StockTransaction::where('type', 'sell')->count(),
            'completed_transactions' => StockTransaction::where('status', 'completed')->count(),
            'pending_transactions' => StockTransaction::where('status', 'pending')->count(),
        ];

        return view('admin.stock-transactions.index', compact('transactions', 'stats'));
    }

    public function show(StockTransaction $transaction)
    {
        $transaction->load([
            'user',
            'stock',
            'walletTransaction.paymentMethod',
            'position.stock',
            'strategy',
            'initiatedBy',
        ]);

        $marketplace = app(MarketPriceRouter::class)->normalizeMarketplace(
            $transaction->marketplace ?: 'live'
        );

        $current = app(MarketPriceRouter::class)->price($transaction->stock, $marketplace);

        if ($marketplace === 'controlled') {
            $instrument = ControlledMarketInstrument::query()
                ->where('stock_id', $transaction->stock_id)
                ->first();
            $previous = (float) ($instrument?->previous_price ?? $current);
        } else {
            $previous = (float) ($transaction->stock->previous_close ?? $current);
        }

        $change = $current - $previous;
        $changePercent = $previous > 0 ? ($change / $previous) * 100 : 0;

        $marketContext = [
            'marketplace' => $marketplace,
            'current_price' => $current,
            'previous_price' => $previous,
            'change' => $change,
            'change_percent' => $changePercent,
        ];

        $tradeResult = null;
        $position = $transaction->position;

        if ($position) {
            $isOpen = $position->is_open;
            $tradeResult = [
                'position_id' => $position->id,
                'label' => $isOpen ? 'Current Trade P/L' : 'Realized P/L',
                'pnl' => $isOpen
                    ? (float) $position->current_profit_loss
                    : (float) $position->realized_profit_loss,
                'return_percent' => $isOpen
                    ? (float) $position->current_return_percent
                    : (float) $position->realized_return_percent,
                'status' => $position->status,
            ];
        }

        return view('admin.stock-transactions.show', compact(
            'transaction',
            'marketContext',
            'tradeResult'
        ));
    }
}
