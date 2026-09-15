<?php

$path = __DIR__.'/../routes/console.php';

if (! is_file($path)) {
    fwrite(STDERR, "routes/console.php not found.\n");
    exit(1);
}

$text = file_get_contents($path);

$backup = __DIR__.'/../storage/app/console.php.v4_2_4_1.backup';
@mkdir(dirname($backup), 0777, true);
file_put_contents($backup, $text);

$marker = "stocks:refresh-history";

if (str_contains($text, $marker)) {
    echo "Historical refresh schedule already exists. Nothing to change.\n";
    exit(0);
}

$block = <<<'PHP'

/*
|--------------------------------------------------------------------------
| Historical stock OHLCV refresh
|--------------------------------------------------------------------------
| Alpha Vantage daily history is persisted locally and consumed by charts.
| Live intraday quotes remain handled by Finnhub.
*/
Schedule::command('stocks:refresh-history')
    ->dailyAt('22:30')
    ->timezone('America/New_York')
    ->withoutOverlapping()
    ->onOneServer();

PHP;

$text = rtrim($text).PHP_EOL.PHP_EOL.$block;

file_put_contents($path, $text);

echo "V4.2.4.1 historical refresh schedule added.\n";
echo "Backup: {$backup}\n";
