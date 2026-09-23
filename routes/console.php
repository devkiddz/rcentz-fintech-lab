<?php

use App\Services\StockTradePlanService;
use App\Services\Legacy\LegacyStockExecutionEngine;
use App\Services\MarketSessionService;
use App\Jobs\CleanupOldDataJob;
use App\Jobs\ProcessStockNewsJob;
use App\Jobs\UpdateStockQuotesJob;
use App\Services\BotSubscriptionLifecycleService;
use App\Services\CopyRelationshipLifecycleService;
use App\Services\ControlledMarketEngine;
use App\Services\MarketPriceRouter;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new UpdateStockQuotesJob())
    ->everyFiveMinutes()
    ->timezone(MarketSessionService::TIMEZONE)
    ->when(fn () => app(MarketSessionService::class)->isOpen())
    ->withoutOverlapping()
    ->onOneServer();

// Controlled Market has its own price clock. It runs only while that marketplace
// is active, so no simulated tick can overwrite the persisted Live feed.
Schedule::call(fn () => app(ControlledMarketEngine::class)->tickIfDue())
    ->name('controlled-market:tick')
    ->everyFiveSeconds()
    ->when(fn () => app(MarketPriceRouter::class)->activeMarketplace() === 'controlled')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::job(new ProcessStockNewsJob())
    ->daily()
    ->at('06:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::job(new CleanupOldDataJob())
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('queue:work --stop-when-empty')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('trading-bots:run')
    ->everyFiveMinutes()
    ->withoutOverlapping();


Schedule::call(fn () => app(BotSubscriptionLifecycleService::class)->expireDue())
    ->name('bot-subscriptions:expire-due')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();


Schedule::call(function () {
    app(StockTradePlanService::class)->processDuePlans(
        app(LegacyStockExecutionEngine::class),
        app(MarketSessionService::class)
    );
})->name('stock-trade-plans:process-due')->everyMinute()->withoutOverlapping()->onOneServer();


/*
|--------------------------------------------------------------------------
| Historical stock OHLCV refresh
|--------------------------------------------------------------------------
| Alpha Vantage daily history is persisted locally and consumed by charts.
| Live intraday quotes remain handled by Finnhub.
*/
Schedule::command('stocks:refresh-history')
    ->dailyAt('22:30')
    ->timezone('America/New_York')
    ->withoutOverlapping()
    ->onOneServer();

// Forex daily OHLC is Signal-analysis authority, not execution-grade live pricing.
// Refresh all active pairs once per weekday and pace requests conservatively for Alpha Vantage.
Schedule::command('forex:refresh-history --all --output=compact --throttle-ms=12500')
    ->weekdays()
    ->dailyAt('01:15')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->onOneServer();


Schedule::call(fn () => app(CopyRelationshipLifecycleService::class)->expireDue())
    ->name('copy-relationships:expire-due')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

// Position Engine: stop-loss, take-profit, time expiry and queued exits.
Schedule::command('trade-positions:process')->everyMinute()->withoutOverlapping();

// Execution-grade cross-currency settlement rates for Live Forex fills.
Schedule::command('currency:update-rates')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

// Non-Stock position lifecycle. E3 starts with Forex; future adapters plug in here.
Schedule::command('market-positions:process')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();


/*
|--------------------------------------------------------------------------
| Commodity history + reserve-backed NAV synchronization
|--------------------------------------------------------------------------
| Public commodity history is refreshed first. Private market-linked reserve
| products then revalue from that persisted authority and rebuild eligible NAV
| history. These schedules do nothing while the scheduler process is stopped.
*/
Schedule::command('commodities:refresh-market --all')
    ->dailyAt('00:30')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('private-investments:sync-market-reserves --history')
    ->dailyAt('00:50')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->onOneServer();

/*
|--------------------------------------------------------------------------
| Signals S5 autonomous runtime
|--------------------------------------------------------------------------
| Lifecycle monitoring runs independently every minute. The autonomy runner
| performs only due re-analysis and due scanning; generated Signals remain
| READY until an administrator explicitly publishes them.
*/
Schedule::command('signals:process-lifecycle')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('signals:run-autonomy --limit=25')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();
