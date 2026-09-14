<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\UpdateStockQuotesJob;
use App\Jobs\FetchStockHistoryJob;
use App\Jobs\ProcessStockNewsJob;
use App\Jobs\CleanupOldDataJob;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Update stock quotes every 5 minutes during market hours
        $schedule->job(new UpdateStockQuotesJob())
            ->everyFiveMinutes()
            ->between('09:30', '16:00')
            ->weekdays()
            ->withoutOverlapping()
            ->onOneServer();

        // Fetch historical data hourly
        $schedule->job(new FetchStockHistoryJob())
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer();

        // Process stock news daily
        $schedule->job(new ProcessStockNewsJob())
            ->daily()
            ->at('06:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Cleanup old data weekly
        $schedule->job(new CleanupOldDataJob())
            ->weekly()
            ->sundays()
            ->at('02:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Monitor queue health
        $schedule->command('queue:work --stop-when-empty')
            ->everyMinute()
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
} 