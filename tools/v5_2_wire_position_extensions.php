<?php

$path=__DIR__.'/../routes/web.php';
$text=file_get_contents($path);

$anchor="        Route::post('/trading/positions/{position}/cancel-exit', [\\App\\Http\\Controllers\\TradePositionController::class, 'cancelQueuedExit'])->name('trading.positions.cancel-exit');";

if(!str_contains($text,"trading.positions.extend")){
    if(!str_contains($text,$anchor)){
        throw new RuntimeException('V5.1 cancel-exit route anchor was not found.');
    }

    $block=$anchor."\n".
        "        Route::post('/trading/positions/{position}/extend', [\\App\\Http\\Controllers\\TradePositionController::class, 'extendQueued'])->name('trading.positions.extend');\n".
        "        Route::post('/trading/positions/{position}/keep-next-session', [\\App\\Http\\Controllers\\TradePositionController::class, 'keepThroughNextSession'])->name('trading.positions.keep-next-session');";

    $text=str_replace($anchor,$block,$text,$count);

    if($count!==1){
        throw new RuntimeException('Unexpected V5.2 route replacement count: '.$count);
    }

    file_put_contents($path,$text);
}

echo "V5.2 position extension routes wired.\n";
