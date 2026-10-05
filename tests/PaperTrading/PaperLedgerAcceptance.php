<?php
declare(strict_types=1);

use App\Models\BrokerOrder;
use App\Models\ControlledMarketInstrument;
use App\Models\MarketEnvironment;
use App\Models\MarketExecutionTransaction;
use App\Models\MarketInstrument;
use App\Models\TradePosition;
use App\Models\User;
use App\Models\Wallet;
use App\Services\BrokerOrderService;
use App\Services\BrokerPositionService;
use App\Services\MarketPriceRouter;
use App\Services\PaperTrading\PaperBrokerService;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$connection = DB::connection();
$host = $connection->getConfig('host');
if (!$app->environment('local', 'testing') || $connection->getDriverName() !== 'mysql'
    || !is_string($host) || !in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
    fwrite(STDERR, "Refusing acceptance writes outside a local/testing MySQL database on localhost.\n");
    exit(1);
}
// Rollback isolation requires transactional tables, not merely a local host.
$required = ['users', 'wallets', 'wallet_transactions', 'financial_activities', 'broker_orders',
    'broker_order_events', 'trade_positions', 'trade_position_events', 'market_instruments',
    'market_execution_transactions', 'market_environments', 'controlled_market_instruments', 'currency_rates'];
$engines = $connection->table('information_schema.TABLES')
    ->whereRaw('TABLE_SCHEMA = DATABASE()')->whereIn('TABLE_NAME', $required)
    ->pluck('ENGINE', 'TABLE_NAME')->all();
foreach ($required as $table) {
    if (strtolower((string)($engines[$table] ?? '')) !== 'innodb') {
        fwrite(STDERR, 'Refusing acceptance writes: required InnoDB table missing or incompatible: '.$table.PHP_EOL);
        exit(1);
    }
}
$checks = 0;
function verify(string $name, bool $valid): void
{
    global $checks;
    $checks++;
    if (!$valid) { throw new RuntimeException('FAILED: '.$name); }
}
function equalMoney(string $name, float $actual, float $expected): void
{
    verify($name, abs($actual-$expected) < 0.001);
}
function rejected(string $name, Closure $operation): void
{
    $caught = false;
    try { $operation(); } catch (Throwable) { $caught = true; }
    verify($name, $caught);
}
function snapshotPaper(User $user): array
{
    $wallet = $user->wallet()->firstOrFail();
    return [(string)$wallet->balance, (string)$wallet->reserved_balance,
        BrokerOrder::where('user_id', $user->id)->count(),
        TradePosition::where('user_id', $user->id)->count(),
        MarketExecutionTransaction::where('user_id', $user->id)->count()];
}

