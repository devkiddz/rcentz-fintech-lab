<?php

namespace App\Console\Commands;

use App\Services\TradePositionService;
use Illuminate\Console\Command;

class ProcessTradePositions extends Command
{
    protected $signature='trade-positions:process';
    protected $description='Process stop-loss, take-profit, time-expiry and queued exits for open positions';

    public function handle(TradePositionService $positions): int
    {
        $stats=$positions->processOpenPositions();
        $this->info(json_encode($stats));
        return self::SUCCESS;
    }
}
