<?php

$path = __DIR__.'/../routes/web.php';
$text = file_get_contents($path);

if (!str_contains($text, "name('admin.trading.index')")) {
    $anchor = "        // Admin Car Management";

    if (!str_contains($text, $anchor)) {
        throw new RuntimeException('Could not locate admin route anchor.');
    }

    $routes = <<<'PHP'

        // Admin Trading Command
        Route::get('trading', [\App\Http\Controllers\Admin\TradingOperationsController::class, 'index'])->name('trading.index');
        Route::get('trading/manual', [\App\Http\Controllers\Admin\TradingOperationsController::class, 'manual'])->name('trading.manual');
        Route::get('trading/positions', [\App\Http\Controllers\Admin\TradingOperationsController::class, 'positions'])->name('trading.positions');
        Route::get('trading/history', [\App\Http\Controllers\Admin\TradingOperationsController::class, 'history'])->name('trading.history');

PHP;

    $text = str_replace($anchor, $routes.$anchor, $text);
    file_put_contents($path, $text);
    echo "V5.4.1 admin trading command routes added.\n";
} else {
    echo "V5.4.1 admin trading command routes already present.\n";
}
