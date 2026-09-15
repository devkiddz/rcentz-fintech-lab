<?php

$path = __DIR__.'/../routes/web.php';

if (! is_file($path)) {
    fwrite(STDERR, "routes/web.php not found.\n");
    exit(1);
}

$contents = file_get_contents($path);

$needle = "    // Legacy /trading entry points are redirects only; V2 domains own the handlers.\n";
$replacement = "    // Legacy /trading entry points are redirects only; V2 domains own the handlers.\n    });\n";

if (! str_contains($contents, $needle)) {
    fwrite(STDERR, "V3.2 cleanup marker not found. No changes made.\n");
    exit(2);
}

if (str_contains($contents, $replacement)) {
    echo "Route closure is already present. Nothing to change.\n";
    exit(0);
}

$contents = str_replace($needle, $replacement, $contents, $count);

if ($count !== 1) {
    fwrite(STDERR, "Expected exactly one cleanup marker, found {$count}. No changes made.\n");
    exit(3);
}

file_put_contents($path, $contents);

echo "V3.2.1 route closure restored.\n";
