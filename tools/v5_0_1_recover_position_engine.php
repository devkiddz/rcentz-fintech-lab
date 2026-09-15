<?php

function methodSlice(string $text, string $method): array {
    $needle = "    public function {$method}(";
    $start = strpos($text, $needle);
    if ($start === false) throw new RuntimeException("Method not found: {$method}");
    $next = strpos($text, "\n    public function ", $start + strlen($needle));
    if ($next === false) $next = strlen($text);
    return [$start, $next, substr($text, $start, $next - $start)];
}

function replaceMethod(string $text,string $method,callable $patch): string {
    [$start,$end,$slice]=methodSlice($text,$method);
    $patched=$patch($slice);
    return substr($text,0,$start).$patched.substr($text,$end);
}

function replaceOnce(string $text,string $old,string $new,string $label): string {
    if (str_contains($text,$new)) return $text;
    $pos=strpos($text,$old);
    if($pos===false) throw new RuntimeException("Anchor not found: {$label}");
    return substr($text,0,$pos).$new.substr($text,$pos+strlen($old));
}

// ---------- TradingController ----------
$path=__DIR__.'/../app/Http/Controllers/TradingController.php';
$text=file_get_contents($path);

if(!str_contains($text,'use App\Services\TradePositionService;')){
    $text=replaceOnce(
        $text,
        "use App\\Services\\StockTradeExecutor;\n",
        "use App\\Services\\StockTradeExecutor;\nuse App\\Services\\TradePositionService;\nuse App\\Models\\TradePosition;\n",
        'TradingController imports'
    );
}

