<?php

namespace App\Console\Commands;

use App\Models\CryptoPair;
use App\Services\CryptoMarketDataService;
use Illuminate\Console\Command;

class RefreshCryptoHistory extends Command
{
    protected $signature = 'crypto:refresh-history {symbol? : Pair symbol such as BTCUSD or BTC/USD} {--all : Refresh all active crypto pairs}';
    protected $description = 'Refresh real daily OHLCV history for configured crypto pairs without fabricating market data.';

    public function handle(CryptoMarketDataService $marketData): int
    {
        $pairs = $this->pairs();

        if ($pairs->isEmpty()) {
            $this->error('No matching active crypto pairs were found.');
            return self::FAILURE;
        }

        $rows = [];
        $failed = 0;

        foreach ($pairs as $index => $pair) {
            try {
                $result = $marketData->refreshDaily($pair);
                $rows[] = [
                    $pair->display_symbol,
                    'OK',
                    $result['rows'],
                    $result['latest_date'],
                    number_format($result['latest_close'], (int) $pair->price_precision, '.', ''),
                    'INACTIVE UNTIL C2',
                ];
            } catch (\Throwable $e) {
                $failed++;
                $rows[] = [$pair->display_symbol, 'FAILED', '-', '-', $e->getMessage(), 'UNCHANGED'];
            }

            if ($index < $pairs->count() - 1) {
                usleep(850000);
            }
        }

        $this->table(['Pair', 'Result', 'Rows', 'Latest date', 'Latest close / Error', 'Parent runtime'], $rows);

        if ($failed > 0) {
            $this->error("CRYPTO_C1_REFRESH_PARTIAL_FAILURE={$failed}");
            return self::FAILURE;
        }

        $this->info('CRYPTO_C1_REFRESH_OK');
        return self::SUCCESS;
    }

    private function pairs()
    {
        $query = CryptoPair::query()->active()->orderBy('id');

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
