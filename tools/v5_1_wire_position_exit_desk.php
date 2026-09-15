<?php
$path=__DIR__.'/../routes/web.php';
$text=file_get_contents($path);

$anchor="        Route::post('/trading/positions/{position}/close', [\\App\\Http\\Controllers\\TradePositionController::class, 'close'])->name('trading.positions.close');";
$new=$anchor."\n".
"        Route::post('/trading/positions/{position}/cancel-exit', [\\App\\Http\\Controllers\\TradePositionController::class, 'cancelQueuedExit'])->name('trading.positions.cancel-exit');";

if(!str_contains($text,"trading.positions.cancel-exit")){
    if(!str_contains($text,$anchor)){
        throw new RuntimeException('Position close route anchor was not found.');
    }
    $text=str_replace($anchor,$new,$text,$count);
    if($count!==1){
        throw new RuntimeException('Unexpected position route replacement count: '.$count);
    }
    file_put_contents($path,$text);
}

echo "V5.1 exit desk route wiring complete.\n";