// Everything below is uncommitted and rolled back, including marketplace and fixture prices.
// No broker API, worker, cache-clear or seeding command is invoked.
$connection->beginTransaction();
try {
    config(['paper_trading.enabled'=>true, 'paper_trading.fee_basis_points'=>0]);
    MarketEnvironment::current()->update(['active_marketplace'=>'controlled']);
    $app->forgetInstance(MarketPriceRouter::class);
    $broker = $app->make(BrokerOrderService::class);
    $paper = $app->make(PaperBrokerService::class);
    $tag = bin2hex(random_bytes(5));
    $user = User::withoutEvents(fn () => User::create([
        'name'=>'Paper Acceptance', 'email'=>'paper-'.$tag.'@example.invalid',
        'password'=>bin2hex(random_bytes(24)), 'email_verified_at'=>now(),
        'is_admin'=>false, 'is_production_demo'=>false, 'account_status'=>'active',
        'country'=>'Nigeria', 'currency'=>'USD',
    ]));
    Wallet::create(['user_id'=>$user->id, 'balance'=>10000, 'reserved_balance'=>0, 'currency'=>'USD']);
    $initialRows = $user->id;
    $expectedBalance = 10000.0;
    foreach (['stock', 'forex', 'crypto'] as $asset) {
        $symbol = strtoupper(substr($asset, 0, 2)).$tag;
        $instrument = MarketInstrument::create(['symbol'=>$symbol, 'display_symbol'=>$symbol,
            'name'=>'Paper Acceptance '.$asset, 'asset_class'=>$asset, 'base_asset'=>$asset === 'forex' ? 'EUR' : $symbol,
            'quote_asset'=>'USD', 'price_precision'=>8, 'is_active'=>true, 'is_featured'=>false]);
        $feed = ControlledMarketInstrument::create(['market_instrument_id'=>$instrument->id,
            'stock_id'=>null, 'symbol'=>$symbol, 'label'=>'Paper Acceptance', 'asset_class'=>$asset,
            'current_price'=>100, 'previous_price'=>100, 'opening_price'=>100, 'high'=>100, 'low'=>100,
            'decimal_precision'=>8, 'minimum_tick'=>0.00000001, 'minimum_price'=>0.00000001,
            'volatility_percent'=>0, 'individual_bias'=>0, 'is_active'=>true]);

        // A Sell with no owned holding must open a short; Buy closes that short.
        $key = 'paper-'.$tag.'-'.$asset.'-short';
        $short = $broker->placeMarketOrder($user, $instrument, 'sell', 2, 'units', $key,
            ['stop_loss_percent'=>5, 'take_profit_percent'=>10]);
        $position = $short->execution->tradePosition;
        verify($asset.' sell opens short without a purchase', $position->direction === 'short');
        verify($asset.' opening receipt has open intent', $short->execution->metadata['order_intent'] === 'open');
        equalMoney($asset.' opening reserves collateral', (float)$user->wallet()->first()->reserved_balance, 200);
        equalMoney($asset.' opening does not credit proceeds', (float)$user->wallet()->first()->balance, $expectedBalance);
        $before = snapshotPaper($user);
        $again = $broker->placeMarketOrder($user, $instrument, 'sell', 2, 'units', $key,
            ['stop_loss_percent'=>5, 'take_profit_percent'=>10]);
        verify($asset.' opening replay returns original order', $again->id === $short->id);
        verify($asset.' replay does not mutate ledger', snapshotPaper($user) === $before);
        $feed->update(['current_price'=>90]);
        equalMoney($asset.' falling-price short P/L', (float)$position->current_profit_loss, 20);
        $partialKey = 'paper-'.$tag.'-'.$asset.'-partial';
        $partial = $broker->placePositionClose($user, $position, 1, $partialKey);
        verify($asset.' short closes with buy', $partial->side === 'buy');
        $expectedBalance += 10;
        equalMoney($asset.' partial settlement wallet', (float)$user->wallet()->first()->balance, $expectedBalance);
        equalMoney($asset.' partial reservation', (float)$user->wallet()->first()->reserved_balance, 100);
        equalMoney($asset.' partial exposure', (float)$position->refresh()->open_quantity, 1);
        $before = snapshotPaper($user);
        $broker->placePositionClose($user, $position, 1, $partialKey);
        verify($asset.' partial replay does not debit/credit twice', snapshotPaper($user) === $before);
        $feed->update(['current_price'=>80]);
        $fullKey = 'paper-'.$tag.'-'.$asset.'-full';
        $full = $broker->placePositionClose($user, $position, null, $fullKey);
        $expectedBalance += 20;
        equalMoney($asset.' full settlement wallet', (float)$user->wallet()->first()->balance, $expectedBalance);
        equalMoney($asset.' final collateral released', (float)$user->wallet()->first()->reserved_balance, 0);
        verify($asset.' closed position terminal', $position->refresh()->status === 'closed');
        $before = snapshotPaper($user);
        verify($asset.' terminal close replay', $broker->placePositionClose($user, $position, null, $fullKey)->id === $full->id);
        verify($asset.' terminal replay financially unchanged', snapshotPaper($user) === $before);

        // Long profit and short loss exercise both settlement signs.
        $feed->update(['current_price'=>100]);
        $long = $broker->placeMarketOrder($user, $instrument, 'buy', 1, 'units', 'paper-'.$tag.'-'.$asset.'-long');
        verify($asset.' buy opens long', $long->execution->tradePosition->direction === 'long');
        $feed->update(['current_price'=>110]);
        $exit = $broker->placePositionClose($user, $long->execution->tradePosition, null, 'paper-'.$tag.'-'.$asset.'-long-exit');
        verify($asset.' long close is sell', $exit->side === 'sell');
        $expectedBalance += 10;
        equalMoney($asset.' long gain settled', (float)$user->wallet()->first()->balance, $expectedBalance);
        $feed->update(['current_price'=>100]);
        $loser = $broker->placeMarketOrder($user, $instrument, 'sell', 1, 'units', 'paper-'.$tag.'-'.$asset.'-loss');
        $feed->update(['current_price'=>110]);
        $broker->placePositionClose($user, $loser->execution->tradePosition, null, 'paper-'.$tag.'-'.$asset.'-loss-exit');
        $expectedBalance -= 10;
        equalMoney($asset.' short loss settled', (float)$user->wallet()->first()->balance, $expectedBalance);

        $feed->update(['current_price'=>100]);
        $before = snapshotPaper($user);
        rejected($asset.' insufficient collateral', fn () => $broker->placeMarketOrder($user, $instrument, 'sell', 100000, 'units', 'paper-'.$tag.'-'.$asset.'-insufficient'));
        verify($asset.' rejected trade changes nothing', snapshotPaper($user) === $before);
        rejected($asset.' changed request cannot reuse key', fn () => $broker->placeMarketOrder($user, $instrument, 'sell', 3, 'units', $key));
        verify($asset.' no filled order lacks receipt', BrokerOrder::where('user_id',$user->id)->where('status','filled')->whereNull('market_execution_transaction_id')->count() === 0);
    }

    $feed->update(['current_price'=>100]);
    $owned = $broker->placeMarketOrder($user, $instrument, 'buy', 1, 'units', 'paper-'.$tag.'-owned');
    $other = User::withoutEvents(fn () => User::create([
        'name'=>'Other Paper Acceptance', 'email'=>'other-paper-'.$tag.'@example.invalid',
        'password'=>bin2hex(random_bytes(24)), 'email_verified_at'=>now(),
        'is_admin'=>false, 'is_production_demo'=>false, 'account_status'=>'active',
        'country'=>'Nigeria', 'currency'=>'USD',
    ]));
    Wallet::create(['user_id'=>$other->id, 'balance'=>10000, 'reserved_balance'=>0, 'currency'=>'USD']);
    $before = snapshotPaper($user);
    rejected('Another customer cannot close owned position', fn () => $broker->placePositionClose(
        $other, $owned->execution->tradePosition, null, 'paper-'.$tag.'-foreign-close'));
    verify('Foreign close preserves owner ledger', snapshotPaper($user) === $before);
    $other->update(['is_production_demo'=>true]);
    config(['release.production_demo.read_only'=>true]);
    rejected('Protected showcase cannot open trades', fn () => $broker->placeMarketOrder(
        $other, $instrument, 'sell', 1, 'units', 'paper-'.$tag.'-showcase'));
    $broker->placePositionClose($user, $owned->execution->tradePosition, null, 'paper-'.$tag.'-owned-exit');

    $lastInstrument = $instrument;
    $before = snapshotPaper($user);
    MarketExecutionTransaction::creating(function () { throw new RuntimeException('Intentional receipt-write failure'); });
    try {
        rejected('Receipt failure rejects order', fn () => $broker->placeMarketOrder($user, $lastInstrument, 'buy', 1, 'units', 'paper-'.$tag.'-rollback'));
    } finally { MarketExecutionTransaction::flushEventListeners(); }
    verify('Receipt failure rolls back wallet/position/order together', snapshotPaper($user) === $before);

    config(['paper_trading.fee_basis_points'=>10]);
    $feed->update(['current_price'=>100]);
    $feeOrder = $broker->placeMarketOrder($user, $lastInstrument, 'buy', 1, 'units', 'paper-'.$tag.'-fees');
    equalMoney('Entry fee debited', (float)$user->wallet()->first()->balance, $expectedBalance-0.1);
    $broker->placePositionClose($user, $feeOrder->execution->tradePosition, null, 'paper-'.$tag.'-fees-exit');
    $expectedBalance -= 0.2;
    equalMoney('Entry and exit fees settled once', (float)$user->wallet()->first()->balance, $expectedBalance);
    config(['paper_trading.fee_basis_points'=>0]);
    $gap = $broker->placeMarketOrder($user, $lastInstrument, 'sell', 1, 'units', 'paper-'.$tag.'-gap');
    $feed->update(['current_price'=>300]);
    $gapExit = $broker->placePositionClose($user, $gap->execution->tradePosition, null, 'paper-'.$tag.'-gap-exit');
    $expectedBalance -= 100;
    equalMoney('Isolated synthetic loss limited to collateral', (float)$user->wallet()->first()->balance, $expectedBalance);
    equalMoney('Uncollectible gap recorded explicitly', (float)$gapExit->execution->metadata['uncollectible_gap_minor'], 10000);
    equalMoney('No orphaned reservation', (float)$user->wallet()->first()->reserved_balance, 0);

    // Cross-currency marks and fills must use the same settlement currency and basis.
    $other->forceFill(['is_production_demo'=>false])->saveQuietly();
    $other->wallet()->first()->update(['currency'=>'EUR', 'balance'=>1000, 'reserved_balance'=>0]);
    \App\Models\CurrencyRate::updateOrCreate(['currency'=>'EUR'], ['rate'=>0.9, 'last_updated'=>now()]);
    $feed->update(['current_price'=>100]);
    $fx = $broker->placeMarketOrder($other, $lastInstrument, 'sell', 1, 'units', 'paper-'.$tag.'-eur');
    $fxPosition = $fx->execution->tradePosition;
    equalMoney('Cross-currency collateral converted', (float)$other->wallet()->first()->reserved_balance, 90);
    $app->make(BrokerPositionService::class)->updateRisk($other, $fxPosition, 10, 20, 15);
    $fxPosition->refresh();
    equalMoney('Risk update retains short stop orientation', (float)$fxPosition->stop_loss_price, 110);
    equalMoney('Risk update retains short target orientation', (float)$fxPosition->take_profit_price, 80);
    $feed->update(['current_price'=>90]);
    equalMoney('Cross-currency short mark in EUR', (float)$fxPosition->current_profit_loss, 9);
    equalMoney('Return uses settlement collateral basis', (float)$fxPosition->current_return_percent, 10);
    $broker->placePositionClose($other, $fxPosition, null, 'paper-'.$tag.'-eur-exit');
    equalMoney('Cross-currency settlement matches mark', (float)$other->wallet()->first()->balance, 1009);
    \App\Models\CurrencyRate::where('currency','EUR')->update(['last_updated'=>now()->subDays(10)]);
    $beforeOther = snapshotPaper($other);
    rejected('Stale conversion blocks opening', fn () => $broker->placeMarketOrder(
        $other, $lastInstrument, 'sell', 1, 'units', 'paper-'.$tag.'-stale-rate'));
    verify('Stale conversion rejection preserves funds', snapshotPaper($other) === $beforeOther);

    $connection->rollBack();
    verify('Fixture user rolled back', !User::where('id', $initialRows)->exists());
    echo 'Paper ledger acceptance: '.$checks.' checks; 0 failures. Fixture changes rolled back.'.PHP_EOL;
} catch (Throwable $error) {
    while ($connection->transactionLevel() > 0) { $connection->rollBack(); }
    fwrite(STDERR, 'Acceptance stopped: '.get_class($error).' in '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
    if ($error instanceof PDOException || $error instanceof Illuminate\Database\QueryException) {
        $info = $error->errorInfo ?? [];
        fwrite(STDERR, 'Database error code: '.(string)$error->getCode().' driver='.(string)($info[1] ?? 'unknown').' (credentials omitted)'.PHP_EOL);
        if (preg_match('/(?:Column|Field) [\'`]([A-Za-z0-9_]+)[\'`]/i', $error->getMessage(), $field)) {
            fwrite(STDERR, 'Schema field: '.$field[1].PHP_EOL);
        }
    } else {
        fwrite(STDERR, $error->getMessage().PHP_EOL);
    }
    exit(1);
}
