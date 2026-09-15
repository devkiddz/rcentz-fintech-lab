<?php

function replaceOnce(string $text,string $old,string $new,string $label): string {
    if (str_contains($text,$new)) return $text;
    if (! str_contains($text,$old)) throw new RuntimeException("Anchor not found: {$label}");
    $count=0;
    $text=str_replace($old,$new,$text,$count);
    if($count!==1) throw new RuntimeException("Unexpected replacement count {$count}: {$label}");
    return $text;
}

// ---- TradingController ----
$path=__DIR__.'/../app/Http/Controllers/TradingController.php';
$text=file_get_contents($path);

$text=replaceOnce(
    $text,
    "use App\\Services\\StockTradeExecutor;\n",
    "use App\\Services\\StockTradeExecutor;\nuse App\\Services\\TradePositionService;\nuse App\\Models\\TradePosition;\n",
    'TradingController imports'
);

$text=replaceOnce(
    $text,
    "        StockTradePlanService \$tradePlans\n    ) {",
    "        StockTradePlanService \$tradePlans,\n        TradePositionService \$positions\n    ) {",
    'executeBuy signature'
);

$text=replaceOnce(
    $text,
    "            'plan_mode' => 'nullable|in:reminder,automatic',\n",
    "            'plan_mode' => 'nullable|in:reminder,automatic',\n            'stop_loss_percent' => 'nullable|numeric|min:0.01|max:100',\n            'take_profit_percent' => 'nullable|numeric|min:0.01|max:100',\n",
    'buy risk validation'
);

$oldBuyPlan=<<<'PHP'
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
PHP;

$newBuyPlan=<<<'PHP'
        try {
            $positions->openLongFromTrade(
                $stockTransaction,
                [
                    'stop_loss_percent' => $request->input('stop_loss_percent'),
                    'take_profit_percent' => $request->input('take_profit_percent'),
                    // Existing Trade Horizon duration becomes MAXIMUM EXPOSURE.
                    // Once reached, the position is sold automatically when the market is open.
                    'duration_minutes' => $request->integer('plan_duration_minutes') ?: null,
                ],
                'manual_trade',
                null,
                null,
                'user',
                $user->id
            );
        } catch (\Throwable $e) {
            \Log::warning('Position creation failed after stock buy', [
                'trade_id' => $stockTransaction->id,
                'error' => $e->getMessage(),
            ]);
        }
PHP;
$text=replaceOnce($text,$oldBuyPlan,$newBuyPlan,'replace buy trade plan with position');

# sell signature is identical later; replace second occurrence safely after show sell marker
$marker="    public function executeSell(";
$pos=strpos($text,$marker);
if($pos===false) throw new RuntimeException('executeSell not found');
$head=substr($text,0,$pos);
$tail=substr($text,$pos);
$tail=replaceOnce(
    $tail,
    "        StockTradePlanService \$tradePlans\n    ) {",
    "        StockTradePlanService \$tradePlans,\n        TradePositionService \$positions\n    ) {",
    'executeSell signature'
);

$oldSellPlan=<<<'PHP'
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
PHP;

$newSellPlan=<<<'PHP'
        try {
            // A normal SELL is an exit event. Consume the user's open positions
            // FIFO instead of creating a second disconnected timed plan.
            $positions->consumeSellTransaction(
                $stockTransaction,
                'manual_close',
                null,
                null,
                'user',
                $user->id
            );
        } catch (\Throwable $e) {
            \Log::warning('Position reconciliation failed after manual sell', [
                'trade_id' => $stockTransaction->id,
                'error' => $e->getMessage(),
            ]);
        }
PHP;
$tail=replaceOnce($tail,$oldSellPlan,$newSellPlan,'replace sell trade plan with position reconciliation');
$text=$head.$tail;

# Portfolio now loads positions
$oldPortfolio=<<<'PHP'
        $tradePlans = StockTradePlan::with('stock')
            ->where('user_id', $user->id)
            ->whereIn('status', ['active','due'])
            ->orderBy('due_at')
            ->get();
