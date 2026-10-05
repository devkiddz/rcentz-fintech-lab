<?php

namespace App\Http\Controllers;

use App\Models\BrokerOrder;
use App\Models\CopyStrategy;
use App\Models\MarketExecutionTransaction;
use App\Models\MarketHolding;
use App\Models\MarketInstrument;
use App\Models\StockHolding;
use App\Models\TradePosition;
use App\Services\BrokerOrderService;
use App\Services\BrokerPortfolioService;
use App\Services\BrokerPositionService;
use App\Services\CopyTradingService;
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
        if (request()->query('entry_estimate') === '1') {
            $input = request()->validate([
                'side'=>'required|in:buy,sell', 'quantity'=>'required|numeric|gt:0|max:1000000000000',
                'quantity_mode'=>'required|in:units,lots,settlement_amount',
            ]);
            try {
                $estimate = app(\App\Services\PaperTrading\PaperBrokerService::class)->estimate(
                    Auth::user(), $instrument, $input['side'], (float)$input['quantity'], $input['quantity_mode']);
                return response()->json(['success'=>true,'estimate'=>$estimate])->header('Cache-Control','private, no-store');
            } catch (\RuntimeException|\InvalidArgumentException $error) {
                if ($error instanceof \Illuminate\Database\QueryException) { throw $error; }
                return response()->json(['success'=>false,'message'=>$error->getMessage()],422)->header('Cache-Control','private, no-store');
            }
        }
        $instrumentOptions = MarketInstrument::query()->where('is_active', true)
            ->where('asset_class', $instrument->asset_class)
            ->orderBy('display_symbol')
            ->get(['id','asset_class','symbol','display_symbol','name']);
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
        if (config('paper_trading.enabled')) {
            try {
                $quotes = app(\App\Services\PaperTrading\PaperQuoteService::class);
                $quotes->execution($instrument, 'buy', $marketplace);
                $quotes->execution($instrument, 'sell', $marketplace);
                $executionReady = true;
            } catch (\Throwable) { $executionReady = false; }
        }
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

        $positions = TradePosition::query()->with('entryMarketExecutionTransaction')
            ->where('user_id', $user->id)
            ->where('market_instrument_id', $instrument->id)
            ->where('marketplace', $marketplace)
            ->whereIn('status', ['open', 'exit_queued'])
            ->where('open_quantity', '>', 0)
            ->latest('opened_at')
            ->get();

        foreach ($positions as $position) {
            $position->setAttribute('runtime_valuation', app(\App\Services\PaperTrading\PositionPresentationService::class)->describe($position));
            $position->setAttribute('runtime_mark_price', $position->runtime_valuation['cmp']);
        }

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
            MarketInstrument::ASSET_COMMODITY => ['units' => 'Troy ounces'],
            default => [],
        };

        $copyStrategies = CopyStrategy::query()
            ->with('profile')
            ->where('is_active', true)
            ->whereHas('profile', fn ($q) => $q
                ->where('user_id', $user->id)
                ->whereNotNull('approved_at'))
            ->orderBy('name')
            ->get();

        return view('broker.workstation', compact(
            'instrument',
            'instrumentOptions',
            'analysis',
            'marketplace',
            'capabilities',
            'executionReady',
            'wallet',
            'holding',
            'positions',
            'recentOrders',
            'quantityModes',
            'copyStrategies'
        ) + ['idempotencyKey' => (string) Str::uuid()]);
    }

    public function submitOrder(
        Request $request,
        string $assetClass,
        string $symbol,
        BrokerOrderService $orders,
        CopyTradingService $copyTrading
    ) {
        $instrument = $this->resolveInstrument($assetClass, $symbol);
        $user = Auth::user();

        $data = $request->validate([
            'side' => 'required|in:buy,sell',
            'quantity' => 'required|numeric|min:0.00000001|max:1000000000000',
            'quantity_mode' => 'required|in:units,lots,settlement_amount',
            'idempotency_key' => 'required|string|max:120',
            'stop_loss_price' => 'nullable|numeric|min:0.00000001|max:1000000000000',
            'take_profit_price' => 'nullable|numeric|min:0.00000001|max:1000000000000',
            'stop_loss_percent' => 'nullable|numeric|min:0.01|max:99.99',
            'take_profit_percent' => 'nullable|numeric|min:0.01|max:99.99',
            'duration_minutes' => 'nullable|integer|min:1|max:43200',
            'copy_strategy_id' => 'nullable|integer|exists:copy_strategies,id',
        ]);

        if (!config('paper_trading.enabled') && (isset($data['stop_loss_price']) || isset($data['take_profit_price']))) {
            return $this->tradeFailure($request, 'order', 'Exact risk prices require the new position engine.');
        }

        $strategy = $this->resolveCopyStrategy($user, isset($data['copy_strategy_id']) ? (int) $data['copy_strategy_id'] : null);
        $marketplace = app(MarketPriceRouter::class)->activeMarketplace();

        if (!config('paper_trading.enabled') && $strategy && $data['side'] === 'sell') {
            if ($data['quantity_mode'] !== 'units') {
                return $this->tradeFailure($request, 'order', 'Copy-strategy exits use asset/base units so the sale cannot exceed strategy-attributed exposure.');
            }

            $available = (float) TradePosition::query()
                ->where('user_id', $user->id)
                ->where('market_instrument_id', $instrument->id)
                ->where('marketplace', $marketplace)
                ->where('context_type', 'copy_strategy')
                ->where('context_id', $strategy->id)
                ->whereIn('status', ['open', 'exit_queued'])
                ->where('open_quantity', '>', 0)
                ->sum('open_quantity');

            if ((float) $data['quantity'] > $available + 0.00000001) {
                return $this->tradeFailure($request, 'order', 'Copy-strategy sell quantity exceeds the strategy-attributed open exposure.');
            }
        }

        $risk = (config('paper_trading.enabled') || $data['side'] === 'buy')
            ? array_filter([
                'stop_loss_price' => $data['stop_loss_price'] ?? null,
                'take_profit_price' => $data['take_profit_price'] ?? null,
                'stop_loss_percent' => $data['stop_loss_percent'] ?? null,
                'take_profit_percent' => $data['take_profit_percent'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
            ], fn ($value) => $value !== null && $value !== '')
            : [];

        $context = [];
        if ($strategy) {
            $risk['metadata'] = array_merge($risk['metadata'] ?? [], [
                'copy_strategy_id' => $strategy->id,
            ]);
            $context = [
                'execution_source' => 'copy_strategy',
                'execution_source_id' => $strategy->id,
                'context_type' => 'copy_strategy',
                'context_id' => $strategy->id,
                'actor_type' => 'user',
                'actor_id' => $user->id,
                'exit_reason' => 'provider_exit',
                'metadata' => [
                    'copy_strategy_id' => $strategy->id,
                ],
            ];
        }

        try {
            $order = $orders->placeMarketOrder(
                $user,
                $instrument,
                $data['side'],
                (float) $data['quantity'],
                $data['quantity_mode'],
                $data['idempotency_key'],
                $risk,
                $context
            );

            if ($order->status === BrokerOrder::STATUS_FAILED) {
                throw new RuntimeException($order->failure_message ?: 'The order failed closed. Submit a new order to retry.');
            }
        } catch (\Throwable $e) {
            return $this->tradeFailure($request, 'order', $this->publicFailure($e));
        }

        if ($strategy && $order->status === BrokerOrder::STATUS_FILLED) {
            try {
                $order->loadMissing('execution');
                if ($order->execution) {
                    $copyTrading->mirrorCompletedExecution($order->execution, $strategy->id);
                }
            } catch (\Throwable $e) {
                \Log::warning('Provider BrokerOrder completed but copy mirroring encountered an error.', [
                    'broker_order_id' => $order->id,
                    'copy_strategy_id' => $strategy->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($request->expectsJson()) { return $this->tradeResult($order, strtoupper($order->side).' order '.strtolower($order->status).'.'); }

        return redirect()
            ->route('broker.workstation', ['assetClass' => $instrument->asset_class, 'symbol' => $instrument->symbol])
            ->with('success', strtoupper($order->side).' order '.strtolower($order->status).'.')
            ->with('last_trade_order', $order->public_id);
    }

    public function portfolio(BrokerPortfolioService $portfolio)
    {
        return view('broker.portfolio', $portfolio->build(Auth::user()));
    }

    public function orders()
    {
        $orders = BrokerOrder::query()
            ->with(['marketInstrument', 'execution.tradePosition'])
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
            $row = app(\App\Services\PaperTrading\PositionPresentationService::class)->describe($position);
            $position->setAttribute('runtime_valuation', $row);
            $position->setAttribute('runtime_mark_price', $row['cmp']);
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
            'stop_loss_price' => 'nullable|numeric|min:0.00000001|max:1000000000000',
            'take_profit_price' => 'nullable|numeric|min:0.00000001|max:1000000000000',
            'stop_loss_percent' => 'nullable|numeric|min:0.01|max:99.99',
            'take_profit_percent' => 'nullable|numeric|min:0.01|max:99.99',
            'duration_minutes' => 'nullable|integer|min:1|max:43200',
        ]);

        try {
            $positions->updateRisk(
                Auth::user(),
                $position,
                isset($data['stop_loss_percent']) ? (float) $data['stop_loss_percent'] : null,
                isset($data['take_profit_percent']) ? (float) $data['take_profit_percent'] : null,
                isset($data['duration_minutes']) ? (int) $data['duration_minutes'] : null,
                isset($data['stop_loss_price']) ? (float)$data['stop_loss_price'] : null,
                isset($data['take_profit_price']) ? (float)$data['take_profit_price'] : null
            );
        } catch (\Throwable $e) {
            return $this->tradeFailure($request, 'position', $this->publicFailure($e));
        }

        if ($request->expectsJson()) { return response()->json(['success'=>true,'message'=>'Position risk controls updated.']); }

        return back()->with('success', 'Position risk controls updated.');
    }

    public function closePosition(
        Request $request,
        TradePosition $position,
        BrokerPositionService $positions,
        CopyTradingService $copyTrading
    ) {
        abort_unless((int) $position->user_id === (int) Auth::id(), 403);

        $data = $request->validate([
            'quantity' => 'nullable|numeric|min:0.00000001',
            'idempotency_key' => 'required|string|max:120',
        ]);

        $strategyId = $position->context_type === 'copy_strategy' && (int) $position->context_id > 0
            ? (int) $position->context_id
            : null;

        $context = $strategyId ? [
            'execution_source' => 'copy_strategy',
            'execution_source_id' => $strategyId,
            'context_type' => 'copy_strategy',
            'context_id' => $strategyId,
            'actor_type' => 'user',
            'actor_id' => Auth::id(),
            'exit_reason' => 'provider_exit',
            'metadata' => ['copy_strategy_id' => $strategyId],
        ] : [];

        try {
            $order = $positions->close(
                Auth::user(),
                $position,
                isset($data['quantity']) ? (float) $data['quantity'] : null,
                $data['idempotency_key'],
                $context
            );

            if ($order->status === BrokerOrder::STATUS_FAILED) {
                throw new RuntimeException($order->failure_message ?: 'The position close failed closed.');
            }
        } catch (\Throwable $e) {
            return $this->tradeFailure($request, 'position', $this->publicFailure($e));
        }

        if ($strategyId && $order->status === BrokerOrder::STATUS_FILLED) {
            try {
                $order->loadMissing('execution');
                if ($order->execution) {
                    $copyTrading->mirrorCompletedExecution($order->execution, $strategyId);
                }
            } catch (\Throwable $e) {
                \Log::warning('Provider position close completed but copy mirroring encountered an error.', [
                    'broker_order_id' => $order->id,
                    'copy_strategy_id' => $strategyId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($request->expectsJson()) { return $this->tradeResult($order, 'Position close order '.strtolower($order->status).'.'); }

        return back()
            ->with('success', 'Position close order '.strtolower($order->status).'.')
            ->with('last_trade_order', $order->public_id);
    }

    private function tradeFailure(Request $request, string $field, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['success'=>false,'message'=>$message,'errors'=>[$field=>[$message]]],422);
        }
        return back()->withErrors([$field=>$message])->withInput($request->except('idempotency_key'));
    }

    private function tradeResult(BrokerOrder $order, string $message)
    {
        return response()->json([
            'success'=>true, 'message'=>$message, 'status'=>$order->status,
            'receipt_url'=>route('broker.orders.show',['publicId'=>$order->public_id]),
            'next_idempotency_key'=>(string) Str::uuid(),
        ]);
    }

    private function publicFailure(\Throwable $error): string
    {
        if ($error instanceof RuntimeException && !($error instanceof \Illuminate\Database\QueryException)
            && !($error instanceof \PDOException)) {
            return $error->getMessage() ?: 'The trade could not be completed.';
        }
        \Log::warning('Broker request failed', ['error_class'=>get_class($error)]);
        return 'The trade could not be completed. Please retry or contact support.';
    }

    private function resolveCopyStrategy($user, ?int $strategyId): ?CopyStrategy
    {
        if (! $strategyId) {
            return null;
        }

        $strategy = CopyStrategy::query()->with('profile')->findOrFail($strategyId);
        abort_unless(
            (int) $strategy->profile?->user_id === (int) $user->id
            && $strategy->profile?->approved_at
            && $strategy->is_active,
            403,
            'This copy strategy is not available for provider execution.'
        );

        return $strategy;
    }

    private function resolveInstrument(string $assetClass, string $symbol): MarketInstrument
    {
        $assetClass = strtolower(trim($assetClass));
        if (! in_array($assetClass, [
            MarketInstrument::ASSET_STOCK,
            MarketInstrument::ASSET_FOREX,
            MarketInstrument::ASSET_CRYPTO,
            MarketInstrument::ASSET_COMMODITY,
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
