<?php

$path = __DIR__.'/../routes/web.php';

if (! is_file($path)) {
    fwrite(STDERR, "routes/web.php not found.\n");
    exit(1);
}

$contents = file_get_contents($path);
$backupDir = __DIR__.'/../storage/app';
@mkdir($backupDir, 0777, true);
$backup = $backupDir.'/web.php.v3_2.backup';
file_put_contents($backup, $contents);

$pattern = <<<'REGEX'
~\n\s*// Copy Trading\n\s*Route::get\('/trading/copy'.*?Route::get\('/trading/bot-executions'.*?;\n\s*\}\);\n~s
REGEX;

// IMPORTANT: preserve the closing brace for the surrounding stock-trading middleware.
$cleaned = preg_replace(
    $pattern,
    "\n        // Legacy V1 handlers removed; canonical product-domain routes are below.\n    });\n",
    $contents,
    1,
    $count
);

if ($count !== 1) {
    fwrite(STDERR, "Route cleanup block was not found exactly once. Original file left untouched.\n");
    exit(2);
}

file_put_contents($path, $cleaned);

echo "V3.2 route cleanup complete.\n";
echo "Backup: {$backup}\n";
