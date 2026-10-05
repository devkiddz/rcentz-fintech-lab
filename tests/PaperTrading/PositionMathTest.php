<?php
declare(strict_types=1);

require __DIR__.'/../../app/Services/PaperTrading/PositionMath.php';

use App\Services\PaperTrading\PositionMath;

$checks = 0;
$failures = [];
function check(string $name, mixed $actual, mixed $expected): void
{
    global $checks, $failures;
    $checks++;
    $equal = is_float($expected)
        ? is_numeric($actual) && abs($actual - $expected) <= max(1e-10, abs($expected) * 1e-10)
        : $actual === $expected;
    if (!$equal) { $failures[] = $name; }
}
function rejects(string $name, Closure $operation): void
{
    try { $operation(); check($name, false, true); }
    catch (InvalidArgumentException) { check($name, true, true); }
}

check('Buy opens long', PositionMath::directionForOpeningSide('buy'), 'long');
check('Sell opens short', PositionMath::directionForOpeningSide('sell'), 'short');
check('Long closes with sell', PositionMath::closingSide('long'), 'sell');
check('Short closes with buy', PositionMath::closingSide('short'), 'buy');
foreach ([['stock', 100.0, 110.0, 3.0, 30.0], ['forex', 1.1, 1.105, 1000.0, 5.0], ['crypto', 60000.0, 61000.0, 0.01, 10.0]] as [$asset, $entry, $up, $quantity, $gain]) {
    check($asset.' long rising gain', PositionMath::quoteProfitLoss('long', $entry, $up, $quantity), $gain);
    check($asset.' short rising loss', PositionMath::quoteProfitLoss('short', $entry, $up, $quantity), -$gain);
    check($asset.' short falling gain', PositionMath::quoteProfitLoss('short', $up, $entry, $quantity), $gain);
    check($asset.' long falling loss', PositionMath::quoteProfitLoss('long', $up, $entry, $quantity), -$gain);
    check($asset.' unchanged short', PositionMath::quoteProfitLoss('short', $entry, $entry, $quantity), 0.0);
}
$long = PositionMath::riskLevels('long', 100, 5, 10);
$short = PositionMath::riskLevels('short', 100, 5, 10);
check('Long SL below entry', $long['stop_loss_price'], 95.0);
check('Long TP above entry', $long['take_profit_price'], 110.0);
check('Short SL above entry', $short['stop_loss_price'], 105.0);
check('Short TP below entry', $short['take_profit_price'], 90.0);
foreach ([['long', 95.0, 'stop_loss'], ['long', 94.0, 'stop_loss'], ['long', 110.0, 'take_profit'], ['long', 111.0, 'take_profit'], ['short', 105.0, 'stop_loss'], ['short', 106.0, 'stop_loss'], ['short', 90.0, 'take_profit'], ['short', 89.0, 'take_profit'], ['short', 100.0, null], ['long', 100.0, null]] as [$direction, $price, $expected]) {
    $levels = $direction === 'long' ? $long : $short;
    check($direction.' threshold '.$price, PositionMath::triggeredExit($direction, $price, $levels['stop_loss_price'], $levels['take_profit_price']), $expected);
}
// Inclusive boundaries must work without triggering at materially different prices.
check('Long TP immediately below', PositionMath::triggeredExit('long', 109.99999999, null, $long['take_profit_price']), null);
check('Long TP immediately above', PositionMath::triggeredExit('long', 110.00000001, null, $long['take_profit_price']), 'take_profit');
check('Short SL immediately below', PositionMath::triggeredExit('short', 104.99999999, $short['stop_loss_price'], null), null);
check('Short SL immediately above', PositionMath::triggeredExit('short', 105.00000001, $short['stop_loss_price'], null), 'stop_loss');
check('Long SL immediately above', PositionMath::triggeredExit('long', 95.00000001, $long['stop_loss_price'], null), null);
check('Short TP immediately above', PositionMath::triggeredExit('short', 90.00000001, null, $short['take_profit_price']), null);
check('No risk levels', PositionMath::riskLevels('short', 100, null, null), ['stop_loss_price' => null, 'take_profit_price' => null]);
$remaining = 10001;
$open = 3.0;
$released = 0;
foreach ([1.0, 1.0, 1.0] as $quantity) {
    $split = PositionMath::splitCollateral($remaining, $open, $quantity);
    check('Collateral conservation at '.$open, $split['released'] + $split['remaining'], $remaining);
    $released += $split['released'];
    $remaining = $split['remaining'];
    $open -= $quantity;
}
check('All original cents released', $released, 10001);
check('No residual collateral', $remaining, 0);
check('Tiny partial close retains final cent', PositionMath::splitCollateral(1, 1, 0.1), ['released' => 0, 'remaining' => 1]);
rejects('Unknown direction', fn () => PositionMath::quoteProfitLoss('flat', 100, 110, 1));
rejects('Invalid side', fn () => PositionMath::directionForOpeningSide('close'));
rejects('Zero quantity', fn () => PositionMath::quoteProfitLoss('short', 100, 90, 0));
rejects('Negative price', fn () => PositionMath::quoteProfitLoss('long', -1, 2, 1));
rejects('NaN price', fn () => PositionMath::quoteProfitLoss('long', NAN, 2, 1));
rejects('Infinite price', fn () => PositionMath::quoteProfitLoss('long', INF, 2, 1));
rejects('Overflow P/L', fn () => PositionMath::quoteProfitLoss('long', 1, PHP_FLOAT_MAX, PHP_FLOAT_MAX));
rejects('Close more than open', fn () => PositionMath::splitCollateral(10000, 1, 2));
rejects('Negative collateral', fn () => PositionMath::splitCollateral(-1, 1, 1));
rejects('100 percent risk', fn () => PositionMath::riskLevels('short', 100, null, 100));
rejects('Invalid risk', fn () => PositionMath::riskLevels('long', 100, -5, 10));
echo 'Paper position math: '.$checks.' checks; '.count($failures).' failures'.PHP_EOL;
foreach ($failures as $failure) { fwrite(STDERR, 'FAIL: '.$failure.PHP_EOL); }
exit($failures ? 1 : 0);
