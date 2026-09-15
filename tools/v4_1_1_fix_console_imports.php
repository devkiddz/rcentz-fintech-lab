<?php

$path = __DIR__.'/../routes/console.php';

if (! is_file($path)) {
    fwrite(STDERR, "routes/console.php not found.\n");
    exit(1);
}

$contents = file_get_contents($path);
$backup = __DIR__.'/../storage/app/console.php.v4_1_1.backup';
@mkdir(dirname($backup), 0777, true);
file_put_contents($backup, $contents);

$imports = [
    'use App\\Services\\MarketSessionService;',
    'use App\\Services\\StockTradePlanService;',
    'use App\\Services\\StockExecutionService;',
];

foreach ($imports as $import) {
    $quoted = preg_quote($import, '/');
    preg_match_all('/^' . $quoted . '\R/m', $contents, $matches);

    if (count($matches[0]) > 1) {
        $seen = false;
        $contents = preg_replace_callback(
            '/^' . $quoted . '\R/m',
            function ($match) use (&$seen) {
                if (! $seen) {
                    $seen = true;
                    return $match[0];
                }
                return '';
            },
            $contents
        );
    }
}

file_put_contents($path, $contents);

echo "V4.1.1 console imports normalized.\n";
echo "Backup: {$backup}\n";