$text=replaceMethod($text,'executeBuy',function($m){
    if(!str_contains($m,'TradePositionService $positions')){
        $m=str_replace(
            "        StockTradePlanService \$tradePlans\n    ) {",
            "        StockTradePlanService \$tradePlans,\n        TradePositionService \$positions\n    ) {",
            $m
        );
    }

    if(!str_contains($m,"'stop_loss_percent'")){
        $m=str_replace(
            "            'plan_mode' => 'nullable|in:reminder,automatic',\n",
            "            'plan_mode' => 'nullable|in:reminder,automatic',\n".
            "            'stop_loss_percent' => 'nullable|numeric|min:0.01|max:100',\n".
            "            'take_profit_percent' => 'nullable|numeric|min:0.01|max:100',\n",
            $m
        );
    }

    if(!str_contains($m,"'manual_trade'")){
        $anchor="        // Everything below is post-execution side effect.";
        $block=<<<'PHP'
        try {
            // BUY creates the living position BEFORE mirroring so strategy/copy
            // followers can inherit its risk and timing context.
            $positions->openLongFromTrade(
                $stockTransaction,
                [
                    'stop_loss_percent' => $request->input('stop_loss_percent'),
                    'take_profit_percent' => $request->input('take_profit_percent'),
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
        if(!str_contains($m,$anchor)) throw new RuntimeException('executeBuy side-effect anchor missing');
        $m=str_replace($anchor,$block.$anchor,$m);
    }

    // Remove legacy post-buy trade-plan creation. New buys use TradePosition.
    $m=preg_replace(
        '/\n        try \{\n            \$tradePlans->createForTransaction\(\n                \$stockTransaction,\n                \$request->only\(\[\'plan_duration_minutes\', \'plan_mode\'\]\)\n            \);\n        \} catch \(\\\\Throwable \$e\) \{\n            \\\\Log::warning\(\'Trade plan creation failed after stock buy\', \[\n                \'trade_id\' => \$stockTransaction->id,\n                \'error\' => \$e->getMessage\(\),\n            \]\);\n        \}\n/s',
        "\n",
        $m
    );

    return $m;
});

$text=replaceMethod($text,'executeSell',function($m){
    if(!str_contains($m,'TradePositionService $positions')){
        $m=str_replace(
            "        StockTradePlanService \$tradePlans\n    ) {",
            "        StockTradePlanService \$tradePlans,\n        TradePositionService \$positions\n    ) {",
            $m
        );
    }

    if(!str_contains($m,'consumeSellTransaction')){
        $anchor="        try {\n            NotificationService::createInvestmentSuccessNotification(";
        $block=<<<'PHP'
        try {
            // Reconcile the SELL with open positions BEFORE copy mirroring.
            // This gives provider exits a concrete source position.
            $positions->consumeSellTransaction(
                $stockTransaction,
                'manual_close',
                null,
                null,
                'user',
                $user->id
            );
            $stockTransaction->refresh();
        } catch (\Throwable $e) {
            \Log::warning('Position reconciliation failed after manual sell', [
                'trade_id' => $stockTransaction->id,
                'error' => $e->getMessage(),
            ]);
        }

PHP;
        if(!str_contains($m,$anchor)) throw new RuntimeException('executeSell notification anchor missing');
        $m=str_replace($anchor,$block.$anchor,$m);
    }

    $m=preg_replace(
        '/\n        try \{\n            \$tradePlans->createForTransaction\(\n                \$stockTransaction,\n                \$request->only\(\[\'plan_duration_minutes\', \'plan_mode\'\]\)\n            \);\n        \} catch \(\\\\Throwable \$e\) \{\n            \\\\Log::warning\(\'Trade plan creation failed after stock sell\', \[\n                \'trade_id\' => \$stockTransaction->id,\n                \'error\' => \$e->getMessage\(\),\n            \]\);\n        \}\n/s',
        "\n",
        $m
    );

    return $m;
});

$text=replaceMethod($text,'portfolio',function($m){
    if(!str_contains($m,'$positions = TradePosition::')){
        $legacy=<<<'PHP'
        $tradePlans = StockTradePlan::with('stock')
            ->where('user_id', $user->id)
            ->whereIn('status', ['active','due'])
            ->orderBy('due_at')
            ->get();
PHP;
        $modern=<<<'PHP'
        $tradePlans = collect(); // Legacy rows stay readable; new entries use TradePosition.
        $positions = TradePosition::with(['stock','events' => fn ($q) => $q->latest()->limit(3)])
            ->where('user_id', $user->id)
            ->whereIn('status', ['open','exit_queued'])
            ->orderBy('expires_at')
            ->get();
PHP;
        if(!str_contains($m,$legacy)) throw new RuntimeException('Portfolio legacy plan block missing');
        $m=str_replace($legacy,$modern,$m);
    }
    if(!str_contains($m,"'positions'")){
        $m=str_replace("'tradePlans'\n        ));","'tradePlans',\n            'positions'\n        ));",$m);
    }
    return $m;
});

file_put_contents($path,$text);

// ---------- CopyTradingController: never mark a due contract completed in the page ----------
$path=__DIR__.'/../app/Http/Controllers/CopyTradingController.php';
$text=file_get_contents($path);
$text=replaceMethod($text,'myCopies',function($m){
    if(!str_contains($m,'CopyRelationshipLifecycleService')){
        $m=str_replace(
            'public function myCopies(TradingPerformanceService $performance)',
            'public function myCopies(TradingPerformanceService $performance, \App\Services\CopyRelationshipLifecycleService $lifecycle)',
            $m
        );
        $m=str_replace(
            "    {\n        \$relationships =",
            "    {\n        // Due contracts settle their attributed positions before completion.\n".
            "        \$lifecycle->expireDue();\n\n        \$relationships =",
            $m
        );
    }

    $m=preg_replace(
        '/\n        foreach \(\$relationships as \$relationship\) \{\n            if \(\$relationship->status === \'active\' && \$relationship->ends_at && \$relationship->ends_at->isPast\(\)\) \{\n                \$relationship->update\(\[\n                    \'status\' => \'completed\',\n                    \'completed_at\' => \$relationship->completed_at \?\? now\(\),\n                \]\);\n                \$relationship->refresh\(\);\n            \}\n/s',
        "\n        foreach (\$relationships as \$relationship) {\n",
        $m
    );

    return $m;
});
file_put_contents($path,$text);

// ---------- Routes ----------
$path=__DIR__.'/../routes/web.php';
$text=file_get_contents($path);
$anchor="        Route::delete('/trading/plans/{plan}', [StockTradePlanController::class, 'cancel'])->name('trading.plans.cancel');";
if(!str_contains($text,"trading.positions.reenter")){
    $block=$anchor."\n".
        "        Route::get('/trading/positions', [\\App\\Http\\Controllers\\TradePositionController::class, 'index'])->name('trading.positions.index');\n".
        "        Route::patch('/trading/positions/{position}/risk', [\\App\\Http\\Controllers\\TradePositionController::class, 'updateRisk'])->name('trading.positions.risk');\n".
        "        Route::post('/trading/positions/{position}/close', [\\App\\Http\\Controllers\\TradePositionController::class, 'close'])->name('trading.positions.close');\n".
        "        Route::post('/trading/positions/{position}/partial-close', [\\App\\Http\\Controllers\\TradePositionController::class, 'partialClose'])->name('trading.positions.partial-close');\n".
        "        Route::post('/trading/positions/{position}/reenter', [\\App\\Http\\Controllers\\TradePositionController::class, 'reenter'])->name('trading.positions.reenter');";
    $text=replaceOnce($text,$anchor,$block,'position routes');
}
file_put_contents($path,$text);

// ---------- Scheduler ----------
$path=__DIR__.'/../routes/console.php';
$text=file_get_contents($path);
if(!str_contains($text,"trade-positions:process")){
    $text.="\n// Position Engine: stop-loss, take-profit, time expiry and queued exits.\n".
        "Schedule::command('trade-positions:process')->everyMinute()->withoutOverlapping();\n";
}
file_put_contents($path,$text);

// ---------- Performance ----------
$path=__DIR__.'/../app/Services/TradingPerformanceService.php';
$text=file_get_contents($path);
if(!str_contains($text,"context_type', 'copy_relationship'")){
    [$start,$end,$m]=methodSlice($text,'copyRelationship');
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

                $this->countMark($positionPnl,$positive,$negative,$neutral);
            }

            $total = $realized + $open;
            $completed = $executions->where('status','completed');
            $volume = (float) $completed->sum('executed_amount');

            $metrics = $this->summary(
                $executions,$completed,$volume,$total,$positive,$negative,$neutral,
                (float)$relationship->allocation_limit,
                [
                    'realized_profit_loss'=>$realized,
                    'open_profit_loss'=>$open,
                    'performance_basis'=>$basis,
                    'return_percent'=>$basis>0?($total/$basis)*100:0,
                    'realized_return_percent'=>$basis>0?($realized/$basis)*100:0,
                    'open_return_percent'=>$basis>0?($open/$basis)*100:0,
                ]
            );
        } else {
            $metrics = $this->fromCopyExecutions($executions,(float)$relationship->allocation_limit);
        }

        $metrics['copy_ratio_percent']=(float)$relationship->copy_ratio_percent;
        $relationship->loadMissing('strategy');

        return $this->applyManualPerformance(
            $metrics,
            (bool)$relationship->strategy?->use_manual_performance,
            $relationship->strategy?->manual_profit_loss,
            $relationship->strategy?->manual_return_percent,
            $relationship->strategy?->manual_performance_label,
            $relationship->strategy?->manual_performance_note
        );
    }
PHP;
    $text=substr($text,0,$start).$replacement.substr($text,$end);
}
file_put_contents($path,$text);

