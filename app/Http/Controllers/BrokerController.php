<?php

namespace App\Http\Controllers;

use App\Models\BrokerOrder;
use App\Models\MarketExecutionTransaction;
use App\Models\MarketHolding;
use App\Models\MarketInstrument;
use App\Models\StockHolding;
use App\Models\TradePosition;
use App\Services\BrokerOrderService;
use App\Services\BrokerPortfolioService;
use App\Services\BrokerPositionService;
use App\Services\MarketExecutionRouter;
use App\Services\MarketInstrumentAnalysisService;
use App\Services\MarketPriceRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use RuntimeException;

final class BrokerController extends Controller
{
    public function workstation(
        string $assetClass,
        string $symbol,
        MarketInstrumentAnalysisService $analysisService,
        MarketPriceRouter $prices,
        MarketExecutionRouter $execution
    ) {
        $instrument = $this->resolveInstrument($assetClass, $symbol);
        $instrument->load([
            'stock',
            'forexPair',
            'canonicalStock',
            'canonicalForexPair',
            'canonicalCryptoPair',
            'controlledMarketInstrument',
        ]);

        $user = Auth::user();
        $marketplace = $prices->activeMarketplace();
        $analysis = $this->safeAnalysis($instrument, $analysisService, $prices, $marketplace);
        $capabilities = $execution->capabilities($instrument);
        $executionReady = $execution->canExecute($instrument);
        $wallet = $user->wallet;

        $holding = $instrument->isStock()
            ? StockHolding::query()
                ->where('user_id', $user->id)
                ->where('market_instrument_id', $instrument->id)
                ->where('marketplace', $marketplace)
                ->first()
            : MarketHolding::query()
                ->where('user_id', $user->id)
                ->where('market_instrument_id', $instrument->id)
                ->where('marketplace', $marketplace)
                ->first();

        $positions = TradePosition::query()
            ->where('user_id', $user->id)
            ->where('market_instrument_id', $instrument->id)
            ->where('marketplace', $marketplace)
            ->whereIn('status', ['open', 'exit_queued'])
            ->where('open_quantity', '>', 0)
            ->latest('opened_at')
            ->get();

        $recentOrders = BrokerOrder::query()
            ->where('user_id', $user->id)
            ->where('market_instrument_id', $instrument->id)
            ->latest('id')
            ->limit(6)
            ->get();

        $quantityModes = match ($instrument->asset_class) {
            MarketInstrument::ASSET_STOCK => ['units' => 'Shares'],
            MarketInstrument::ASSET_FOREX => ['units' => 'Base units', 'lots' => 'Standard lots'],
            MarketInstrument::ASSET_CRYPTO => ['units' => 'Asset units', 'settlement_amount' => 'Account amount'],
            default => [],
        };

        return view('broker.workstation', compact(
            'instrument',
            'analysis',
            'marketplace',
            'capabilities',
            'executionReady',
            'wallet',
            'holding',
            'positions',
            'recentOrders',
            'quantityModes'
        ) + ['idempotencyKey' => (string) Str::uuid()]);
    }

    public function submitOrder(
        Request $request,
        string $assetClass,
        string $symbol,
        BrokerOrderService $orders
    ) {
        $instrument = $this->resolveInstrument($assetClass, $symbol);
        $user = Auth::user();

        $data = $request->validate([
            'side' => 'required|in:buy,sell',
            'quantity' => 'required|numeric|min:0.00000001|max:1000000000000',
            'quantity_mode' => 'required|in:units,lots,settlement_amount',
            'idempotency_key' => 'required|string|max:120',
            'stop_loss_percent' => 'nullable|numeric|min:0.01|max:100',
            'take_profit_percent' => 'nullable|numeric|min:0.01|max:100',
            'duration_minutes' => 'nullable|integer|min:1|max:43200',
        ]);

        $risk = $data['side'] === 'buy'
            ? array_filter([
                'stop_loss_percent' => $data['stop_loss_percent'] ?? null,
                'take_profit_percent' => $data['take_profit_percent'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
            ], fn ($value) => $value !== null && $value !== '')
            : [];

        try {
            $order = $orders->placeMarketOrder(
                $user,
                $instrument,
                $data['side'],
                (float) $data['quantity'],
                $data['quantity_mode'],
                $data['idempotency_key'],
                $risk
            );

            if ($order->status === BrokerOrder::STATUS_FAILED) {
                throw new RuntimeException($order->failure_message ?: 'The order failed closed. Submit a new order to retry.');
            }
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['order' => $e->getMessage() ?: 'The order could not be executed. No partial customer action was accepted.'])
                ->withInput($request->except('idempotency_key'));
        }

        return redirect()
            ->route('broker.orders.show', ['publicId' => $order->public_id])
            ->with('success', 'Order '.$order->public_id.' completed with status '.strtoupper($order->status).'.');
    }

    public function portfolio(BrokerPortfolioService $portfolio)
    {
        return view('broker.portfolio', $portfolio->build(Auth::user()));
    }

    public function orders()
    {
        $orders = BrokerOrder::query()
            ->with(['marketInstrument', 'execution'])
            ->where('user_id', Auth::id())
            ->latest('id')
            ->paginate(30);

        return view('broker.orders', compact('orders'));
    }

