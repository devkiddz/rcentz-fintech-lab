<?php

$path = __DIR__.'/../app/Jobs/FetchStockHistoryJob.php';

if (! is_file($path)) {
    fwrite(STDERR, "FetchStockHistoryJob.php not found.\n");
    exit(1);
}

$text = file_get_contents($path);
$backup = __DIR__.'/../storage/app/FetchStockHistoryJob.v4_2_1.backup.php';
@mkdir(dirname($backup), 0777, true);
file_put_contents($backup, $text);

$old = <<<'PHP'
        $historicalData = $yahooFinanceService->getHistoricalData($symbol, $this->interval, 30);
PHP;

$new = <<<'PHP'
        // Yahoo's history endpoint expects lower-case interval tokens such as
        // "1d". Keep our DB interval canonical ("1D") but normalize only the API
        // request. Pull enough rows to support SMA20/50/200 when available.
        $apiInterval = strtolower($this->interval);
        $historicalData = $yahooFinanceService->getHistoricalData($symbol, $apiInterval, 220);
PHP;

if (! str_contains($text, $old)) {
    fwrite(STDERR, "History fetch target not found. No changes made.\n");
    exit(2);
}

$text = str_replace($old, $new, $text, $count);

if ($count !== 1) {
    fwrite(STDERR, "Expected one history fetch target, found {$count}. No changes made.\n");
    exit(3);
}

file_put_contents($path, $text);

echo "V4.2.1 stock history fetch normalized.\n";
echo "Backup: {$backup}\n";
