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
    'copy_trader_profiles', 'copy_strategies', 'copy_relationships', 'copy_trade_executions', 'kycs'];
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

    $connection->rollBack();
    verify('Fixture user rolled back', !User::where('id', $initialRows)->exists());
    echo 'Paper ledger + lifecycle + copy acceptance: '.$checks.' checks; 0 failures. Fixture changes rolled back.'.PHP_EOL;
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
