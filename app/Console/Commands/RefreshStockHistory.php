<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Services\StockHistoryBackfillService;
use Illuminate\Console\Command;

class RefreshStockHistory extends Command
{
    protected $signature = 'stocks:refresh-history';
    protected $description = 'Refresh stored daily stock history from Alpha Vantage';

    public function handle(StockHistoryBackfillService $backfill): int
    {
        $stocks = Stock::query()
            ->where('is_active', true)
            ->orderBy('symbol')
            ->get();

        foreach ($stocks as $stock) {
            $result = $backfill->refreshLatest($stock);

            if (! $result['ok']) {
                $this->warn("{$stock->symbol}: {$result['reason']}");
            }
        }

        return self::SUCCESS;
    }
}
