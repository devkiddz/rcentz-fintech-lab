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
    'market_execution_transactions', 'market_environments', 'controlled_market_instruments', 'currency_rates',
    'copy_trader_profiles', 'copy_strategies', 'copy_relationships', 'copy_trade_executions', 'kycs',
    'trading_bots', 'trading_bot_executions', 'bot_products', 'bot_subscriptions', 'market_holdings', 'forex_pairs', 'payment_methods'];
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

    // Stage 3 extends the complete 94-check ledger suite with real lifecycle settlement.
    config(['paper_trading.lifecycle_enabled'=>true, 'paper_trading.fee_basis_points'=>0]);
    $lifecycle = $app->make(\App\Services\PaperTrading\PaperLifecycleService::class);
    foreach (['stock', 'forex', 'crypto'] as $asset) {
        $symbol = 'LC'.strtoupper(substr($asset, 0, 2)).$tag;
        $instrument = MarketInstrument::create(['symbol'=>$symbol, 'display_symbol'=>$symbol,
            'name'=>'Lifecycle Acceptance '.$asset, 'asset_class'=>$asset,
            'base_asset'=>$asset === 'forex' ? 'EUR' : $symbol,
            'quote_asset'=>'USD', 'price_precision'=>8, 'is_active'=>true, 'is_featured'=>false]);
        $feed = ControlledMarketInstrument::create(['market_instrument_id'=>$instrument->id,
            'stock_id'=>null, 'symbol'=>$symbol, 'label'=>'Lifecycle Acceptance', 'asset_class'=>$asset,
            'current_price'=>100, 'previous_price'=>100, 'opening_price'=>100, 'high'=>100, 'low'=>100,
            'decimal_precision'=>8, 'minimum_tick'=>0.00000001, 'minimum_price'=>0.00000001,
            'volatility_percent'=>0, 'individual_bias'=>0, 'is_active'=>true]);
        foreach (['buy'=>'long', 'sell'=>'short'] as $side=>$direction) {
            foreach (['stop_loss', 'take_profit', 'time_expiry'] as $reason) {
                $feed->update(['current_price'=>100]);
                $opening = $broker->placeMarketOrder($user, $instrument, $side, 2, 'units',
                    'lifecycle-'.$tag.'-'.$asset.'-'.$side.'-'.$reason,
                    ['stop_loss_percent'=>10, 'take_profit_percent'=>20, 'duration_minutes'=>15]);
                $position = $opening->execution->tradePosition;
                $before = snapshotPaper($user);
                $stats = $lifecycle->process([$position->id], 'controlled');
                verify($asset.' '.$direction.' no premature exit', $stats['unchanged'] === 1 && $stats['closed'] === 0);
                verify($asset.' '.$direction.' untriggered ledger unchanged', snapshotPaper($user) === $before);
                if ($reason === 'time_expiry') {
                    $position->update(['expires_at'=>now()->subSecond()]);
                    $price = 100;
                } else {
                    $price = $reason === 'stop_loss'
                        ? ($direction === 'long' ? 90 : 110)
                        : ($direction === 'long' ? 120 : 80);
                }
                $feed->update(['current_price'=>$price]);
                $stats = $lifecycle->process([$position->id], 'controlled');
                verify($asset.' '.$direction.' '.$reason.' closes once', $stats[$reason] === 1 && $stats['closed'] === 1 && $stats['failed'] === 0);
                verify($asset.' '.$direction.' exit reason retained', $position->refresh()->exit_reason === $reason);
                $exit = $position->lastExitMarketExecutionTransaction;
                verify($asset.' '.$direction.' automatic side is opposite opening', $exit->side === ($side === 'buy' ? 'sell' : 'buy'));
                verify($asset.' '.$direction.' close intent on receipt', $exit->metadata['order_intent'] === 'close');
                $expectedBalance += $reason === 'stop_loss' ? -20 : ($reason === 'take_profit' ? 40 : 0);
                equalMoney($asset.' '.$direction.' '.$reason.' wallet settlement', (float)$user->wallet()->first()->balance, $expectedBalance);
                equalMoney($asset.' '.$direction.' final reserve released', (float)$user->wallet()->first()->reserved_balance, 0);
                $before = snapshotPaper($user);
                $lifecycle->process([$position->id], 'controlled');
                verify($asset.' '.$direction.' repeated pass unchanged', snapshotPaper($user) === $before);
                $again = $paper->closeTriggered($user, $position, 'paper-auto-close-'.$position->id);
                verify($asset.' '.$direction.' direct automatic replay', $again->execution->id === $exit->id);
                verify($asset.' '.$direction.' automatic replay unchanged', snapshotPaper($user) === $before);
            }
        }
    }
    // Partial customer closure followed by an automatic exit settles only remaining exposure.
    $feed->update(['current_price'=>100]);
    $partial = $broker->placeMarketOrder($user, $instrument, 'sell', 2, 'units', 'lifecycle-'.$tag.'-partial',
        ['take_profit_percent'=>20]);
    $position = $partial->execution->tradePosition;
    $feed->update(['current_price'=>90]);
    $broker->placePositionClose($user, $position, 1, 'lifecycle-'.$tag.'-partial-close');
    $expectedBalance += 10;
    $feed->update(['current_price'=>80]);
    $stats = $lifecycle->process([$position->id]);
    $expectedBalance += 20;
    verify('Partial short remainder exits at target', $stats['take_profit'] === 1);
    equalMoney('Partial plus automatic settlement conserved', (float)$user->wallet()->first()->balance, $expectedBalance);
    equalMoney('Partial automatic final reserve released', (float)$user->wallet()->first()->reserved_balance, 0);

    $feed->update(['current_price'=>100]);
    $gap = $broker->placeMarketOrder($user, $instrument, 'sell', 1, 'units', 'lifecycle-'.$tag.'-margin');
    $feed->update(['current_price'=>300]);
    $stats = $lifecycle->process([$gap->execution->tradePosition->id]);
    $expectedBalance -= 100;
    verify('Collateral exhaustion closes with margin reason', $stats['margin_exhausted'] === 1);
    equalMoney('Automatic gap obeys isolated limit', (float)$user->wallet()->first()->balance, $expectedBalance);

    // A blocked/invalid quote must leave the expired position intact and retry later.
    $feed->update(['current_price'=>100]);
    $deferred = $broker->placeMarketOrder($user, $instrument, 'buy', 1, 'units', 'lifecycle-'.$tag.'-deferred',
        ['duration_minutes'=>1]);
    $deferredPosition = $deferred->execution->tradePosition;
    $deferredPosition->update(['expires_at'=>now()->subSecond()]);
    $feed->update(['current_price'=>0]);
    $before = snapshotPaper($user);
    $stats = $lifecycle->process([$deferredPosition->id]);
    verify('Bad quote defers expired position', $stats['failed'] === 1 && $deferredPosition->refresh()->is_open);
    verify('Bad quote preserves all accounting', snapshotPaper($user) === $before);
    $feed->update(['current_price'=>100]);
    $stats = $lifecycle->process([$deferredPosition->id]);
    verify('Expiry retries after valid quote returns', $stats['time_expiry'] === 1 && !$deferredPosition->refresh()->is_open);

    // Scope isolation: inactive instruments fail independently; healthy later positions still close.
    $bad = $broker->placeMarketOrder($user, $instrument, 'buy', 1, 'units', 'lifecycle-'.$tag.'-bad', ['duration_minutes'=>1]);
    $badPosition = $bad->execution->tradePosition;
    $badPosition->update(['expires_at'=>now()->subSecond()]);
    $healthyInstrument = MarketInstrument::where('symbol', 'LCST'.$tag)->firstOrFail();
    $healthy = $broker->placeMarketOrder($user, $healthyInstrument, 'sell', 1, 'units', 'lifecycle-'.$tag.'-healthy', ['duration_minutes'=>1]);
    $healthyPosition = $healthy->execution->tradePosition;
    $healthyPosition->update(['expires_at'=>now()->subSecond()]);
    $instrument->update(['is_active'=>false]);
    $stats = $lifecycle->process([$badPosition->id, $healthyPosition->id]);
    verify('One position failure does not abort later exits', $stats['failed'] === 1 && $stats['closed'] === 1);
    verify('Failed instrument retains its open position', $badPosition->refresh()->is_open);
    $instrument->update(['is_active'=>true]);
    $lifecycle->process([$badPosition->id]);
    equalMoney('Recovery preserves expected balance', (float)$user->wallet()->first()->balance, $expectedBalance);

    // A previously read position must not override newer risk controls in the database.
    $feed->update(['current_price'=>100]);
    $updated = $broker->placeMarketOrder($user, $instrument, 'sell', 1, 'units', 'lifecycle-'.$tag.'-updated-risk',
        ['take_profit_percent'=>20]);
    $stalePosition = $updated->execution->tradePosition;
    $paper->updateRisk($user, $stalePosition, null, null, null);
    $feed->update(['current_price'=>80]);
    $before = snapshotPaper($user);
    $none = $paper->closeTriggered($user, $stalePosition, 'paper-auto-close-'.$stalePosition->id);
    verify('Latest locked risk controls prevent obsolete exit', $none === null && snapshotPaper($user) === $before);
    $feed->update(['current_price'=>100]);
    $broker->placePositionClose($user, $stalePosition, null, 'lifecycle-'.$tag.'-updated-risk-cleanup');

    $fault = $broker->placeMarketOrder($user, $instrument, 'sell', 1, 'units', 'lifecycle-'.$tag.'-receipt-fault',
        ['take_profit_percent'=>20]);
    $faultPosition = $fault->execution->tradePosition;
    $feed->update(['current_price'=>80]);
    $before = snapshotPaper($user);
    MarketExecutionTransaction::creating(function () { throw new RuntimeException('Intentional automatic receipt failure'); });
    try { $stats = $lifecycle->process([$faultPosition->id]); }
    finally { MarketExecutionTransaction::flushEventListeners(); }
    verify('Automatic receipt failure counted', $stats['failed'] === 1 && $stats['closed'] === 0);
    verify('Automatic receipt failure preserves open position and funds', $faultPosition->refresh()->is_open && snapshotPaper($user) === $before);
    $stats = $lifecycle->process([$faultPosition->id]);
    $expectedBalance += 20;
    verify('Automatic receipt failure is retryable', $stats['take_profit'] === 1 && $stats['failed'] === 0);
    equalMoney('Retry settles automatic profit once', (float)$user->wallet()->first()->balance, $expectedBalance);
    $feed->update(['current_price'=>100]);

    $legacy = $broker->placeMarketOrder($user, $instrument, 'buy', 1, 'units', 'lifecycle-'.$tag.'-legacy-test');
    $legacyPosition = $legacy->execution->tradePosition;
    $metadata = $legacyPosition->metadata;
    unset($metadata['execution_model']);
    $legacyPosition->update(['metadata'=>$metadata, 'expires_at'=>now()->subSecond()]);
    $before = snapshotPaper($user);
    $stats = $lifecycle->process([$legacyPosition->id]);
    verify('Legacy records excluded from paper lifecycle', $stats['checked'] === 0 && snapshotPaper($user) === $before);
    $legacyPosition->update(['metadata'=>array_merge($metadata, ['execution_model'=>PaperBrokerService::MODEL])]);
    $broker->placePositionClose($user, $legacyPosition->refresh(), null, 'lifecycle-'.$tag.'-legacy-cleanup');

    $copy = $broker->placeMarketOrder($user, $instrument, 'sell', 1, 'units', 'lifecycle-'.$tag.'-copy-test', [],
        ['context_type'=>'copy_strategy', 'context_id'=>null]);
    $copyPosition = $copy->execution->tradePosition;
    $copyPosition->update(['expires_at'=>now()->subSecond()]);
    $before = snapshotPaper($user);
    $stats = $lifecycle->process([$copyPosition->id]);
    verify('Copy context deferred until integration', $stats['checked'] === 0 && snapshotPaper($user) === $before);
    $broker->placePositionClose($user, $copyPosition->refresh(), null, 'lifecycle-'.$tag.'-copy-cleanup');
    config(['paper_trading.lifecycle_enabled'=>false]);
    $before = snapshotPaper($user);
    rejected('Normal lifecycle activation remains gated', fn () => $lifecycle->process([]));
    verify('Disabled lifecycle does not mutate accounting', snapshotPaper($user) === $before);
    equalMoney('All lifecycle collateral released', (float)$user->wallet()->first()->reserved_balance, 0);

    config(['paper_trading.enabled'=>true, 'paper_trading.copy_enabled'=>true]);
    $copyService = $app->make(\App\Services\CopyTradingService::class);
    $paperCopy = $app->make(\App\Services\PaperTrading\PaperCopyTradingService::class);
    $follower = User::withoutEvents(fn () => User::create([
        'name'=>'Copy Acceptance', 'email'=>'copy-'.$tag.'@example.invalid',
        'password'=>bin2hex(random_bytes(24)), 'email_verified_at'=>now(),
        'is_admin'=>false, 'is_production_demo'=>false, 'account_status'=>'active',
        'country'=>'Nigeria', 'currency'=>'USD',
    ]));
    Wallet::create(['user_id'=>$follower->id, 'balance'=>10000, 'reserved_balance'=>0, 'currency'=>'USD']);
    // Synthetic KYC fixture only; no real identity documents, notifications or uploads.
    $kyc = \App\Models\KYC::withoutEvents(fn () => \App\Models\KYC::create([
        'user_id'=>$follower->id, 'first_name'=>'Copy', 'last_name'=>'Acceptance',
        'date_of_birth'=>'2000-01-01', 'nationality'=>'Nigerian', 'document_type'=>'passport',
        'document_number'=>'SYNTHETIC-'.$tag, 'document_expiry_date'=>'2030-01-01',
        'address_line_1'=>'Synthetic fixture', 'city'=>'Warri', 'state_province'=>'Delta',
        'postal_code'=>'000000', 'country'=>'Nigeria', 'phone_number'=>'00000000000',
        'document_front_path'=>'fixture-not-a-document', 'document_back_path'=>'fixture-not-a-document',
        'selfie_path'=>'fixture-not-a-document', 'status'=>'approved', 'submitted_at'=>now(), 'verified_at'=>now(),
    ]));
    $profile = \App\Models\CopyTraderProfile::create(['user_id'=>$user->id,
        'strategy_name'=>'Synthetic Acceptance', 'bio'=>'Local test only', 'risk_level'=>'low',
        'is_public'=>false, 'is_accepting_copiers'=>true, 'approved_at'=>now()]);
    $strategy = \App\Models\CopyStrategy::create(['copy_trader_profile_id'=>$profile->id,
        'name'=>'Copy Acceptance '.$tag, 'description'=>'Local synthetic fixture', 'risk_level'=>'low',
        'minimum_allocation'=>0, 'recommended_allocation'=>100, 'is_public'=>false, 'is_active'=>true]);
    $relationship = \App\Models\CopyRelationship::create(['follower_id'=>$follower->id,
        'provider_id'=>$user->id, 'copy_strategy_id'=>$strategy->id, 'allocation_limit'=>1000,
        'used_amount'=>0, 'max_trade_amount'=>50, 'copy_ratio_percent'=>50, 'status'=>'active', 'started_at'=>now()]);
    $followerExpected = 10000.0;
    foreach (['stock','forex','crypto'] as $asset) {
        $instrument = MarketInstrument::where('symbol','LC'.strtoupper(substr($asset,0,2)).$tag)->firstOrFail();
        $feed = ControlledMarketInstrument::where('market_instrument_id',$instrument->id)->firstOrFail();
        foreach (['buy'=>'long','sell'=>'short'] as $side=>$direction) {
            $strategy->update(['is_active'=>true]);
            $relationship->update(['status'=>'active','ends_at'=>null,'used_amount'=>0]);
            $feed->update(['current_price'=>100]);
            $opening = $broker->placeMarketOrder($user,$instrument,$side,2,'units',
                'copy-'.$tag.'-'.$asset.'-'.$side, ['stop_loss_percent'=>10,'take_profit_percent'=>20],
                ['context_type'=>'copy_strategy','context_id'=>$strategy->id]);
            $source = $opening->execution->tradePosition;
            $copyService->mirrorCompletedExecution($opening->execution,$strategy->id);
            $record = \App\Models\CopyTradeExecution::where('copy_relationship_id',$relationship->id)
                ->where('provider_market_execution_transaction_id',$opening->execution->id)->firstOrFail();
            verify($asset.' '.$direction.' copy entry completed', $record->status === 'completed');
            $linked = $record->followerMarketExecution->tradePosition;
            verify($asset.' '.$direction.' copied direction', $linked->direction === $direction);
            verify($asset.' '.$direction.' exact source position link', (int)$linked->source_position_id === (int)$source->id);
            equalMoney($asset.' capped entry quantity', (float)$linked->open_quantity,0.5);
            equalMoney($asset.' capped allocation reserved', (float)$relationship->refresh()->used_amount,50);
            equalMoney($asset.' entry does not credit wallet proceeds',(float)$follower->wallet()->first()->balance,$followerExpected);
            $before = snapshotPaper($follower);
            $copyService->mirrorCompletedExecution($opening->execution,$strategy->id);
            verify($asset.' opening copy replay accounting unchanged',snapshotPaper($follower) === $before);
            equalMoney($asset.' replay allocation unchanged',(float)$relationship->refresh()->used_amount,50);

            $feed->update(['current_price'=>$direction === 'long' ? 110 : 90]);
            $partial = $broker->placePositionClose($user,$source,1,'copy-'.$tag.'-'.$asset.'-'.$side.'-partial');
            // Exits must work with zero new-entry caps and paused/expired eligibility.
            $strategy->update(['is_active'=>false]);
            $relationship->update(['max_trade_amount'=>0,'status'=>'paused','ends_at'=>now()->subMinute()]);
            $copyService->mirrorCompletedExecution($partial->execution,$strategy->id);
            equalMoney($asset.' partial follower exposure',(float)$linked->refresh()->open_quantity,0.25);
            equalMoney($asset.' partial allocation release',(float)$relationship->refresh()->used_amount,25);
            $followerExpected += 2.5;
            equalMoney($asset.' partial copied P/L',(float)$follower->wallet()->first()->balance,$followerExpected);
            $partialRecord = \App\Models\CopyTradeExecution::where('copy_relationship_id',$relationship->id)
                ->where('provider_market_execution_transaction_id',$partial->execution->id)->firstOrFail();
            verify($asset.' close uses opposite side without new entry', $partialRecord->followerMarketExecution->side === ($side === 'buy' ? 'sell' : 'buy'));
            $before = snapshotPaper($follower);
            $copyService->mirrorCompletedExecution($partial->execution,$strategy->id);
            verify($asset.' partial replay does not repeat settlement',snapshotPaper($follower) === $before);
            equalMoney($asset.' partial replay allocation unchanged',(float)$relationship->refresh()->used_amount,25);

            $feed->update(['current_price'=>$direction === 'long' ? 120 : 80]);
            $full = $broker->placePositionClose($user,$source,null,'copy-'.$tag.'-'.$asset.'-'.$side.'-full');
            $copyService->mirrorCompletedExecution($full->execution,$strategy->id);
            $followerExpected += 5;
            verify($asset.' expired relationship receives full exit',!$linked->refresh()->is_open);
            equalMoney($asset.' final allocation released',(float)$relationship->refresh()->used_amount,0);
            equalMoney($asset.' copied final P/L',(float)$follower->wallet()->first()->balance,$followerExpected);
            $before = snapshotPaper($follower);
            $copyService->mirrorCompletedExecution($full->execution,$strategy->id);
            verify($asset.' full replay cannot create another order',snapshotPaper($follower) === $before);
            $relationship->update(['max_trade_amount'=>50]);
        }
    }
    $strategy->update(['is_active'=>true]);
    $relationship->update(['status'=>'active','ends_at'=>null,'max_trade_amount'=>1000,'copy_ratio_percent'=>50]);
    $feed->update(['current_price'=>100]);
    $opening = $broker->placeMarketOrder($user,$instrument,'sell',2,'units','copy-'.$tag.'-record-failure',[],
        ['context_type'=>'copy_strategy','context_id'=>$strategy->id]);
    $before = snapshotPaper($follower);
    \App\Models\CopyTradeExecution::creating(function () { throw new RuntimeException('Intentional copy record failure'); });
    try { $stats = $paperCopy->mirror($opening->execution,$strategy->id); }
    finally { \App\Models\CopyTradeExecution::flushEventListeners(); }
    verify('Copy record failure reported', $stats['failed'] === 1);
    verify('Copy record failure rolls back follower financial writes',snapshotPaper($follower) === $before);
    equalMoney('Copy record failure leaves allocation untouched',(float)$relationship->refresh()->used_amount,0);
    $stats = $paperCopy->mirror($opening->execution,$strategy->id);
    verify('Copy record failure is retryable',$stats['completed'] === 1);
    equalMoney('Retry reserves allocation once',(float)$relationship->refresh()->used_amount,100);
    $exit = $broker->placePositionClose($user,$opening->execution->tradePosition,null,'copy-'.$tag.'-record-failure-exit');
    $paperCopy->mirror($exit->execution,$strategy->id);

    // Exact linkage: an unmatched provider exit cannot consume a different follower position.
    $a = $broker->placeMarketOrder($user,$instrument,'buy',1,'units','copy-'.$tag.'-linked-a',[],
        ['context_type'=>'copy_strategy','context_id'=>$strategy->id]);
    $paperCopy->mirror($a->execution,$strategy->id);
    $b = $broker->placeMarketOrder($user,$instrument,'buy',1,'units','copy-'.$tag.'-unlinked-b',[],
        ['context_type'=>'copy_strategy','context_id'=>$strategy->id]);
    $bExit = $broker->placePositionClose($user,$b->execution->tradePosition,null,'copy-'.$tag.'-unlinked-b-exit');
    $before = snapshotPaper($follower);
    $stats = $paperCopy->mirror($bExit->execution,$strategy->id);
    verify('Unmatched provider exit cannot close another source position',snapshotPaper($follower) === $before && $stats['completed'] === 0);
    $aExit = $broker->placePositionClose($user,$a->execution->tradePosition,null,'copy-'.$tag.'-linked-a-exit');
    $paperCopy->mirror($aExit->execution,$strategy->id);

    // Cross-currency entry sizing preserves wallet-currency allocation limits.
    \App\Models\CurrencyRate::updateOrCreate(['currency'=>'EUR'],['rate'=>0.9,'last_updated'=>now()]);
    $follower->wallet()->first()->update(['currency'=>'EUR','balance'=>1000,'reserved_balance'=>0]);
    $fx = $broker->placeMarketOrder($user,$instrument,'sell',2,'units','copy-'.$tag.'-eur',[],
        ['context_type'=>'copy_strategy','context_id'=>$strategy->id]);
    $paperCopy->mirror($fx->execution,$strategy->id);
    equalMoney('EUR follower allocation converts provider capital',(float)$relationship->refresh()->used_amount,90);
    $feed->update(['current_price'=>90]);
    $fxExit = $broker->placePositionClose($user,$fx->execution->tradePosition,null,'copy-'.$tag.'-eur-exit');
    $paperCopy->mirror($fxExit->execution,$strategy->id);
    equalMoney('EUR copied short profit settled',(float)$follower->wallet()->first()->balance,1009);
    equalMoney('EUR copy allocation fully released',(float)$relationship->refresh()->used_amount,0);
    $feed->update(['current_price'=>100]);

    $kyc->update(['status'=>'pending']);
    $blocked = $broker->placeMarketOrder($user,$instrument,'sell',1,'units','copy-'.$tag.'-kyc',[],
        ['context_type'=>'copy_strategy','context_id'=>$strategy->id]);
    $before = snapshotPaper($follower);
    $stats = $paperCopy->mirror($blocked->execution,$strategy->id);
    verify('Unapproved follower cannot open copy position',$stats['skipped'] === 1 && snapshotPaper($follower) === $before);
    $broker->placePositionClose($user,$blocked->execution->tradePosition,null,'copy-'.$tag.'-kyc-exit');
    $kyc->update(['status'=>'approved']);

    $ownership = $broker->placeMarketOrder($user,$instrument,'sell',1,'units','copy-'.$tag.'-wrong-strategy',[],
        ['context_type'=>'copy_strategy','context_id'=>$strategy->id]);
    $before = snapshotPaper($follower);
    rejected('Wrong strategy cannot be supplied for provider receipt',fn () => $paperCopy->mirror($ownership->execution,$strategy->id+9999));
    verify('Wrong strategy preserves follower funds',snapshotPaper($follower) === $before);
    config(['paper_trading.copy_enabled'=>false]);
    rejected('Paper receipt cannot fall through legacy copy logic',fn () => $copyService->mirrorCompletedExecution($ownership->execution,$strategy->id));
    verify('Disabled paper copy preserves follower funds',snapshotPaper($follower) === $before);
    $broker->placePositionClose($user,$ownership->execution->tradePosition,null,'copy-'.$tag.'-wrong-strategy-exit');
    equalMoney('Final copy collateral released',(float)$follower->wallet()->first()->reserved_balance,0);

    config(['paper_trading.enabled'=>true,'paper_trading.copy_enabled'=>true,
        'paper_trading.lifecycle_enabled'=>true,'paper_trading.bots_enabled'=>true]);
    // Automatic provider exit and durable retry after a follower quote failure.
    $strategy->update(['is_active'=>true]);
    $relationship->update(['status'=>'active','ends_at'=>null,'max_trade_amount'=>1000,'used_amount'=>0]);
    $feed->update(['current_price'=>100]);
    $provider = $broker->placeMarketOrder($user,$instrument,'sell',2,'units','automation-'.$tag.'-provider',
        ['take_profit_percent'=>20],['context_type'=>'copy_strategy','context_id'=>$strategy->id]);
    $source = $provider->execution->tradePosition;
    $paperCopy->mirror($provider->execution,$strategy->id);
    $feed->update(['current_price'=>80]);
    \App\Models\CurrencyRate::where('currency','EUR')->update(['last_updated'=>now()->subDays(10)]);
    $stats = $lifecycle->process([$source->id]);
    verify('Provider short target exits automatically',$stats['take_profit'] === 1 && !$source->refresh()->is_open);
    verify('Follower conversion failure remains pending',$stats['copy_retry']['failed'] === 1);
    equalMoney('Pending follower retains allocation',(float)$relationship->refresh()->used_amount,90);
    \App\Models\CurrencyRate::where('currency','EUR')->update(['last_updated'=>now()]);
    $stats = $lifecycle->process([$source->id]);
    verify('Closed-provider receipt retries without another provider trade',$stats['checked'] === 0 && $stats['copy_retry']['completed'] === 1);
    equalMoney('Retry releases final follower allocation',(float)$relationship->refresh()->used_amount,0);
    equalMoney('Copied automatic short profit settles in EUR',(float)$follower->wallet()->first()->balance,1027);
    $before = snapshotPaper($follower);
    $lifecycle->process([$source->id]);
    verify('Automatic copy receipt replay is financially unchanged',snapshotPaper($follower) === $before);

    // Copy contract expiry closes only its linked follower, releasing allocation.
    $feed->update(['current_price'=>100]);
    $provider = $broker->placeMarketOrder($user,$instrument,'buy',2,'units','automation-'.$tag.'-copy-expiry',[],
        ['context_type'=>'copy_strategy','context_id'=>$strategy->id]);
    $paperCopy->mirror($provider->execution,$strategy->id);
    $linked = \App\Models\TradePosition::where('context_type','copy_relationship')->where('context_id',$relationship->id)
        ->where('source_position_id',$provider->execution->trade_position_id)->firstOrFail();
    $relationship->update(['ends_at'=>now()->subMinute()]);
    $stats = $lifecycle->process([$linked->id]);
    verify('Expired copy contract settles its follower position',$stats['copy_contract_expiry'] === 1 && !$linked->refresh()->is_open);
    equalMoney('Expired copy contract releases allocation',(float)$relationship->refresh()->used_amount,0);
    verify('Follower contract expiry leaves provider position open',$provider->execution->tradePosition->refresh()->is_open);
    $broker->placePositionClose($user,$provider->execution->tradePosition,null,'automation-'.$tag.'-copy-expiry-cleanup');

    // The gateway stub isolates bot logic. Production FeatureAccessService remains unchanged.
    $gateway = new class extends \App\Services\FeatureAccessService {
        public bool $allow = true;
        public function __construct() {}
        public function allows(User $user,string $key): bool { return $this->allow; }
    };
    $app->instance(\App\Services\FeatureAccessService::class,$gateway);
    $app->forgetInstance(\App\Services\PaperTrading\PaperBotService::class);
    $botService = $app->make(\App\Services\PaperTrading\PaperBotService::class);
    $follower->wallet()->first()->update(['currency'=>'USD','balance'=>10000,'reserved_balance'=>0]);
    $botExpected = 10000.0;
    foreach (['stock','forex','crypto'] as $asset) {
        $instrument = MarketInstrument::where('symbol','LC'.strtoupper(substr($asset,0,2)).$tag)->firstOrFail();
        $feed = ControlledMarketInstrument::where('market_instrument_id',$instrument->id)->firstOrFail();
        foreach (['open_long'=>'buy','open_short'=>'sell'] as $intent=>$side) {
            $feed->update(['current_price'=>100]);
            $product = \App\Models\BotProduct::create(['created_by'=>$user->id,'market_instrument_id'=>$instrument->id,
                'name'=>'Automation Acceptance','slug'=>'auto-'.$tag.'-'.$asset.'-'.$intent,'description'=>'Local fixture only',
                'strategy'=>'dca','action'=>$side,'risk_level'=>'low','price'=>0,'billing_period'=>'monthly',
                'minimum_balance'=>0,'max_user_allocation'=>1000,'default_interval_minutes'=>5,
                'default_max_daily_trades'=>24,'default_trade_amount'=>100,'allow_user_trade_amount'=>true,
                'allow_user_trigger_price'=>true,'is_active'=>true]);
            $bot = \App\Models\TradingBot::create(['user_id'=>$follower->id,'market_instrument_id'=>$instrument->id,
                'name'=>'Automation Acceptance','strategy'=>'dca','action'=>$side,'paper_intent'=>$intent,
                'quantity_per_trade'=>1,'amount_per_trade'=>null,'interval_minutes'=>5,'max_daily_trades'=>24,
                'max_total_spend'=>1000,'spent_total'=>0,'status'=>'active','next_run_at'=>now()->subMinute(),
                'stop_loss_percent'=>10,'take_profit_percent'=>20]);
            $subscription = \App\Models\BotSubscription::create(['user_id'=>$follower->id,'bot_product_id'=>$product->id,
                'trading_bot_id'=>$bot->id,'price_paid'=>0,'status'=>'active','starts_at'=>now()->subDay(),'ends_at'=>now()->addMonth()]);
            $key = 'accept-'.$tag.'-'.$asset.'-'.$intent;
            $record = $botService->run($bot,true,$key);
            verify($asset.' '.$intent.' bot completes',$record->status === 'completed');
            $position = $record->marketExecution->tradePosition;
            verify($asset.' bot direction matches explicit intent',$position->direction === ($side === 'buy' ? 'long' : 'short'));
            equalMoney($asset.' both directions charge collateral budget',(float)$bot->refresh()->spent_total,100);
            equalMoney($asset.' opening leaves synthetic balance unchanged',(float)$follower->wallet()->first()->balance,$botExpected);
            $before = snapshotPaper($follower);
            $again = $botService->run($bot,true,$key);
            verify($asset.' bot replay returns same log',$again->id === $record->id && snapshotPaper($follower) === $before);
            equalMoney($asset.' bot replay spend unchanged',(float)$bot->refresh()->spent_total,100);
            // Closing does not need an active subscription or opening entitlement.
            $subscription->update(['status'=>'expired','ends_at'=>now()->subMinute()]);
            $gateway->allow = false;
            $bot->update(['paper_intent'=>'close','quantity_per_trade'=>0.5]);
            $feed->update(['current_price'=>$side === 'buy' ? 110 : 90]);
            $closed = $botService->run($bot,true,$key.'-partial-close');
            $botExpected += 5;
            verify($asset.' expired bot can reduce exposure',$closed->status === 'completed');
            verify($asset.' bot close uses opposite side',$closed->action === ($side === 'buy' ? 'sell' : 'buy'));
            equalMoney($asset.' partial bot P/L',(float)$follower->wallet()->first()->balance,$botExpected);
            equalMoney($asset.' partial bot remaining exposure',(float)$position->refresh()->open_quantity,0.5);
            $stats = $lifecycle->process([$position->id]);
            $botExpected += 5;
            verify($asset.' subscription expiry automatically settles remainder',$stats['bot_subscription_expiry'] === 1);
            equalMoney($asset.' expired remainder P/L',(float)$follower->wallet()->first()->balance,$botExpected);
            equalMoney($asset.' expired bot final reserve',(float)$follower->wallet()->first()->reserved_balance,0);
            $gateway->allow = true;
        }
    }
    // Use the last bot as a guard/atomicity fixture with a renewed synthetic subscription.
    $subscription->update(['status'=>'active','starts_at'=>now()->subDay(),'ends_at'=>now()->addMonth()]);
    $bot->update(['paper_intent'=>'open_short','quantity_per_trade'=>1,'max_daily_trades'=>24,'max_total_spend'=>1000]);
    $feed->update(['current_price'=>100]);
    $gateway->allow = false;
    $before = snapshotPaper($follower);
    $record = $botService->run($bot,true,'accept-'.$tag.'-short-membership');
    verify('Short entries require opening entitlement',$record->status === 'skipped' && snapshotPaper($follower) === $before);
    $gateway->allow = true;
    $bot->update(['status'=>'active','max_total_spend'=>100]);
    $before = snapshotPaper($follower);
    rejected('Short collateral budget cannot be exceeded',fn () => $botService->run($bot,true,'accept-'.$tag.'-budget'));
    verify('Budget failure rolls back all financial writes',snapshotPaper($follower) === $before);
    equalMoney('Budget failure preserves spend counter',(float)$bot->refresh()->spent_total,100);
    $bot->update(['max_total_spend'=>1000,'max_daily_trades'=>1]);
    $record = $botService->run($bot,true,'accept-'.$tag.'-daily-limit');
    verify('Daily limit blocks new short entry',$record->status === 'skipped');
    $bot->update(['max_daily_trades'=>24]);
    $before = snapshotPaper($follower);
    $beforeSpend = (float)$bot->spent_total;
    \App\Models\TradingBotExecution::creating(function () { throw new RuntimeException('Intentional bot-log failure'); });
    try { rejected('Bot log failure rejects atomic order',fn () => $botService->run($bot,true,'accept-'.$tag.'-log-fault')); }
    finally { \App\Models\TradingBotExecution::flushEventListeners(); }
    verify('Bot log failure preserves wallet/order/position',snapshotPaper($follower) === $before);
    equalMoney('Bot log failure preserves collateral budget',(float)$bot->refresh()->spent_total,$beforeSpend);
    $record = $botService->run($bot,true,'accept-'.$tag.'-log-fault');
    verify('Bot log failure can retry once',$record->status === 'completed');
    $bot->update(['paper_intent'=>'open_long']);
    rejected('Bot key rejects changed intent',fn () => $botService->run($bot,true,'accept-'.$tag.'-log-fault'));
    $bot->update(['paper_intent'=>null,'action'=>'sell']);
    verify('Legacy sell bot retains close interpretation',\App\Services\PaperTrading\PaperBotService::intent($bot) === 'close');
    $bot->update(['paper_intent'=>null,'action'=>'buy']);
    verify('Legacy buy bot retains long-entry interpretation',\App\Services\PaperTrading\PaperBotService::intent($bot) === 'open_long');
    $bot->update(['paper_intent'=>'close','quantity_per_trade'=>1]);
    $botService->run($bot,true,'accept-'.$tag.'-final-close');
    equalMoney('All bot fixture collateral released',(float)$follower->wallet()->first()->reserved_balance,0);
    $before = snapshotPaper($follower);
    $bot->update(['paper_intent'=>'open_short','next_run_at'=>now()->addMinutes(5),'status'=>'active']);
    $notDue = $botService->run($bot,false,'accept-'.$tag.'-not-due');
    verify('Scheduled bot respects due time',$notDue->status === 'skipped' && snapshotPaper($follower) === $before);
    $bot->update(['strategy'=>'price_below','trigger_price'=>90,'next_run_at'=>now()->subMinute()]);
    $trigger = $botService->run($bot,true,'accept-'.$tag.'-price-trigger');
    verify('Price trigger prevents premature opening',$trigger->status === 'skipped' && snapshotPaper($follower) === $before);
    $subscription->update(['status'=>'expired','ends_at'=>now()->subMinute()]);
    $bot->update(['strategy'=>'dca']);
    $expired = $botService->run($bot,true,'accept-'.$tag.'-expired-entry');
    verify('Expired subscription blocks new entry',$expired->status === 'skipped' && snapshotPaper($follower) === $before);
    $bot->update(['paper_intent'=>'invalid']);
    rejected('Unknown bot intent is rejected',fn () => $botService->run($bot,true,'accept-'.$tag.'-invalid'));
    config(['paper_trading.bots_enabled'=>false]);
    rejected('Normal bot activation remains disabled',fn () => $app->make(\App\Services\TradingBotService::class)->run($bot,true));


    // Broker-surface acceptance uses new fixtures and real production presenters.
    // These controller checks are in-process; they are not a browser/HTTP-kernel test.
    config(['paper_trading.enabled'=>true, 'paper_trading.fee_basis_points'=>0]);
    $surfaceUser = User::withoutEvents(fn () => User::create([
        'name'=>'Surface Acceptance', 'email'=>'surface-'.$tag.'@example.invalid',
        'password'=>bin2hex(random_bytes(24)), 'email_verified_at'=>now(),
        'is_admin'=>false, 'is_production_demo'=>false, 'account_status'=>'active',
        'country'=>'Nigeria', 'currency'=>'USD',
    ]));
    Wallet::create(['user_id'=>$surfaceUser->id, 'balance'=>10000, 'reserved_balance'=>0, 'currency'=>'USD']);
    $presenter=$app->make(\App\Services\PaperTrading\PositionPresentationService::class);
    $summaryService=$app->make(\App\Services\PaperTrading\TradingAccountSummaryService::class);
    $surfacePositions=[];
    $surfaceFeeds=[];
    foreach (['stock','forex','crypto'] as $surfaceAsset) {
        $surfaceSymbol='SUR'.strtoupper(substr($surfaceAsset,0,2)).$tag;
        $surfaceInstrument=MarketInstrument::create([
            'symbol'=>$surfaceSymbol,'display_symbol'=>$surfaceSymbol,'name'=>'Surface fixture',
            'asset_class'=>$surfaceAsset,'base_asset'=>$surfaceAsset==='forex'?'EUR':$surfaceSymbol,
            'quote_asset'=>'USD','price_precision'=>4,'is_active'=>true,'is_featured'=>false,
        ]);
        $surfaceFeed=ControlledMarketInstrument::create([
            'market_instrument_id'=>$surfaceInstrument->id,'stock_id'=>null,'symbol'=>$surfaceSymbol,
            'label'=>'Surface fixture','asset_class'=>$surfaceAsset,'current_price'=>100,
            'previous_price'=>100,'opening_price'=>100,'high'=>100,'low'=>100,'decimal_precision'=>4,
            'minimum_tick'=>0.0001,'minimum_price'=>0.0001,'volatility_percent'=>0,
            'individual_bias'=>0,'is_active'=>true,
        ]);
        $surfaceLong=$broker->placeMarketOrder($surfaceUser,$surfaceInstrument,'buy',1,'units','surface-'.$tag.'-'.$surfaceAsset.'-long')->execution->tradePosition;
        $surfaceShort=$broker->placeMarketOrder($surfaceUser,$surfaceInstrument,'sell',2,'units','surface-'.$tag.'-'.$surfaceAsset.'-short')->execution->tradePosition;
        $surfaceFeed->update(['current_price'=>90]);
        $surfacePositions[$surfaceAsset]=[$surfaceLong,$surfaceShort];
        $surfaceFeeds[$surfaceAsset]=$surfaceFeed;
        $longRow=$presenter->describe($surfaceLong);
        $shortRow=$presenter->describe($surfaceShort);
        equalMoney($surfaceAsset.' surface long P/L',$longRow['pnl'],-10);
        equalMoney($surfaceAsset.' surface short P/L',$shortRow['pnl'],20);
        equalMoney($surfaceAsset.' surface parent mark',$shortRow['cmp'],90);
        verify($surfaceAsset.' surface direction label',$shortRow['direction']==='short');
        verify($surfaceAsset.' P/L currency is explicit',str_contains($shortRow['formatted_pnl'],'USD'));
    }
    $summary=$summaryService->build($surfaceUser);
    equalMoney('Account balance is not reduced by collateral',$summary['balance'],10000);
    equalMoney('Account reserved is aggregated',$summary['reserved'],900);
    equalMoney('Account available deducts reserved once',$summary['available'],9100);
    equalMoney('Account unrealized includes all three assets',$summary['unrealized_profit_loss'],30);
    equalMoney('Account equity never adds collateral twice',$summary['equity'],10030);
    verify('Account valuation complete',$summary['valuation_complete']);
    $surfaceClose=$broker->placePositionClose($surfaceUser,$surfacePositions['crypto'][1],1,'surface-'.$tag.'-partial');
    $summary=$summaryService->build($surfaceUser);
    equalMoney('Partial realization increases wallet once',$summary['balance'],10010);
    equalMoney('Partial realization reduces open P/L',$summary['unrealized_profit_loss'],20);
    equalMoney('Equity stable across partial realization',$summary['equity'],10030);
    equalMoney('Partial collateral released',$summary['reserved'],800);
    $investmentBefore=(float)$surfaceUser->wallet()->first()->total_investments;
    $broker->placePositionClose($surfaceUser,$surfacePositions['stock'][0],null,'surface-'.$tag.'-loss-close');
    equalMoney('Trading settlement loss is not a legacy investment',(float)$surfaceUser->wallet()->first()->total_investments,$investmentBefore);
    verify('Loss close actually produced a classified debit',$surfaceUser->wallet()->first()->transactions()->where('reference_id','like','PAPER-%')->where('direction','debit')->exists());
    $closedRow=$presenter->describe($surfacePositions['stock'][0]->refresh());
    equalMoney('Closed presenter uses realized P/L',$closedRow['pnl'],-10);
    config(['paper_trading.fee_basis_points'=>10]);
    $beforeFeeSummary=$summaryService->build($surfaceUser);
    $feeInstrument=$surfacePositions['forex'][0]->marketInstrument;
    $feeOpening=$broker->placeMarketOrder($surfaceUser,$feeInstrument,'buy',1,'units','surface-'.$tag.'-fee');
    $feeSummary=$summaryService->build($surfaceUser);
    equalMoney('Entry fee lowers equity once',$feeSummary['equity'],$beforeFeeSummary['equity']-0.09);
    equalMoney('Entry fee is not counted again as open P/L',$feeSummary['unrealized_profit_loss'],$beforeFeeSummary['unrealized_profit_loss']);
    $feePosition=$feeOpening->execution->tradePosition;
    equalMoney('Position return still includes entry fee',$presenter->describe($feePosition)['pnl'],-0.09);
    $broker->placePositionClose($surfaceUser,$feePosition,null,'surface-'.$tag.'-fee-close');
    $feeClosedSummary=$summaryService->build($surfaceUser);
    equalMoney('Exit fee lowers equity once',$feeClosedSummary['equity'],$beforeFeeSummary['equity']-0.18);
    equalMoney('Entry/exit fees are not legacy investments',(float)$surfaceUser->wallet()->first()->total_investments,$investmentBefore);
    config(['paper_trading.fee_basis_points'=>0]);
    $surfaceFeeds['forex']->update(['is_active'=>false]);
    $summary=$summaryService->build($surfaceUser);
    verify('Missing mark does not fabricate equity',$summary['equity']===null && !$summary['valuation_complete']);
    $badRow=$presenter->describe($surfacePositions['forex'][1]);
    verify('Missing mark is unavailable, not zero profit',$badRow['pnl']===null && $badRow['formatted_pnl']==='—');

    $request=\Illuminate\Http\Request::create('/market-runtime','GET',[
        'positions'=>implode(',',array_merge(array_map(fn($p)=>$p->id,$surfacePositions['forex']),
            array_map(fn($p)=>$p->id,$surfacePositions['crypto']),[$position->id])),
    ]);
    $request->setUserResolver(fn()=>$surfaceUser);
    $beforeRuntime=snapshotPaper($surfaceUser);
    $beforeFeed=ControlledMarketInstrument::whereIn('id',array_map(fn($f)=>$f->id,$surfaceFeeds))->get()->toJson();
    $beforeEnvironment=MarketEnvironment::query()->get()->toJson();
    $runtime=$app->make(\App\Http\Controllers\MarketRuntimeController::class)->snapshot(
        $request,$app->make(MarketPriceRouter::class),$app->make(\App\Services\ControlledMarketEngine::class),
        $app->make(\App\Services\StockAnalysisService::class),$app->make(\App\Services\MarketInstrumentAnalysisService::class)
    )->getData(true);
    verify('Runtime polling never advances controlled clock',$runtime['controlled_tick']['ticked']===false);
    verify('Runtime snapshot leaves prices unchanged',ControlledMarketInstrument::whereIn('id',array_map(fn($f)=>$f->id,$surfaceFeeds))->get()->toJson()===$beforeFeed);
    verify('Runtime snapshot leaves ledger unchanged',snapshotPaper($surfaceUser)===$beforeRuntime);
    verify('Runtime snapshot leaves environment unchanged',MarketEnvironment::query()->get()->toJson()===$beforeEnvironment);
    verify('Runtime excludes another customer position',!isset($runtime['positions'][$position->id]));
    verify('Runtime isolates missing forex mark',$runtime['positions'][$surfacePositions['forex'][1]->id]['unavailable']===true);
    equalMoney('Runtime continues valuing crypto short',$runtime['positions'][$surfacePositions['crypto'][1]->id]['pnl'],20);
    $surfaceFeeds['forex']->update(['is_active'=>true]);

    // Preserve the protected showcase account's mutation guard.
    $surfaceUser->update(['is_production_demo'=>true]);
    config(['release.production_demo.read_only'=>true]);
    $post=\Illuminate\Http\Request::create('/broker/stock/test/orders','POST');
    $post->setUserResolver(fn()=>$surfaceUser);
    $denied=false;
    try {
        (new \App\Http\Middleware\ProtectProductionDemoMutations)->handle($post,fn()=>response('allowed'));
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $error) { $denied=$error->getStatusCode()===403; }
    verify('Protected showcase mutations still return 403',$denied);
    $get=\Illuminate\Http\Request::create('/broker/portfolio','GET');
    $get->setUserResolver(fn()=>$surfaceUser);
    verify('Protected showcase can still read',(new \App\Http\Middleware\ProtectProductionDemoMutations)->handle($get,fn()=>response('allowed'))->getStatusCode()===200);
    $surfaceUser->update(['is_production_demo'=>false]);
    verify('Normal customer passes showcase mutation guard',(new \App\Http\Middleware\ProtectProductionDemoMutations)->handle($post,fn()=>response('allowed'))->getStatusCode()===200);

    // The disabled engine must not reinterpret an explicit Short as a holdings sale.
    $guardBot = $bot->fresh();
    $guardBot->update(['paper_intent'=>'open_short']);
    config(['paper_trading.enabled'=>false]);
    $before = snapshotPaper($follower);
    $guardResult = app(\App\Services\TradingBotService::class)->run($guardBot, true);
    verify('Explicit intent cannot fall through to legacy execution', $guardResult->status === 'skipped');
    verify('Disabled explicit intent leaves ledger unchanged', snapshotPaper($follower) === $before);
    config(['paper_trading.enabled'=>true]);

    // Genuine HTTP-kernel acceptance: preserve auth, CSRF, account, KYC and owner middleware.
    require_once __DIR__.'/LocalHttpHarness.php';
    config(['paper_trading.enabled'=>true, 'paper_trading.lifecycle_enabled'=>false,
        'paper_trading.copy_enabled'=>false, 'paper_trading.bots_enabled'=>false,
        'paper_trading.fee_basis_points'=>0, 'release.production_demo.read_only'=>true]);
    $httpKyc = \App\Models\KYC::withoutEvents(fn () => \App\Models\KYC::create([
        'user_id'=>$surfaceUser->id, 'first_name'=>'HTTP', 'last_name'=>'Acceptance',
        'date_of_birth'=>'2000-01-01', 'nationality'=>'Nigerian', 'document_type'=>'passport',
        'document_number'=>'HTTP-SYNTHETIC-'.$tag, 'document_expiry_date'=>'2030-01-01',
        'address_line_1'=>'Synthetic fixture', 'city'=>'Warri', 'state_province'=>'Delta',
        'postal_code'=>'000000', 'country'=>'Nigeria', 'phone_number'=>'00000000000',
        'document_front_path'=>'fixture-not-a-document', 'document_back_path'=>'fixture-not-a-document',
        'selfie_path'=>'fixture-not-a-document', 'status'=>'approved', 'submitted_at'=>now(), 'verified_at'=>now(),
    ]));
    $http = new LocalTradingHttpHarness($app);
    try {
        $httpInstrument = $surfacePositions['forex'][1]->marketInstrument;
        $surfaceFeeds['forex']->update(['current_price'=>100, 'is_active'=>true]);
        $params = ['assetClass'=>'forex', 'symbol'=>$httpInstrument->symbol];
        $submitUri = route('broker.orders.submit', $params, false);
        $pageUri = route('broker.workstation', $params, false);
        $sell = ['side'=>'sell', 'quantity'=>2, 'quantity_mode'=>'units',
            'idempotency_key'=>'http-'.$tag.'-short', 'stop_loss_percent'=>10, 'take_profit_percent'=>20];
        $before = snapshotPaper($surfaceUser);
        $guest = $http->request(null, 'GET', $pageUri);
        $http->status('Unauthenticated workstation access is denied', $guest, 401);
        $noCsrf = $http->request($surfaceUser, 'POST', $submitUri, $sell, false);
        $http->status('Missing CSRF rejects trading', $noCsrf, 419);
        verify('CSRF rejection leaves ledger unchanged', snapshotPaper($surfaceUser) === $before);
        $invalid = $sell; $invalid['quantity'] = 0;
        $response = $http->request($surfaceUser, 'POST', $submitUri, $invalid);
        $http->status('Invalid order is rejected', $response, 422);
        $invalidJson = $http->json('Invalid order', $response);
        verify('Validation names the quantity field', isset($invalidJson['errors']['quantity']));
        verify('Invalid order leaves ledger unchanged', snapshotPaper($surfaceUser) === $before);
        $response = $http->request($surfaceUser, 'POST', $submitUri, $sell);
        $http->status('Sell opens a Short over HTTP', $response, 200);
        $sellJson = $http->json('Sell opening', $response);
        verify('Sell reports success and filled receipt', ($sellJson['success'] ?? null) === true && ($sellJson['status'] ?? null) === 'filled');
        $httpOrder = BrokerOrder::where('user_id', $surfaceUser->id)->where('idempotency_key', $sell['idempotency_key'])->firstOrFail();
        $httpPosition = $httpOrder->execution->tradePosition;
        verify('Sell persists a Short with correct owner and instrument', $httpPosition->direction === 'short'
            && (int)$httpPosition->user_id === (int)$surfaceUser->id && (int)$httpPosition->market_instrument_id === (int)$httpInstrument->id);
        equalMoney('Short opens the requested quantity', (float)$httpPosition->open_quantity, 2);
        verify('Successful response carries a real receipt URL', ($sellJson['receipt_url'] ?? '') === route('broker.orders.show', ['publicId'=>$httpOrder->public_id]));
        $after = snapshotPaper($surfaceUser);
        $response = $http->request($surfaceUser, 'POST', $submitUri, $sell);
        $http->status('Same opening request can be replayed', $response, 200);
        verify('HTTP replay creates no second trade', snapshotPaper($surfaceUser) === $after);
        $changed = $sell; $changed['quantity'] = 3;
        $response = $http->request($surfaceUser, 'POST', $submitUri, $changed);
        $http->status('Changed request cannot reuse the opening key', $response, 422);
        verify('Changed replay leaves ledger unchanged', snapshotPaper($surfaceUser) === $after);
        $response = $http->request($surfaceUser, 'GET', $pageUri, [], true, false);
        $http->status('Instrument page renders after the trade', $response, 200);
        verify('Trading page renders the instrument picker', str_contains($response->getContent(), 'id="instrument-picker"'));
        verify('Picker includes the current instrument route', str_contains($response->getContent(), route('broker.workstation', $params)));
        preg_match('/<select\b[^>]*id="instrument-picker"[^>]*>(.*?)<\/select>/s', $response->getContent(), $pickerMarkup);
        verify('Forex picker excludes stocks and crypto routes', isset($pickerMarkup[1])
            && !str_contains($pickerMarkup[1], '/broker/stock/') && !str_contains($pickerMarkup[1], '/broker/crypto/'));

        // Direction text is ucfirst('short'); CSS makes the badge look uppercase.
        // Check the actual position's card rather than a chart legend elsewhere on the page.
        preg_match_all('/<article\b[^>]*>.*?<\/article>/s', $response->getContent(), $renderedCards);
        $shortCard = null;
        foreach ($renderedCards[0] as $card) {
            if (str_contains($card, 'id="manage-'.$httpPosition->id.'"')) { $shortCard = $card; break; }
        }
        verify('Short appears in the rendered position list', $shortCard !== null
            && preg_match('/>\s*Short\s*<\/span>/i', $shortCard) === 1
            && str_contains($shortCard, 'Position #'.$httpPosition->id));
        $riskUri = route('broker.positions.risk', ['position'=>$httpPosition->id], false);
        $closeUri = route('broker.positions.close', ['position'=>$httpPosition->id], false);
        $risk = ['stop_loss_percent'=>5, 'take_profit_percent'=>10];
        $response = $http->request($follower, 'PATCH', $riskUri, $risk);
        $http->status('Another customer cannot change risk', $response, 403);
        $response = $http->request($follower, 'POST', $closeUri,
            ['quantity'=>1, 'idempotency_key'=>'http-'.$tag.'-foreign']);
        $http->status('Another customer cannot close the position', $response, 403);
        verify('Ownership rejections leave ledger unchanged', snapshotPaper($surfaceUser) === $after);
        $response = $http->request($surfaceUser, 'PATCH', $riskUri, $risk);
        $http->status('Owner can update Short risk', $response, 200);
        $riskJson = $http->json('Risk update', $response);
        verify('Risk update reports success', ($riskJson['success'] ?? null) === true);
        equalMoney('Short stop loss is above entry', (float)$httpPosition->fresh()->stop_loss_price, 105);
        equalMoney('Short take profit is below entry', (float)$httpPosition->fresh()->take_profit_price, 90);
        $surfaceFeeds['forex']->update(['current_price'=>90]);
        $partial = ['quantity'=>1, 'idempotency_key'=>'http-'.$tag.'-partial'];
        $response = $http->request($surfaceUser, 'POST', $closeUri, $partial);
        $http->status('Owner can partially close Short', $response, 200);
        $closeJson = $http->json('Partial close', $response);
        verify('Partial close reports success', ($closeJson['success'] ?? null) === true);
        equalMoney('Partial close leaves one unit', (float)$httpPosition->fresh()->open_quantity, 1);
        $partialState = snapshotPaper($surfaceUser);
        $response = $http->request($surfaceUser, 'POST', $closeUri, $partial);
        $http->status('Partial close replay succeeds', $response, 200);
        verify('Partial close replay does not settle twice', snapshotPaper($surfaceUser) === $partialState);
        $response = $http->request($surfaceUser, 'POST', $closeUri,
            ['idempotency_key'=>'http-'.$tag.'-full']);
        $http->status('Owner can fully close remaining Short', $response, 200);
        verify('Short is persisted as closed', $httpPosition->fresh()->status === 'closed');
        equalMoney('Full close clears remaining quantity', (float)$httpPosition->fresh()->open_quantity, 0);
        equalMoney('HTTP Short realizes the correct profit', (float)$httpPosition->fresh()->realized_profit_loss, 20);
        $response = $http->request($surfaceUser, 'GET', $pageUri, [], true, false);
        $http->status('Instrument page still renders after closing', $response, 200);
        verify('Closed Short leaves the open position list', !str_contains($response->getContent(), 'manage-'.$httpPosition->id));
        // Verify both opening directions through HTTP for every supported asset class.
        foreach (['stock','forex','crypto'] as $httpAsset) {
            $surfaceFeeds[$httpAsset]->update(['current_price'=>100, 'is_active'=>true]);
            $assetInstrument = $surfacePositions[$httpAsset][1]->marketInstrument;
            $assetUri = route('broker.orders.submit', ['assetClass'=>$httpAsset, 'symbol'=>$assetInstrument->symbol], false);
            foreach (['buy'=>'long', 'sell'=>'short'] as $side=>$direction) {
                $assetKey = 'http-'.$tag.'-'.$httpAsset.'-'.$direction;
                $response = $http->request($surfaceUser, 'POST', $assetUri,
                    ['side'=>$side, 'quantity'=>1, 'quantity_mode'=>'units', 'idempotency_key'=>$assetKey]);
                $http->status($httpAsset.' '.$direction.' opening works over HTTP', $response, 200);
                $result = $http->json($httpAsset.' '.$direction.' opening', $response);
                verify($httpAsset.' '.$direction.' opening reports success', ($result['success'] ?? null) === true);
                $assetOrder = BrokerOrder::where('user_id',$surfaceUser->id)->where('idempotency_key',$assetKey)->firstOrFail();
                $assetPosition = $assetOrder->execution->tradePosition;
                verify($httpAsset.' HTTP position has correct direction', $assetPosition->direction === $direction);
                $assetCloseUri = route('broker.positions.close', ['position'=>$assetPosition->id], false);
                $response = $http->request($surfaceUser, 'POST', $assetCloseUri,
                    ['idempotency_key'=>$assetKey.'-close']);
                $http->status($httpAsset.' '.$direction.' close works over HTTP', $response, 200);
                verify($httpAsset.' HTTP position is closed', $assetPosition->fresh()->status === 'closed');
            }
        }
        $closedState = snapshotPaper($surfaceUser);
        $surfaceUser->update(['is_production_demo'=>true]);
        $blocked = $sell; $blocked['idempotency_key'] = 'http-'.$tag.'-showcase';
        $response = $http->request($surfaceUser, 'POST', $submitUri, $blocked);
        $http->status('Showcase account cannot open over HTTP', $response, 403);
        verify('Showcase rejection leaves ledger unchanged', snapshotPaper($surfaceUser) === $closedState);
        $surfaceUser->update(['is_production_demo'=>false]);
        $httpKyc->update(['status'=>'rejected']);
        if (is_kyc_enabled()) {
            $response = $http->request($surfaceUser, 'POST', $submitUri, $blocked);
            $http->status('Rejected KYC cannot open over HTTP', $response, 302);
            verify('KYC rejection leaves ledger unchanged', snapshotPaper($surfaceUser) === $closedState);
        }
        $httpKyc->update(['status'=>'approved']);
    } finally { $http->cleanup(); }

    // Legacy regression: ordinary Sell must reconcile entry-order positions FIFO.
    config(['paper_trading.enabled'=>false]);
    $legacyInstrument = $surfacePositions['forex'][0]->marketInstrument->fresh();
    \App\Models\ForexPair::withoutEvents(fn () => \App\Models\ForexPair::create([
        'market_instrument_id'=>$legacyInstrument->id,'symbol'=>$legacyInstrument->symbol,
        'display_symbol'=>$legacyInstrument->display_symbol,'name'=>'Legacy acceptance Forex',
        'base_currency'=>'EUR','quote_currency'=>'USD','pip_size'=>0.0001,'price_precision'=>4,
        'current_rate'=>100,'previous_close'=>100,'is_active'=>true,'is_featured'=>false,
        'external_feed_enabled'=>false,'last_updated'=>now(),
    ]));
    $legacyInstrument->unsetRelations();
    $surfaceFeeds['forex']->update(['current_price'=>100,'is_active'=>true]);
    $legacyBalance = (float)$surfaceUser->wallet()->first()->balance;
    $legacyOpen = $broker->placeMarketOrder($surfaceUser,$legacyInstrument,'buy',3,'units','legacy-'.$tag.'-entry');
    $legacyPosition = $legacyOpen->execution->tradePosition;
    $broker->placePositionClose($surfaceUser,$legacyPosition->fresh(),1,'legacy-'.$tag.'-partial');
    $legacySell = $broker->placeMarketOrder($surfaceUser,$legacyInstrument,'sell',1,'units','legacy-'.$tag.'-ordinary-sell');
    equalMoney('Ordinary legacy Sell updates the original position',(float)$legacyPosition->fresh()->open_quantity,1);
    equalMoney('Ordinary legacy Sell records reconciled quantity',(float)$legacySell->execution->fresh()->metadata['position_reconciled_quantity'],1);
    $broker->placePositionClose($surfaceUser,$legacyPosition->fresh(),null,'legacy-'.$tag.'-final');
    verify('Mixed partial / ordinary Sell / final close ends closed',$legacyPosition->fresh()->status==='closed');
    verify('Legacy holding cleared after final exit',!\App\Models\MarketHolding::where('user_id',$surfaceUser->id)->where('market_instrument_id',$legacyInstrument->id)->exists());
    equalMoney('Legacy mixed-exit round trip preserves balance',(float)$surfaceUser->wallet()->first()->balance,$legacyBalance);
    config(['paper_trading.enabled'=>true]);

    // Paid-exit repair fixture: settlement already happened; only records are incomplete.
    $repairUser = User::withoutEvents(fn () => User::create([
        'name'=>'Repair Acceptance','email'=>'repair-'.$tag.'@example.invalid','password'=>bin2hex(random_bytes(24)),
        'email_verified_at'=>now(),'is_admin'=>false,'is_production_demo'=>false,'account_status'=>'active',
        'country'=>'Nigeria','currency'=>'USD',
    ]));
    $repairWallet = Wallet::create(['user_id'=>$repairUser->id,'currency'=>'USD','balance'=>10000,'reserved_balance'=>0]);
    $paid = [];
    foreach (['buy','sell','sell','sell'] as $index=>$side) {
        $quantity = $index===0 ? 3 : 1;
        $amount = $quantity*1.15;
        $tx = \App\Models\WalletTransaction::create(['wallet_id'=>$repairWallet->id,'payment_method_id'=>null,
            'type'=>'investment','direction'=>$side==='buy'?'debit':'credit','amount'=>$amount,'fee'=>0,
            'status'=>'completed','reference_id'=>'repair-'.$tag.'-'.$index,'description'=>'Synthetic already-paid fixture']);
        $paid[$index] = MarketExecutionTransaction::create(['user_id'=>$repairUser->id,'market_instrument_id'=>$legacyInstrument->id,
            'wallet_transaction_id'=>$tx->id,'native_type'=>'forex_cash_collateral_execution','side'=>$side,
            'execution_source'=>'broker_order','marketplace'=>'controlled','quantity'=>$quantity,'price'=>1.15,
            'gross_value'=>$amount,'settlement_currency'=>'USD','settlement_amount'=>$amount,'fee'=>0,
            'realized_profit_loss'=>$index===0?null:0,'status'=>'completed','executed_at'=>now()->subMinutes(4-$index),
            'metadata'=>['execution_model'=>'cash_collateralized_long_no_leverage',
                'position_reconciled_quantity'=>0,'collateral_released'=>$index===0?null:1.15]]);
    }
    $repairPosition = TradePosition::create(['user_id'=>$repairUser->id,'market_instrument_id'=>$legacyInstrument->id,
        'entry_market_execution_transaction_id'=>$paid[0]->id,'last_exit_market_execution_transaction_id'=>$paid[3]->id,
        'context_type'=>'broker_order','context_id'=>987654,'marketplace'=>'controlled','direction'=>'long',
        'initial_quantity'=>3,'open_quantity'=>1,'entry_price'=>1.15,'average_exit_price'=>1.15,
        'opened_at'=>$paid[0]->executed_at,'status'=>'open','realized_profit_loss'=>0,'realized_return_percent'=>0,
        'metadata'=>['execution_model'=>'cash_collateralized_long_no_leverage','settlement_currency'=>'USD']]);
    $paid[0]->update(['trade_position_id'=>$repairPosition->id]);
    foreach ([1,3] as $index) {
        $paid[$index]->update(['trade_position_id'=>$repairPosition->id]);
        \App\Models\TradePositionEvent::create(['trade_position_id'=>$repairPosition->id,'market_execution_transaction_id'=>$paid[$index]->id,
            'actor_type'=>'system','event_type'=>'partial_close','quantity'=>1,'price'=>1.15,'profit_loss'=>0]);
    }
    $reconciler = app(\App\Services\LegacyPaidExitReconciler::class);
    $unpaidTx = $paid[2]->walletTransaction;
    $unpaidTx->update(['status'=>'pending']);
    rejected('Repair refuses an exit that was not paid',fn () => $reconciler->reconcile($repairUser,$repairPosition->id,$paid[2]->id));
    verify('Rejected repair preserves position',(float)$repairPosition->fresh()->open_quantity===1.0);
    $unpaidTx->update(['status'=>'completed']);
    $walletBefore = $repairWallet->fresh()->getAttributes();
    $cashBefore = $repairWallet->transactions()->orderBy('id')->get()->toJson();
    $result = $reconciler->reconcile($repairUser,$repairPosition->id,$paid[2]->id);
    verify('Paid exit record repair completes',$result['wallet_unchanged'] && $repairPosition->fresh()->status==='closed');
    verify('Repair does not change wallet',$repairWallet->fresh()->getAttributes()===$walletBefore);
    verify('Repair does not add or alter payments',$repairWallet->transactions()->orderBy('id')->get()->toJson()===$cashBefore);
    verify('Repair retains the latest real settlement timestamp',$repairPosition->fresh()->closed_at->eq($paid[3]->executed_at));
    verify('Repair retains latest exit receipt',(int)$repairPosition->fresh()->last_exit_market_execution_transaction_id===$paid[3]->id);
    $eventCount = $repairPosition->events()->count();
    $repeat = $reconciler->reconcile($repairUser,$repairPosition->id,$paid[2]->id);
    verify('Repair replay is idempotent',$repeat['already_reconciled'] && $repairPosition->events()->count()===$eventCount);
    verify('Repair replay leaves wallet and payments unchanged',$repairWallet->fresh()->getAttributes()===$walletBefore
        && $repairWallet->transactions()->orderBy('id')->get()->toJson()===$cashBefore);

    // Read-only calculator uses execution sizing, conversion and cent rounding.
    $engine = app(PaperBrokerService::class);
    $beforeEstimate = snapshotPaper($surfaceUser);
    config(['paper_trading.fee_basis_points'=>10]);
    $unitEstimate = $engine->estimate($surfaceUser,$legacyInstrument,'buy',3,'units');
    $lotEstimate = $engine->estimate($surfaceUser,$legacyInstrument,'sell',0.00003,'lots');
    equalMoney('Standard lots convert to 100000 base units',(float)$lotEstimate['units'],3);
    equalMoney('Equivalent unit/lot collateral matches',$unitEstimate['collateral'],$lotEstimate['collateral']);
    equalMoney('Estimate includes entry fee',$unitEstimate['required'],$unitEstimate['collateral']+$unitEstimate['entry_fee']);
    equalMoney('Available estimate excludes reserved funds',$unitEstimate['available'],
        (float)$surfaceUser->wallet()->first()->balance-(float)$surfaceUser->wallet()->first()->reserved_balance);
    equalMoney('Remaining estimate subtracts collateral and fee',$unitEstimate['remaining'],$unitEstimate['available']-$unitEstimate['required']);
    verify('Estimate leaves wallet/orders/positions unchanged',snapshotPaper($surfaceUser)===$beforeEstimate);
    $largeEstimate=$engine->estimate($surfaceUser,$legacyInstrument,'buy',100000000,'units');
    verify('Oversized estimate reports insufficient funds without trading',!$largeEstimate['sufficient'] && $largeEstimate['remaining']<0);
    rejected('Estimate rejects invalid quantity',fn()=>$engine->estimate($surfaceUser,$legacyInstrument,'buy',0,'units'));
    rejected('Estimate rejects invalid side',fn()=>$engine->estimate($surfaceUser,$legacyInstrument,'other',1,'units'));
    $entry=$broker->placeMarketOrder($surfaceUser,$legacyInstrument,'buy',3,'units','estimate-'.$tag.'-open');
    $estimatedPosition=$entry->execution->tradePosition;
    equalMoney('Executed collateral matches estimate',(float)$estimatedPosition->metadata['initial_collateral_minor']/100,$unitEstimate['collateral']);
    equalMoney('Executed entry fee matches estimate',(float)$estimatedPosition->metadata['entry_fee_minor']/100,$unitEstimate['entry_fee']);
    $broker->placePositionClose($surfaceUser,$estimatedPosition,null,'estimate-'.$tag.'-close');
    config(['paper_trading.fee_basis_points'=>0]);
    $httpEstimate=new LocalTradingHttpHarness($app);
    try {
        $estimateUrl=route('broker.workstation',['assetClass'=>$legacyInstrument->asset_class,'symbol'=>$legacyInstrument->symbol],false);
        $estimateState=snapshotPaper($surfaceUser);
        $response=$httpEstimate->request($surfaceUser,'GET',$estimateUrl,['entry_estimate'=>'1','side'=>'buy','quantity'=>'3','quantity_mode'=>'units']);
        $httpEstimate->status('Calculator GET returns JSON',$response,200);
        $json=json_decode($response->getContent(),true,512,JSON_THROW_ON_ERROR);
        verify('HTTP calculator reports positive collateral',$json['success'] && $json['estimate']['collateral']>0);
        verify('HTTP calculator is not cached',str_contains((string)$response->headers->get('Cache-Control'),'no-store'));
        verify('HTTP estimate leaves financial state unchanged',snapshotPaper($surfaceUser)===$estimateState);
        $response=$httpEstimate->request($surfaceUser,'GET',$estimateUrl,['entry_estimate'=>'1','side'=>'buy','quantity'=>'0','quantity_mode'=>'units']);
        $httpEstimate->status('Calculator validates quantity',$response,422);
        $response=$httpEstimate->request(null,'GET',$estimateUrl,['entry_estimate'=>'1','side'=>'buy','quantity'=>'3','quantity_mode'=>'units']);
        $httpEstimate->status('Unauthenticated calculator cannot expose funds',$response,401);
    } finally { $httpEstimate->cleanup(); }

    // Exact-price risk controls: validation, entry, update and automatic exit.
    config(['paper_trading.lifecycle_enabled'=>true,'paper_trading.fee_basis_points'=>0]);
    $surfaceFeeds['forex']->update(['current_price'=>100,'is_active'=>true]);
    $priceOpen=$broker->placeMarketOrder($surfaceUser,$legacyInstrument,'buy',3,'units','price-'.$tag.'-long',
        ['stop_loss_price'=>99,'take_profit_price'=>101]);
    $pricePosition=$priceOpen->execution->tradePosition;
    equalMoney('Exact stop saved on Long',(float)$pricePosition->stop_loss_price,99);
    equalMoney('Exact target saved on Long',(float)$pricePosition->take_profit_price,101);
    verify('Exact prices do not invent percentage settings',$pricePosition->stop_loss_percent===null && $pricePosition->take_profit_percent===null);
    $positionService=app(\App\Services\BrokerPositionService::class);
    $positionService->updateRisk($surfaceUser,$pricePosition,null,null,null,98,102);
    equalMoney('Exact target update persists',(float)$pricePosition->fresh()->take_profit_price,102);
    $riskState=snapshotPaper($surfaceUser);
    rejected('Long stop cannot be above entry',fn()=>$positionService->updateRisk($surfaceUser,$pricePosition,null,null,null,101,102));
    rejected('Mixed price and percentage rejected',fn()=>$positionService->updateRisk($surfaceUser,$pricePosition,1,null,null,99,102));
    verify('Rejected risk updates preserve ledger',snapshotPaper($surfaceUser)===$riskState);
    $surfaceFeeds['forex']->update(['current_price'=>102]);
    $engine->closeTriggered($surfaceUser,$pricePosition,'price-'.$tag.'-trigger-long');
    verify('Exact Long target closes at threshold',$pricePosition->fresh()->status==='closed' && $pricePosition->fresh()->exit_reason==='take_profit');
    $surfaceFeeds['forex']->update(['current_price'=>100]);
    $priceShort=$broker->placeMarketOrder($surfaceUser,$legacyInstrument,'sell',3,'units','price-'.$tag.'-short',
        ['stop_loss_price'=>101,'take_profit_price'=>99])->execution->tradePosition;
    $surfaceFeeds['forex']->update(['current_price'=>101]);
    $engine->closeTriggered($surfaceUser,$priceShort,'price-'.$tag.'-trigger-short');
    verify('Exact Short stop closes at threshold',$priceShort->fresh()->status==='closed' && $priceShort->fresh()->exit_reason==='stop_loss');
    rejected('Exact negative price rejected',fn()=>\App\Services\PaperTrading\PositionMath::exactRiskLevels('long',100,-1,101));
    rejected('Short target cannot exceed entry',fn()=>\App\Services\PaperTrading\PositionMath::exactRiskLevels('short',100,101,102));
    config(['paper_trading.lifecycle_enabled'=>false]);
    $surfaceFeeds['forex']->update(['current_price'=>100]);
    $priceHttp=new LocalTradingHttpHarness($app);
    try {
        $priceKey='price-'.$tag.'-http';
        $priceSubmit=route('broker.orders.submit',['assetClass'=>$legacyInstrument->asset_class,'symbol'=>$legacyInstrument->symbol],false);
        $response=$priceHttp->request($surfaceUser,'POST',$priceSubmit,['side'=>'buy','quantity'=>'3','quantity_mode'=>'units',
            'idempotency_key'=>$priceKey,'stop_loss_price'=>'99','take_profit_price'=>'101']);
        $priceHttp->status('HTTP opens with exact prices',$response,200);
        $httpPricePosition=BrokerOrder::where('idempotency_key',$priceKey)->firstOrFail()->execution->tradePosition;
        equalMoney('HTTP target persists',(float)$httpPricePosition->take_profit_price,101);
        $riskUrl=route('broker.positions.risk',$httpPricePosition,false);
        $response=$priceHttp->request($surfaceUser,'PATCH',$riskUrl,['stop_loss_price'=>'98','take_profit_price'=>'102']);
        $priceHttp->status('HTTP updates exact prices',$response,200);
        equalMoney('HTTP updated target persists',(float)$httpPricePosition->fresh()->take_profit_price,102);
        $broker->placePositionClose($surfaceUser,$httpPricePosition,null,'price-'.$tag.'-http-close');
    } finally { $priceHttp->cleanup(); }

    // Compile the edited Blade templates, then syntax-check generated PHP.
    // No view-cache clearing, global cache compilation or rendering writes.
    $blade=$app->make('blade.compiler');
    foreach (['workstation','positions','portfolio','order-show','partials/risk-inputs','partials/instrument-position'] as $template) {
        $compiled=$blade->compileString(file_get_contents($root.'/resources/views/broker/'.$template.'.blade.php'));
        $temporary=tempnam(sys_get_temp_dir(),'trade-view-');
        try {
            if ($temporary===false || file_put_contents($temporary,$compiled)===false) { throw new RuntimeException('Temporary Blade compilation failed'); }
            $process=proc_open([PHP_BINARY,'-l',$temporary],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
            if (!is_resource($process)) { throw new RuntimeException('Compiled Blade syntax checker unavailable'); }
            $lintOut=stream_get_contents($pipes[1]);$lintErr=stream_get_contents($pipes[2]);
            fclose($pipes[1]);fclose($pipes[2]);
            $status=proc_close($process);
            verify('Compiled Blade syntax '.$template,$status===0);
        } finally { if ($temporary!==false && is_file($temporary)) { unlink($temporary); } }
    }

    $connection->rollBack();
    verify('Fixture user rolled back', !User::where('id', $initialRows)->exists());
    echo 'Trading ledger + lifecycle + copy + automation + broker surface + bot intent + HTTP + category picker + legacy reconciliation + entry calculator + exact price risk acceptance: '.$checks.' checks; 0 failures. Fixture changes rolled back.'.PHP_EOL;
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
