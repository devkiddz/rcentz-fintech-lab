<?php

$path = __DIR__.'/../routes/web.php';

if (! file_exists($path)) {
    throw new RuntimeException('routes/web.php not found.');
}

$text = file_get_contents($path);

$anchor = "        // Individual stock route (move this down)\n        Route::get('/{stock}', [AdminStockController::class, 'show'])->name('show');";

$block = "        // Admin Strategy Trading Desk\n".
    "        Route::get('/{stock}/trade', [AdminStockController::class, 'trade'])->name('trade');\n".
    "        Route::post('/{stock}/trade', [AdminStockController::class, 'executeStrategyTrade'])->name('trade.execute');\n\n".
    $anchor;

if (! str_contains($text, "->name('trade.execute');")) {
    if (! str_contains($text, $anchor)) {
        throw new RuntimeException('Admin stock route anchor not found.');
    }

    $text = str_replace($anchor, $block, $text, $count);

    if ($count !== 1) {
        throw new RuntimeException("Unexpected route insertion count: {$count}");
    }
}

file_put_contents($path, $text);

echo "Admin strategy trading routes wired.\n";