// ---------- Buy view ----------
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
    <p class="mt-2 text-[9px] leading-4 text-muted-foreground">Trade Horizon is the maximum exposure time. Stop loss, take profit, manual close or time expiry — whichever happens first — closes the position.</p>
</div>

BLADE;
    $text=replaceOnce($text,$needle,$risk.$needle,'buy risk panel');
}
file_put_contents($path,$text);

// ---------- Portfolio entry point ----------
$path=__DIR__.'/../resources/views/trading/portfolio.blade.php';
$text=file_get_contents($path);
if(!str_contains($text,"route('trading.positions.index')")){
    $needle='<a href="{{ route(\'stocks.index\') }}" class="ui-btn ui-btn-primary"><i data-lucide="plus" class="h-4 w-4"></i> Browse Stocks</a>';
    $text=replaceOnce(
        $text,$needle,
        '<div class="flex gap-2"><a href="{{ route(\'trading.positions.index\') }}" class="ui-btn ui-btn-secondary"><i data-lucide="route" class="h-4 w-4"></i> Positions</a>'.$needle.'</div>',
        'portfolio positions button'
    );
}
file_put_contents($path,$text);

// ---------- Bot configuration ----------
$path=__DIR__.'/../app/Http/Controllers/TradingBotController.php';
$text=file_get_contents($path);
$text=replaceMethod($text,'update',function($m){
    if(!str_contains($m,"'stop_loss_percent'")){
        $m=str_replace(
            "            'max_total_spend'=>'nullable|numeric|min:1|max:1000000',\n",
            "            'max_total_spend'=>'nullable|numeric|min:1|max:1000000',\n".
            "            'stop_loss_percent'=>'nullable|numeric|min:0.01|max:100',\n".
            "            'take_profit_percent'=>'nullable|numeric|min:0.01|max:100',\n".
            "            'position_duration_minutes'=>'nullable|integer|min:1|max:43200',\n",
            $m
        );
    }
    return $m;
});
file_put_contents($path,$text);

$path=__DIR__.'/../resources/views/ai-bots/configure.blade.php';
$text=file_get_contents($path);
if(!str_contains($text,'name="position_duration_minutes"')){
    $needle='                <div>'."\n".'                    <label class="ui-label">Allocation Cap</label>';
    $risk=<<<'BLADE'
                <div class="rounded-xl border border-border bg-muted/10 p-4">
                    <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Position risk</p>
                    <h3 class="mt-1 text-sm font-semibold">Exit rules for bot-opened positions</h3>
                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        <div><label class="ui-label">Stop Loss %</label><input name="stop_loss_percent" type="number" min="0.01" max="100" step="0.01" class="ui-input" value="{{ old('stop_loss_percent',$bot?->stop_loss_percent) }}"></div>
                        <div><label class="ui-label">Take Profit %</label><input name="take_profit_percent" type="number" min="0.01" max="100" step="0.01" class="ui-input" value="{{ old('take_profit_percent',$bot?->take_profit_percent) }}"></div>
                        <div><label class="ui-label">Max Holding (min)</label><input name="position_duration_minutes" type="number" min="1" max="43200" class="ui-input" value="{{ old('position_duration_minutes',$bot?->position_duration_minutes) }}"></div>
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">The first of stop loss, take profit or time expiry closes the bot-attributed position.</p>
                </div>

BLADE;
    $text=replaceOnce($text,$needle,$risk.$needle,'bot risk UI');
}
file_put_contents($path,$text);

echo "V5.0.1 recovery wiring complete.\n";
