<?php

function replaceOne(string $path, string $old, string $new, string $label): void {
    if (!is_file($path)) throw new RuntimeException("Missing {$path}");
    $text = file_get_contents($path);
    if (str_contains($text, $new)) { echo "{$label}: already applied\n"; return; }
    if (!str_contains($text, $old)) throw new RuntimeException("Target not found for {$label}");
    file_put_contents($path, str_replace($old, $new, $text, $count));
    echo "{$label}: applied ({$count})\n";
}

$root = dirname(__DIR__);

replaceOne(
    $root.'/app/Services/TradingPerformanceService.php',
    "\$volume = (float) \$completed->sum('copied_amount');",
    "\$volume = (float) \$completed->sum('executed_amount');",
    'Copy performance volume truth'
);

replaceOne(
    $root.'/app/Services/CopyTradingService.php',
    "->where('provider_id',\$providerTrade->user_id)->where('status','active')->whereHas('strategy'",
    "->where('provider_id',\$providerTrade->user_id)->where('status','active')->where(function(\$q){\$q->whereNull('ends_at')->orWhere('ends_at','>',now());})->whereHas('strategy'",
    'Copy execution duration gate'
);

$console = $root.'/routes/console.php';
$text = file_get_contents($console);
$import = "use App\\Services\\CopyRelationshipLifecycleService;";
if (!str_contains($text, $import)) {
    $text = str_replace(
        "use App\\Services\\BotSubscriptionLifecycleService;",
        "use App\\Services\\BotSubscriptionLifecycleService;\n{$import}",
        $text
    );
}
if (!str_contains($text, "copy-relationships:expire-due")) {
    $text .= "\n\nSchedule::call(fn () => app(CopyRelationshipLifecycleService::class)->expireDue())\n".
             "    ->name('copy-relationships:expire-due')\n".
             "    ->everyMinute()\n".
             "    ->withoutOverlapping()\n".
             "    ->onOneServer();\n";
}
file_put_contents($console, $text);
echo "Copy relationship lifecycle schedule: applied\n";

echo "V4.3 service wiring complete.\n";
