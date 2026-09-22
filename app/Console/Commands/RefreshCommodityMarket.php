<?php

namespace App\Console\Commands;

use App\Models\CommodityInstrument;
use App\Services\CommodityMarketDataService;
use Illuminate\Console\Command;

class RefreshCommodityMarket extends Command
{
    protected $signature = 'commodities:refresh-market {symbol? : Commodity symbol such as XAUUSD or XAU/USD} {--all : Refresh all active commodity instruments}';
    protected $description = 'Refresh real commodity market price history and current reference prices without fabricating data.';

    public function handle(CommodityMarketDataService $marketData): int
    {
        $items = $this->items();
        if ($items->isEmpty()) {
            $this->error('No matching active commodity instruments were found.');
            return self::FAILURE;
        }

        $rows = [];
        $failed = 0;

        foreach ($items as $index => $commodity) {
            try {
                $result = $marketData->refresh($commodity);
                $rows[] = [
                    $commodity->display_symbol,
                    'OK',
                    $result['rows'],
                    $result['latest_date'],
                    number_format($result['latest_price'], (int) $commodity->price_precision, '.', ''),
                    $result['spot_used'] ? 'SPOT + HISTORY' : 'HISTORY',
                ];
            } catch (\Throwable $e) {
                $failed++;
                $rows[] = [$commodity->display_symbol, 'FAILED', '-', '-', $e->getMessage(), 'UNCHANGED'];
            }

            if ($index < $items->count() - 1) {
                usleep(850000);
            }
        }

        $this->table(['Commodity', 'Result', 'Rows', 'Latest date', 'Latest price / Error', 'Feed'], $rows);

        if ($failed > 0) {
            $this->error("COMMODITY_REFRESH_PARTIAL_FAILURE={$failed}");
            return self::FAILURE;
        }

        $this->info('COMMODITY_REFRESH_OK');
        return self::SUCCESS;
    }

    private function items()
    {
        $query = CommodityInstrument::query()->active()->orderBy('id');

        if ($symbol = $this->argument('symbol')) {
            $normalized = strtoupper(str_replace(['/', '-', '_', ' '], '', (string) $symbol));
            return $query->where('symbol', $normalized)->get();
        }

        if ($this->option('all')) {
            return $query->get();
        }

        return $query->featured()->get();
    }
}
