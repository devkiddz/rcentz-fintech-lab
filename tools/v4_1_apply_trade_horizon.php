<?php

function patchFile(string $path, array $replacements): void
{
    if (! is_file($path)) throw new RuntimeException("Missing file: {$path}");
    $text = file_get_contents($path);
    foreach ($replacements as [$old,$new,$label]) {
        if (! str_contains($text,$old)) {
            throw new RuntimeException("Patch target not found ({$label}) in {$path}");
        }
        $text = str_replace($old,$new,$text,$count);
        if ($count < 1) throw new RuntimeException("Patch failed ({$label})");
    }
    file_put_contents($path,$text);
}

$root = dirname(__DIR__);
$controller = $root.'/app/Http/Controllers/TradingController.php';
$web = $root.'/routes/web.php';
$console = $root.'/routes/console.php';

@copy($controller,$root.'/storage/app/TradingController.v4_1.backup.php');
@copy($web,$root.'/storage/app/web.v4_1.backup.php');
@copy($console,$root.'/storage/app/console.v4_1.backup.php');

patchFile($controller, [
    [
        "use App\\Services\\CopyTradingService;\n",
        "use App\\Services\\CopyTradingService;\nuse App\\Services\\StockTradePlanService;\nuse App\\Models\\StockTradePlan;\n",
        'controller imports'
    ],
    [
        "public function executeBuy(Request \$request, Stock \$stock, FinancialActivityService \$activity, CopyTradingService \$copyTrading)",
        "public function executeBuy(Request \$request, Stock \$stock, FinancialActivityService \$activity, CopyTradingService \$copyTrading, StockTradePlanService \$tradePlans)",
        'buy signature'
    ],
    [
        "'quantity' => 'required|numeric|min:1|max:10000',\n        ]);",
        "'quantity' => 'required|numeric|min:1|max:10000',\n            'plan_duration_minutes' => 'nullable|integer|min:0|max:10080',\n            'plan_mode' => 'nullable|in:reminder,automatic',\n        ]);",
        'buy validation'
    ],
    [
        "try { \$copyTrading->mirrorCompletedTrade(\$stockTransaction); } catch (\\Throwable \$e) { \\Log::warning('Copy mirroring failed after provider buy', ['trade_id' => \$stockTransaction->id, 'error' => \$e->getMessage()]); }\n",
        "try { \$copyTrading->mirrorCompletedTrade(\$stockTransaction); } catch (\\Throwable \$e) { \\Log::warning('Copy mirroring failed after provider buy', ['trade_id' => \$stockTransaction->id, 'error' => \$e->getMessage()]); }\n            \$tradePlans->createForTransaction(\$stockTransaction, \$request->only(['plan_duration_minutes','plan_mode']));\n",
        'buy plan creation'
    ],
    [
        "public function executeSell(Request \$request, Stock \$stock, FinancialActivityService \$activity, CopyTradingService \$copyTrading)",
        "public function executeSell(Request \$request, Stock \$stock, FinancialActivityService \$activity, CopyTradingService \$copyTrading, StockTradePlanService \$tradePlans)",
        'sell signature'
    ],
    [
        "'quantity' => 'required|numeric|min:1|max:' . \$holding->quantity,\n        ]);",
        "'quantity' => 'required|numeric|min:1|max:' . \$holding->quantity,\n            'plan_duration_minutes' => 'nullable|integer|min:0|max:10080',\n            'plan_mode' => 'nullable|in:reminder,automatic',\n        ]);",
        'sell validation'
    ],
    [
        "try { \$copyTrading->mirrorCompletedTrade(\$stockTransaction); } catch (\\Throwable \$e) { \\Log::warning('Copy mirroring failed after provider sale', ['trade_id' => \$stockTransaction->id, 'error' => \$e->getMessage()]); }\n",
        "try { \$copyTrading->mirrorCompletedTrade(\$stockTransaction); } catch (\\Throwable \$e) { \\Log::warning('Copy mirroring failed after provider sale', ['trade_id' => \$stockTransaction->id, 'error' => \$e->getMessage()]); }\n            \$tradePlans->createForTransaction(\$stockTransaction, \$request->only(['plan_duration_minutes','plan_mode']));\n",
        'sell plan creation'
    ],
    [
        "        // Sort holdings by current value\n        \$holdings = \$holdings->sortByDesc('current_value')->values();\n",
        "        // Sort holdings by current value\n        \$holdings = \$holdings->sortByDesc('current_value')->values();\n\n        \$tradePlans = StockTradePlan::with('stock')\n            ->where('user_id', \$user->id)\n            ->whereIn('status', ['active','due'])\n            ->orderBy('due_at')\n            ->get();\n",
        'portfolio plans query'
    ],
    [
        "            'recentTransactions'\n        ));",
        "            'recentTransactions',\n            'tradePlans'\n        ));",
        'portfolio compact'
    ],
]);

patchFile($web, [
    [
        "use App\\Http\\Controllers\\TradingBotController;\n",
        "use App\\Http\\Controllers\\TradingBotController;\nuse App\\Http\\Controllers\\StockTradePlanController;\n",
        'web import'
    ],
    [
        "        Route::delete('/trading/watchlist/{stock}', [TradingController::class, 'removeFromWatchlist'])->name('trading.watchlist.remove');\n",
        "        Route::delete('/trading/watchlist/{stock}', [TradingController::class, 'removeFromWatchlist'])->name('trading.watchlist.remove');\n        Route::delete('/trading/plans/{plan}', [StockTradePlanController::class, 'cancel'])->name('trading.plans.cancel');\n",
        'trade plan cancel route'
    ],
]);

$text = file_get_contents($console);
if (! str_contains($text,'StockTradePlanService')) {
    $text = preg_replace(
        '/(<\?php\s*)/',
        "$1use App\\Services\\StockTradePlanService;\nuse App\\Services\\StockExecutionService;\nuse App\\Services\\MarketSessionService;\n",
        $text,
        1
    );
    $text .= "\n\nSchedule::call(function () {\n    app(StockTradePlanService::class)->processDuePlans(\n        app(StockExecutionService::class),\n        app(MarketSessionService::class)\n    );\n})->name('stock-trade-plans:process-due')->everyMinute()->withoutOverlapping()->onOneServer();\n";
    file_put_contents($console,$text);
}

echo "V4.1 trade horizon wiring complete.\n";
echo "Backups saved under storage/app/*.v4_1.backup.php\n";
