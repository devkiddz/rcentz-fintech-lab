<?php

namespace App\Console\Commands;

use App\Services\MarketPositionService;
use Illuminate\Console\Command;

class ProcessMarketPositions extends Command
{
    protected $signature = 'market-positions:process';
    protected $description = 'Process Forex and Crypto position lifecycle using asset-specific execution authority.';

    public function handle(MarketPositionService $positions): int
    {
        $stats = $positions->processOpenNonStockPositions();
        $this->info(json_encode($stats));
        return self::SUCCESS;
    }
}
