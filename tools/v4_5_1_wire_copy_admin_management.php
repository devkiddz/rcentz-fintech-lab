<?php

$path = __DIR__.'/../routes/web.php';

if (! file_exists($path)) {
    throw new RuntimeException("routes/web.php not found.");
}

$text = file_get_contents($path);

$providerAnchor = "        Route::get('/providers', [AdminCopyTradingController::class, 'providers'])->name('providers');";
$providerBlock = $providerAnchor . "\n" .
    "        Route::get('/providers/{provider}', [AdminCopyTradingController::class, 'providerShow'])->name('providers.show');\n" .
    "        Route::patch('/providers/{provider}/toggle', [AdminCopyTradingController::class, 'toggleProvider'])->name('providers.toggle');";

if (! str_contains($text, "->name('providers.show');")) {
    if (! str_contains($text, $providerAnchor)) {
        throw new RuntimeException("Provider route anchor not found inside the admin copy-trading group.");
    }

    $text = str_replace($providerAnchor, $providerBlock, $text, $providerCount);

    if ($providerCount !== 1) {
        throw new RuntimeException("Unexpected provider route replacement count: {$providerCount}");
    }
}

$strategyAnchor = "        Route::get('/strategies', [AdminCopyTradingController::class, 'strategies'])->name('strategies');";
$strategyBlock = $strategyAnchor . "\n" .
    "        Route::get('/strategies/{strategy}', [AdminCopyTradingController::class, 'strategyShow'])->name('strategies.show');\n" .
    "        Route::patch('/strategies/{strategy}/retire', [AdminCopyTradingController::class, 'retireStrategy'])->name('strategies.retire');\n" .
    "        Route::delete('/strategies/{strategy}', [AdminCopyTradingController::class, 'destroyStrategy'])->name('strategies.destroy');";

if (! str_contains($text, "->name('strategies.show');")) {
    if (! str_contains($text, $strategyAnchor)) {
        throw new RuntimeException("Strategy route anchor not found inside the admin copy-trading group.");
    }

    $text = str_replace($strategyAnchor, $strategyBlock, $text, $strategyCount);

    if ($strategyCount !== 1) {
        throw new RuntimeException("Unexpected strategy route replacement count: {$strategyCount}");
    }
}

file_put_contents($path, $text);

echo "V4.5.1.1 Copy Trading admin routes wired successfully.\n";