    public function showOrder(string $publicId)
    {
        $order = BrokerOrder::query()
            ->with(['marketInstrument', 'execution', 'events'])
            ->where('user_id', Auth::id())
            ->where('public_id', $publicId)
            ->firstOrFail();

        return view('broker.order-show', compact('order'));
    }

    public function activity()
    {
        $executions = MarketExecutionTransaction::query()
            ->with('marketInstrument')
            ->where('user_id', Auth::id())
            ->latest('executed_at')
            ->latest('id')
            ->paginate(35);

        $orderMap = BrokerOrder::query()
            ->where('user_id', Auth::id())
            ->whereIn('market_execution_transaction_id', $executions->getCollection()->pluck('id'))
            ->get()
            ->keyBy('market_execution_transaction_id');

        return view('broker.activity', compact('executions', 'orderMap'));
    }

    public function positions(MarketPriceRouter $prices)
    {
        $marketplace = $prices->activeMarketplace();
        $positions = TradePosition::query()
            ->with(['marketInstrument', 'stock.marketInstrument', 'events'])
            ->where('user_id', Auth::id())
            ->where('marketplace', $marketplace)
            ->latest('opened_at')
            ->paginate(25);

        foreach ($positions->getCollection() as $position) {
            $instrument = $position->marketInstrument ?? $position->stock?->marketInstrument;
            $mark = null;
            if ($instrument && $position->is_open) {
                try {
                    $mark = (float) $prices->price($instrument, $position->marketplace ?: $marketplace);
                } catch (\Throwable) {
                    $mark = null;
                }
            }
            $position->setAttribute('runtime_mark_price', $mark);
        }

        return view('broker.positions', compact('positions', 'marketplace'));
    }

    public function updatePositionRisk(
        Request $request,
        TradePosition $position,
        BrokerPositionService $positions
    ) {
        abort_unless((int) $position->user_id === (int) Auth::id(), 403);

        $data = $request->validate([
            'stop_loss_percent' => 'nullable|numeric|min:0.01|max:100',
            'take_profit_percent' => 'nullable|numeric|min:0.01|max:100',
            'duration_minutes' => 'nullable|integer|min:1|max:43200',
        ]);

        try {
            $positions->updateRisk(
                Auth::user(),
                $position,
                isset($data['stop_loss_percent']) ? (float) $data['stop_loss_percent'] : null,
                isset($data['take_profit_percent']) ? (float) $data['take_profit_percent'] : null,
                isset($data['duration_minutes']) ? (int) $data['duration_minutes'] : null
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['position' => $e->getMessage()]);
        }

        return back()->with('success', 'Position risk controls updated.');
    }

    public function closePosition(
        Request $request,
        TradePosition $position,
        BrokerPositionService $positions
    ) {
        abort_unless((int) $position->user_id === (int) Auth::id(), 403);

        $data = $request->validate([
            'quantity' => 'nullable|numeric|min:0.00000001',
            'idempotency_key' => 'required|string|max:120',
        ]);

        try {
            $order = $positions->close(
                Auth::user(),
                $position,
                isset($data['quantity']) ? (float) $data['quantity'] : null,
                $data['idempotency_key']
            );

            if ($order->status === BrokerOrder::STATUS_FAILED) {
                throw new RuntimeException($order->failure_message ?: 'The position close failed closed.');
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['position' => $e->getMessage()]);
        }

        return redirect()
            ->route('broker.orders.show', ['publicId' => $order->public_id])
            ->with('success', 'Position close order completed.');
    }

    private function resolveInstrument(string $assetClass, string $symbol): MarketInstrument
    {
        $assetClass = strtolower(trim($assetClass));
        if (! in_array($assetClass, [
            MarketInstrument::ASSET_STOCK,
            MarketInstrument::ASSET_FOREX,
            MarketInstrument::ASSET_CRYPTO,
        ], true)) {
            abort(404);
        }

        return MarketInstrument::query()
            ->where('asset_class', $assetClass)
            ->where('symbol', strtoupper(trim($symbol)))
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function safeAnalysis(
        MarketInstrument $instrument,
        MarketInstrumentAnalysisService $analysisService,
        MarketPriceRouter $prices,
        string $marketplace
    ): array {
        try {
            return $analysisService->forInstrument($instrument, $marketplace);
        } catch (\Throwable $e) {
            try {
                $price = $prices->price($instrument, $marketplace);
            } catch (\Throwable) {
                $price = 0.0;
            }

            return [
                'market_instrument_id' => $instrument->id,
                'asset_class' => $instrument->asset_class,
                'symbol' => $instrument->symbol,
                'display_symbol' => $instrument->display_symbol,
                'label' => $instrument->name,
                'marketplace' => $marketplace,
                'price_precision' => (int) $instrument->price_precision,
                'quote_asset' => $instrument->quote_asset,
                'source' => 'adapter_pending',
                'series' => [],
                'timeframes' => [],
                'current_price' => $price,
                'previous_close' => $price,
                'has_chart' => false,
                'trend' => 'Unavailable',
                'momentum_percent' => 0,
                'momentum_label' => 'Unavailable',
                'support' => null,
                'resistance' => null,
                'sma20' => null,
                'sma50' => null,
                'sma200' => null,
                'risk_reward' => 'Unavailable',
                'analysis_error' => $e->getMessage(),
            ];
        }
    }
}
