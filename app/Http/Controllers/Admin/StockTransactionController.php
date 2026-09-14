<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockTransaction;
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
        $transaction->load(['user', 'stock', 'walletTransaction.paymentMethod']);

        return view('admin.stock-transactions.show', compact('transaction'));
    }
}
