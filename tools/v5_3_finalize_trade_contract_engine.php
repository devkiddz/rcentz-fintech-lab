<?php

$path=__DIR__.'/../routes/web.php';
$text=file_get_contents($path);

$remove = [
    "        Route::post('/trading/positions/{position}/cancel-exit', [\\App\\Http\\Controllers\\TradePositionController::class, 'cancelQueuedExit'])->name('trading.positions.cancel-exit');\n",
    "        Route::post('/trading/positions/{position}/extend', [\\App\\Http\\Controllers\\TradePositionController::class, 'extendQueued'])->name('trading.positions.extend');\n",
    "        Route::post('/trading/positions/{position}/keep-next-session', [\\App\\Http\\Controllers\\TradePositionController::class, 'keepThroughNextSession'])->name('trading.positions.keep-next-session');\n",
];

foreach ($remove as $line) {
    $text=str_replace($line,'',$text);
}

file_put_contents($path,$text);

echo "V5.3 route cleanup complete. Kill + Manage lifecycle active.\n";
