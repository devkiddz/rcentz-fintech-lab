<?php

namespace App\Console\Commands;

use App\Models\ForexPair;
use App\Services\ForexMarketDataService;
use Illuminate\Console\Command;

class RefreshForexHistory extends Command
{
    protected $signature = 'forex:refresh-history {symbol? : Pair symbol such as EURUSD or EUR/USD} {--all : Refresh all active pairs} {--output=compact : Alpha Vantage compact or full output} {--throttle-ms=12500 : Delay between provider requests when refreshing multiple pairs}';
    protected $description = 'Refresh real daily OHLC history for configured forex pairs without fabricating market data.';

    public function handle(ForexMarketDataService $marketData): int
    {
        $pairs = $this->pairs();

        if ($pairs->isEmpty()) {
            $this->error('No matching active forex pairs were found.');
            return self::FAILURE;
        }

        $rows = [];
        $failed = 0;
        $throttleMs = max(0, (int) $this->option('throttle-ms'));

        foreach ($pairs as $index => $pair) {
            try {
                $result = $marketData->refreshDaily($pair, (string) $this->option('output'));
                $rows[] = [
                    $pair->display_symbol,
                    'OK',
                    $result['rows'],
                    $result['latest_date'],
                    number_format($result['latest_close'], (int) $pair->price_precision, '.', ''),
                ];
            } catch (\Throwable $e) {
                $failed++;
                $rows[] = [$pair->display_symbol, 'FAILED', '-', '-', $e->getMessage()];
            }

            if ($index < $pairs->count() - 1 && $throttleMs > 0) {
                usleep($throttleMs * 1000);
            }
        }

        $this->table(['Pair', 'Result', 'Rows', 'Latest date', 'Latest close / Error'], $rows);

        if ($failed > 0) {
            $this->error("FOREX_FX1_REFRESH_PARTIAL_FAILURE={$failed}");
            return self::FAILURE;
        }

        $this->info('FOREX_FX1_REFRESH_OK');
        return self::SUCCESS;
    }

    private function pairs()
    {
        $query = ForexPair::query()->active()->orderBy('id');

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