PHP;
$newPortfolio=<<<'PHP'
        $tradePlans = collect(); // legacy plans retained in DB but new entries use TradePosition.
        $positions = TradePosition::with(['stock','events' => fn ($q) => $q->latest()->limit(3)])
            ->where('user_id', $user->id)
            ->whereIn('status', ['open','exit_queued'])
            ->orderBy('expires_at')
            ->get();
PHP;
$text=replaceOnce($text,$oldPortfolio,$newPortfolio,'portfolio positions');
$text=replaceOnce(
    $text,
    "            'tradePlans'\n        ));",
    "            'tradePlans',\n            'positions'\n        ));",
    'portfolio compact'
);
file_put_contents($path,$text);

// ---- Routes ----
$path=__DIR__.'/../routes/web.php';
$text=file_get_contents($path);
$anchor="        Route::delete('/trading/plans/{plan}', [StockTradePlanController::class, 'cancel'])->name('trading.plans.cancel');";
$block=$anchor."\n".
"        Route::get('/trading/positions', [\\App\\Http\\Controllers\\TradePositionController::class, 'index'])->name('trading.positions.index');\n".
"        Route::patch('/trading/positions/{position}/risk', [\\App\\Http\\Controllers\\TradePositionController::class, 'updateRisk'])->name('trading.positions.risk');\n".
"        Route::post('/trading/positions/{position}/close', [\\App\\Http\\Controllers\\TradePositionController::class, 'close'])->name('trading.positions.close');\n".
"        Route::post('/trading/positions/{position}/partial-close', [\\App\\Http\\Controllers\\TradePositionController::class, 'partialClose'])->name('trading.positions.partial-close');\n".
"        Route::post('/trading/positions/{position}/reenter', [\\App\\Http\\Controllers\\TradePositionController::class, 'reenter'])->name('trading.positions.reenter');";
if(!str_contains($text,"trading.positions.reenter")){
    $text=replaceOnce($text,$anchor,$block,'position routes');
}
file_put_contents($path,$text);

// ---- Scheduler ----
$path=__DIR__.'/../routes/console.php';
$text=file_get_contents($path);
if(!str_contains($text,"trade-positions:process")){
    $text .= "\n// Position engine: SL / TP / time-stop / queued exits.\n".
             "Schedule::command('trade-positions:process')->everyMinute()->withoutOverlapping();\n";
}
file_put_contents($path,$text);

