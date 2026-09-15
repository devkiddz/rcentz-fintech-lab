<?php

function patchOnce(string $path, string $needle, string $replacement, string $label): void
{
    if (! is_file($path)) {
        throw new RuntimeException("Missing file: {$path}");
    }

    $text = file_get_contents($path);

    if (str_contains($text, $replacement)) {
        echo "{$label}: already applied.\n";
        return;
    }

    if (! str_contains($text, $needle)) {
        throw new RuntimeException("Patch target not found for {$label}: {$path}");
    }

    $text = str_replace($needle, $replacement, $text, $count);

    if ($count !== 1) {
        throw new RuntimeException("Expected one patch target for {$label}, found {$count}.");
    }

    file_put_contents($path, $text);
    echo "{$label}: applied.\n";
}

$root = dirname(__DIR__);
$services = $root.'/config/services.php';
$console = $root.'/routes/console.php';

@mkdir($root.'/storage/app', 0777, true);
@copy($services, $root.'/storage/app/services.v4_2_4.backup.php');
@copy($console, $root.'/storage/app/console.v4_2_4.backup.php');

$servicesNeedle = "return [";
$servicesReplacement = "return [\n    'alpha_vantage' => [\n        'api_key' => env('ALPHA_VANTAGE_API_KEY'),\n    ],";

patchOnce(
    $services,
    $servicesNeedle,
    $servicesReplacement,
    'Alpha Vantage config'
);

$consoleNeedle = "Schedule::job(new FetchStockHistoryJob())\n    ->hourly();";
$consoleReplacement = "Schedule::job(new FetchStockHistoryJob())\n    ->hourly();\n\n// Daily provider-backed OHLCV refresh for historical story charts.\nSchedule::command('stocks:refresh-history')\n    ->dailyAt('22:30')\n    ->timezone('America/New_York')\n    ->withoutOverlapping()\n    ->onOneServer();";

patchOnce(
    $console,
    $consoleNeedle,
    $consoleReplacement,
    'Historical refresh schedule'
);

echo "V4.2.4 wiring complete.\n";
echo "Backups saved under storage/app/*v4_2_4.backup.php\n";
