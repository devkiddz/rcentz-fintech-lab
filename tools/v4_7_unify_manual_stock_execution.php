<?php

$path = __DIR__.'/../app/Http/Controllers/TradingController.php';

if (! file_exists($path)) {
    throw new RuntimeException('TradingController.php not found.');
}

$text = file_get_contents($path);

if (! str_contains($text, 'use App\Services\StockTradeExecutor;')) {
    $anchor = 'use App\Services\StockAnalysisService;';
    if (! str_contains($text, $anchor)) {
        throw new RuntimeException('Could not find StockAnalysisService import anchor.');
    }

    $text = str_replace(
        $anchor,
        $anchor.PHP_EOL.'use App\Services\StockTradeExecutor;',
        $text,
        $importCount
    );

    if ($importCount !== 1) {
        throw new RuntimeException("Unexpected import replacement count: {$importCount}");
    }
}

$buyMethod = <<<'PHP'
    public function executeBuy(
        Request $request,
        Stock $stock,
        StockTradeExecutor $executor,
        CopyTradingService $copyTrading,
        StockTradePlanService $tradePlans
    ) {
        if (! $stock->is_active) {
            abort(404);
        }

        $data = $request->validate([
            'quantity' => 'required|numeric|min:1|max:10000',
            'plan_duration_minutes' => 'nullable|integer|min:0|max:10080',
            'plan_mode' => 'nullable|in:reminder,automatic',
        ]);

        $user = Auth::user();
        $quantity = (float) $data['quantity'];

        try {
            // Single source of truth for wallet mutation, holding mutation,
            // stock transaction creation and financial activity.
            $stockTransaction = $executor->buy(
                $user,
                $stock,
                $quantity,
                'stock_trade'
            );
        } catch (\Throwable $e) {
            \Log::warning('Manual stock buy failed', [
                'user_id' => $user->id,
                'stock_id' => $stock->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'error' => $e->getMessage() ?: 'Failed to process purchase. Please try again.',
            ])->withInput();
        }

        // Everything below is post-execution side effect. A notification,
        // email, copy mirror or trade-plan failure must never roll back a
        // completed financial execution.
        try {
            NotificationService::createInvestmentSuccessNotification(
                $user,
                $stock->symbol,
                (float) $stockTransaction->total_amount
            );
        } catch (\Throwable $e) {
            \Log::warning('Stock buy notification failed', [
                'trade_id' => $stockTransaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $totalPortfolioValue = (float) $user->stockHoldings()->sum('current_value');
            Mail::to($user->email)->send(
                new StockBuyEmail($user, $stockTransaction, $totalPortfolioValue)
            );
        } catch (\Throwable $e) {
            \Log::warning('Stock buy email failed', [
                'trade_id' => $stockTransaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $copyTrading->mirrorCompletedTrade($stockTransaction);
        } catch (\Throwable $e) {
            \Log::warning('Copy mirroring failed after provider buy', [
                'trade_id' => $stockTransaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $tradePlans->createForTransaction(
                $stockTransaction,
                $request->only(['plan_duration_minutes', 'plan_mode'])
            );
        } catch (\Throwable $e) {
            \Log::warning('Trade plan creation failed after stock buy', [
                'trade_id' => $stockTransaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('trading.portfolio')
            ->with(
                'success',
                'Successfully purchased '.number_format($quantity, 6).' shares of '.
                $stock->symbol.' for '.currency_symbol().number_format((float) $stockTransaction->total_amount, 2).'.'
            );
    }
PHP;

$sellMethod = <<<'PHP'
    public function executeSell(
        Request $request,
        Stock $stock,
        StockTradeExecutor $executor,
        CopyTradingService $copyTrading,
        StockTradePlanService $tradePlans
    ) {
        if (! $stock->is_active) {
            abort(404);
        }

        $data = $request->validate([
            'quantity' => 'required|numeric|min:1|max:10000',
            'plan_duration_minutes' => 'nullable|integer|min:0|max:10080',
            'plan_mode' => 'nullable|in:reminder,automatic',
        ]);

        $user = Auth::user();
        $quantity = (float) $data['quantity'];

        try {
            // StockTradeExecutor locks the holding and wallet inside the same
            // database transaction and validates the final available quantity.
            $stockTransaction = $executor->sell(
                $user,
                $stock,
                $quantity,
                'stock_trade'
            );
        } catch (\Throwable $e) {
            \Log::warning('Manual stock sell failed', [
                'user_id' => $user->id,
                'stock_id' => $stock->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'error' => $e->getMessage() ?: 'Failed to process sale. Please try again.',
            ])->withInput();
        }

        try {
            NotificationService::createInvestmentSuccessNotification(
                $user,
                $stock->symbol.' (Sale)',
                (float) $stockTransaction->total_amount
            );
        } catch (\Throwable $e) {
            \Log::warning('Stock sell notification failed', [
                'trade_id' => $stockTransaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $totalPortfolioValue = (float) $user->stockHoldings()->sum('current_value');
            Mail::to($user->email)->send(
                new StockSellEmail($user, $stockTransaction, $totalPortfolioValue)
            );
        } catch (\Throwable $e) {
            \Log::warning('Stock sell email failed', [
                'trade_id' => $stockTransaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $copyTrading->mirrorCompletedTrade($stockTransaction);
        } catch (\Throwable $e) {
            \Log::warning('Copy mirroring failed after provider sale', [
                'trade_id' => $stockTransaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $tradePlans->createForTransaction(
                $stockTransaction,
                $request->only(['plan_duration_minutes', 'plan_mode'])
            );
        } catch (\Throwable $e) {
            \Log::warning('Trade plan creation failed after stock sell', [
                'trade_id' => $stockTransaction->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('trading.portfolio')
            ->with(
                'success',
                'Successfully sold '.number_format($quantity, 6).' shares of '.
                $stock->symbol.' for '.currency_symbol().number_format((float) $stockTransaction->total_amount, 2).'.'
            );
    }
PHP;

$buyPattern = '/    public function executeBuy\(.*?^    }\R\R    \/\*\*\R     \* Show sell stock form/ms';
if (! preg_match($buyPattern, $text)) {
    throw new RuntimeException('Could not locate executeBuy() method block.');
}
$text = preg_replace(
    $buyPattern,
    $buyMethod.PHP_EOL.PHP_EOL."    /**\n     * Show sell stock form",
    $text,
    1,
    $buyCount
);
if ($buyCount !== 1) {
    throw new RuntimeException("Unexpected executeBuy replacement count: {$buyCount}");
}

$sellPattern = '/    public function executeSell\(.*?^    }\R\R    \/\*\*\R     \* Show stock portfolio/ms';
if (! preg_match($sellPattern, $text)) {
    throw new RuntimeException('Could not locate executeSell() method block.');
}
$text = preg_replace(
    $sellPattern,
    $sellMethod.PHP_EOL.PHP_EOL."    /**\n     * Show stock portfolio",
    $text,
    1,
    $sellCount
);
if ($sellCount !== 1) {
    throw new RuntimeException("Unexpected executeSell replacement count: {$sellCount}");
}

file_put_contents($path, $text);

echo "Manual stock buy/sell now use StockTradeExecutor.\n";
