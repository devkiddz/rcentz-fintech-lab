<?php

function replaceExact(string $path, string $old, string $new, string $label): void
{
    if (!is_file($path)) {
        echo "{$label}: skipped (file not found)\n";
        return;
    }

    $text = file_get_contents($path);

    if (str_contains($text, $new)) {
        echo "{$label}: already applied\n";
        return;
    }

    if (!str_contains($text, $old)) {
        echo "{$label}: skipped (target changed)\n";
        return;
    }

    $text = str_replace($old, $new, $text, $count);
    file_put_contents($path, $text);
    echo "{$label}: applied ({$count})\n";
}

function replaceRegex(string $path, string $pattern, string $replacement, string $label): void
{
    if (!is_file($path)) {
        echo "{$label}: skipped (file not found)\n";
        return;
    }

    $text = file_get_contents($path);

    if (str_contains($text, 'mini-analysis-card')) {
        echo "{$label}: already applied\n";
        return;
    }

    $new = preg_replace($pattern, $replacement, $text, 1, $count);

    if (!$count) {
        echo "{$label}: skipped (target changed)\n";
        return;
    }

    file_put_contents($path, $new);
    echo "{$label}: applied\n";
}

$root = dirname(__DIR__);

// AI Bot marketplace
replaceExact(
    $root.'/resources/views/ai-bots/marketplace.blade.php',
    '<div class="h-full w-full" data-rcentz-sparkline data-quotes=\'@json($product->quote_history ?? [])\'></div>',
    '@include(\'trading.partials.mini-analysis-card\',[\'symbol\'=>$stock->symbol,\'height\'=>\'h-[110px]\'])',
    'AI Bot marketplace chart'
);

// AI Bot detail
replaceRegex(
    $root.'/resources/views/ai-bots/show.blade.php',
    '~<div class="relative h-\[320px\] p-3">\s*<div class="h-full w-full"\s*data-rcentz-candles\s*data-quotes=\'@json\(\$quoteHistory\)\'></div>\s*</div>~s',
    '<div class="p-3">@include(\'trading.partials.mini-analysis-card\',[\'symbol\'=>$product->stock->symbol,\'height\'=>\'h-[300px]\'])</div>',
    'AI Bot detail chart'
);

// AI Bot performance
replaceRegex(
    $root.'/resources/views/ai-bots/performance.blade.php',
    '~<div class="relative h-\[300px\] px-3 py-3">\s*<div class="h-full w-full"\s*data-rcentz-candles\s*data-compact="true"\s*data-quotes=\'@json\(\$primaryBot\["quotes"\] \?\? \[\]\)\'></div>\s*</div>~s',
    '<div class="p-3">@include(\'trading.partials.mini-analysis-card\',[\'symbol\'=>$primaryBot[\'symbol\'],\'height\'=>\'h-[280px]\'])</div>',
    'AI Bot performance chart'
);

// Copy marketplace
replaceExact(
    $root.'/resources/views/copy-trading/marketplace.blade.php',
    '<div class="h-full w-full" data-rcentz-sparkline data-quotes=\'@json($series)\'></div>',
    '@include(\'trading.partials.mini-analysis-card\',[\'symbol\'=>$strategy->market_symbol,\'height\'=>\'h-[120px]\'])',
    'Copy marketplace chart'
);

// My copies
replaceExact(
    $root.'/resources/views/copy-trading/my-copies.blade.php',
    '<div class="h-full w-full" data-rcentz-sparkline data-quotes=\'@json($series)\'></div>',
    '@include(\'trading.partials.mini-analysis-card\',[\'symbol\'=>$relationship->market_symbol,\'height\'=>\'h-[140px]\'])',
    'My copied strategies chart'
);

// Copy execution detail
replaceRegex(
    $root.'/resources/views/copy-trading/execution-show.blade.php',
    '~<div class="h-full w-full"\s*data-rcentz-candles\s*data-quotes=\'@json\(\$quoteHistory\)\'\s*data-entry="\{\{ \(float\)\(\$trade\?->price_per_share \?\? 0\) \}\}"></div>~s',
    '@include(\'trading.partials.mini-analysis-card\',[\'symbol\'=>$symbol,\'height\'=>\'h-[280px]\'])',
    'Copy execution chart'
);

// My Bots: replace the old custom SVG region with unified analysis.
replaceRegex(
    $root.'/resources/views/ai-bots/my-bots.blade.php',
    '~<div class="relative h-\[150px\] px-2 py-2 sm:h-\[165px\]">.*?</div>\s*</section>~s',
    '<div class="p-2">@include(\'trading.partials.mini-analysis-card\',[\'symbol\'=>$product->stock->symbol,\'height\'=>\'h-[155px]\'])</div></section>',
    'My Bots chart'
);

echo "V4.2.6 chart-card wiring pass complete.\n";
