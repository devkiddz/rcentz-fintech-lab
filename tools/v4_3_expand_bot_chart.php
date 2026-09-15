<?php
$path = __DIR__.'/../resources/views/ai-bots/my-bots.blade.php';
if (!is_file($path)) throw new RuntimeException('My Bots view missing.');
$text = file_get_contents($path);
$text = str_replace(
    "['symbol'=>\$product->stock->symbol,'height'=>'h-[155px]']",
    "['symbol'=>\$product->stock->symbol,'height'=>'h-[230px] sm:h-[280px]']",
    $text,
    $count
);
file_put_contents($path,$text);
echo "AI Bot chart height updated ({$count}).\n";
