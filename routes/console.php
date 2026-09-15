<?php

use App\Jobs\CleanupOldDataJob;
use App\Jobs\FetchStockHistoryJob;
use App\Jobs\ProcessStockNewsJob;
use App\Jobs\UpdateStockQuotesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new UpdateStockQuotesJob())
    ->everyFiveMinutes()
    ->between('09:30', '16:00')
    ->weekdays()
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
