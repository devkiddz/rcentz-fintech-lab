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
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new UpdateStockQuotesJob())->everyFiveMinutes()->between('09:30', '16:00')->weekdays()->withoutOverlapping()->onOneServer();
        $schedule->job(new FetchStockHistoryJob())->hourly()->withoutOverlapping()->onOneServer();
        $schedule->job(new ProcessStockNewsJob())->daily()->at('06:00')->withoutOverlapping()->onOneServer();
        $schedule->job(new CleanupOldDataJob())->weekly()->sundays()->at('02:00')->withoutOverlapping()->onOneServer();
        $schedule->command('queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();

        // Trading intelligence automation. The command itself only runs active,
        // due bots and re-checks every risk limit before execution.
        $schedule->command('trading-bots:run')->everyFiveMinutes()->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
