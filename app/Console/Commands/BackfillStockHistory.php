<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Services\StockHistoryBackfillService;
use Illuminate\Console\Command;

class BackfillStockHistory extends Command
{
    protected $signature = 'stocks:backfill-history
        {symbol? : Optional stock symbol}
        {--all : Backfill every active stock}
        {--no-replace : Keep existing 1D rows and upsert provider history}';

    protected $description = 'Backfill real daily stock OHLCV history from Alpha Vantage';

    public function handle(StockHistoryBackfillService $backfill): int
    {
        $replace = ! $this->option('no-replace');

        if ($this->option('all')) {
            $stocks = Stock::query()
                ->where('is_active', true)
                ->orderBy('symbol')
                ->get();
        } elseif ($this->argument('symbol')) {
            $stocks = Stock::query()
                ->where('symbol', strtoupper($this->argument('symbol')))
                ->get();
        } else {
            $this->error('Provide a symbol or use --all.');
            return self::FAILURE;
        }

        if ($stocks->isEmpty()) {
            $this->error('No matching stock found.');
            return self::FAILURE;
        }

        $failures = 0;

        foreach ($stocks as $stock) {
            $this->line("Backfilling {$stock->symbol}...");

            $result = $backfill->backfill($stock, $replace);

            if ($result['ok']) {
                $this->info("{$stock->symbol}: imported {$result['imported']} daily candles.");
            } else {
                $failures++;
                $this->warn("{$stock->symbol}: {$result['reason']}");

                if (($result['reason'] ?? null) === 'continuity_guard_failed') {
                    $this->line(
                        "  Current {$result['current_price']} vs latest history {$result['latest_history_close']} ".
                        "({$result['deviation_percent']}% deviation)"
                    );
                }
            }
        }

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
