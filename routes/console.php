<?php

use App\Services\StockTradePlanService;
use App\Services\Legacy\LegacyStockExecutionEngine;
use App\Services\MarketSessionService;
use App\Jobs\CleanupOldDataJob;
use App\Jobs\FetchStockHistoryJob;
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
Schedule::call(fn () => app(ControlledMarketEngine::class)->tickAll())
    ->name('controlled-market:tick')
    ->everyMinute()
    ->when(fn () => app(MarketPriceRouter::class)->activeMarketplace() === 'controlled')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::job(new FetchStockHistoryJob())
    ->hourly()
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


Schedule::call(fn () => app(CopyRelationshipLifecycleService::class)->expireDue())
    ->name('copy-relationships:expire-due')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

// Position Engine: stop-loss, take-profit, time expiry and queued exits.
Schedule::command('trade-positions:process')->everyMinute()->withoutOverlapping();