// ---- Trading Performance: relationship metrics now come from attributed positions ----
$path=__DIR__.'/../app/Services/TradingPerformanceService.php';
$text=file_get_contents($path);
$pattern='/    public function copyRelationship\(CopyRelationship \$relationship\): array\n    \{.*?\n    \}\n\n    \/\*\*/s';
$replacement=<<<'PHP'
    public function copyRelationship(CopyRelationship $relationship): array
    {
        $executions = $relationship->executions()->with('followerTrade.stock')->get();

        $positions = \App\Models\TradePosition::with('stock')
            ->where('context_type', 'copy_relationship')
            ->where('context_id', $relationship->id)
            ->get();

        if ($positions->isNotEmpty()) {
            $realized = (float) $positions->sum('realized_profit_loss');
            $open = 0.0;
            $basis = 0.0;
            $positive = 0;
            $negative = 0;
            $neutral = 0;

            foreach ($positions as $position) {
                $entry = (float) $position->entry_price;
                $initial = (float) $position->initial_quantity;
                $openQty = (float) $position->open_quantity;
                $basis += $entry * $initial;

                $positionPnl = (float) $position->realized_profit_loss;
                if ($openQty > 0 && $position->stock) {
                    $mark = ((float) $position->stock->current_price - $entry) * $openQty;
                    $open += $mark;
                    $positionPnl += $mark;
                }

                $this->countMark($positionPnl, $positive, $negative, $neutral);
            }

            $total = $realized + $open;
            $completed = $executions->where('status','completed');
            $volume = (float) $completed->sum('executed_amount');

            $metrics = $this->summary(
                $executions,
                $completed,
                $volume,
                $total,
                $positive,
                $negative,
                $neutral,
                (float) $relationship->allocation_limit,
                [
                    'realized_profit_loss'=>$realized,
                    'open_profit_loss'=>$open,
                    'performance_basis'=>$basis,
                    'return_percent'=>$basis > 0 ? ($total/$basis)*100 : 0,
                    'realized_return_percent'=>$basis > 0 ? ($realized/$basis)*100 : 0,
                    'open_return_percent'=>$basis > 0 ? ($open/$basis)*100 : 0,
                ]
            );
        } else {
            $metrics = $this->fromCopyExecutions($executions, (float) $relationship->allocation_limit);
        }

        $metrics['copy_ratio_percent'] = (float) $relationship->copy_ratio_percent;
        $relationship->loadMissing('strategy');

        return $this->applyManualPerformance(
            $metrics,
            (bool) $relationship->strategy?->use_manual_performance,
            $relationship->strategy?->manual_profit_loss,
            $relationship->strategy?->manual_return_percent,
            $relationship->strategy?->manual_performance_label,
            $relationship->strategy?->manual_performance_note
        );
    }

    /**
PHP;
$new=preg_replace($pattern,$replacement,$text,1,$count);
if($count!==1) throw new RuntimeException("TradingPerformanceService copyRelationship patch failed: {$count}");
file_put_contents($path,$new);

// ---- Buy form: add SL / TP inputs beside existing horizon without rebuilding page ----
$path=__DIR__.'/../resources/views/trading/buy.blade.php';
$text=file_get_contents($path);
if(!str_contains($text,'name="stop_loss_percent"')){
    $needle='<div class="mt-4 border-t border-border pt-4">';
    $risk=<<<'BLADE'
<div class="mt-4 rounded-xl border border-border bg-muted/10 p-3">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Risk management</p>
            <h3 class="mt-1 text-xs font-semibold">Stop loss + take profit</h3>
        </div>
        <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-1 text-[9px] font-semibold text-emerald-600">First trigger wins</span>
    </div>
    <div class="mt-3 grid grid-cols-2 gap-2">
        <div><label class="ui-label">Stop loss %</label><input name="stop_loss_percent" type="number" min="0.01" max="100" step="0.01" class="ui-input w-full" placeholder="e.g. 2"></div>
        <div><label class="ui-label">Take profit %</label><input name="take_profit_percent" type="number" min="0.01" max="100" step="0.01" class="ui-input w-full" placeholder="e.g. 5"></div>
    </div>
    <p class="mt-2 text-[9px] leading-4 text-muted-foreground">The selected Trade Horizon is now the maximum exposure time. Stop loss, take profit, manual close or time expiry — whichever happens first — closes the position.</p>
</div>

BLADE;
    if(!str_contains($text,$needle)) throw new RuntimeException('Buy view horizon anchor missing');
    $text=str_replace($needle,$risk.$needle,$text,$count);
    if($count!==1) throw new RuntimeException("Buy view risk insertion count: {$count}");
}
file_put_contents($path,$text);

// ---- Portfolio: add Managed Positions entry point and open-position summary ----
$path=__DIR__.'/../resources/views/trading/portfolio.blade.php';
$text=file_get_contents($path);
if(!str_contains($text,"route('trading.positions.index')")){
    $needle='<a href="{{ route(\'stocks.index\') }}" class="ui-btn ui-btn-primary"><i data-lucide="plus" class="h-4 w-4"></i> Browse Stocks</a>';
    $new='<div class="flex gap-2"><a href="{{ route(\'trading.positions.index\') }}" class="ui-btn ui-btn-secondary"><i data-lucide="route" class="h-4 w-4"></i> Positions</a>'.$needle.'</div>';
    $text=replaceOnce($text,$needle,$new,'portfolio positions button');
}
file_put_contents($path,$text);

echo "V5.0 Position Engine wiring complete.\n";
