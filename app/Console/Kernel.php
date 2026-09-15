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
        // Laravel 12 schedule definitions for this project live in routes/console.php.
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
