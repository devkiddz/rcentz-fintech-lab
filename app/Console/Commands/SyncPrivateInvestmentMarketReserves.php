<?php

namespace App\Console\Commands;

use App\Models\PrivateInvestmentAsset;
use App\Models\PrivateInvestmentInstrument;
use App\Services\PrivateInvestmentReserveService;
use Illuminate\Console\Command;

class SyncPrivateInvestmentMarketReserves extends Command
{
    protected $signature = 'private-investments:sync-market-reserves
        {symbol? : Optional private investment symbol}
        {--history : Rebuild eligible private NAV history from stored public-market history}';

    protected $description =
        'Synchronize market-linked private reserves to persisted public-market authority and optionally rebuild eligible NAV history.';

    public function handle(PrivateInvestmentReserveService $reserves): int
    {
        $query = PrivateInvestmentInstrument::query()
            ->whereHas('assets', function ($query) {
                $query->where('status', 'active')
                    ->where('is_reserve_backing', true)
                    ->where(
                        'valuation_mode',
                        PrivateInvestmentAsset::VALUATION_MARKET_LINKED
                    )
                    ->whereNotNull('market_instrument_id');
            })
            ->orderBy('id');

        if ($symbol = $this->argument('symbol')) {
            $query->where('symbol', strtoupper(trim((string) $symbol)));
        }

        $instruments = $query->get();
        if ($instruments->isEmpty()) {
            $this->warn('No matching market-linked private investment reserves were found.');
            return self::SUCCESS;
        }

        $rows = [];
        $failed = 0;
        $historyEnabled = (bool) $this->option('history');

        foreach ($instruments as $instrument) {
            try {
                $beforePrice = (float) $instrument->current_price;
                $synced = $reserves->syncInstrument($instrument, false);
                $historyRows = $historyEnabled
                    ? $reserves->rebuildSingleMarketLinkedHistory($synced)
                    : 0;
                $summary = $reserves->summary($synced->fresh());

                $rows[] = [
                    $synced->symbol,
                    'OK',
                    number_format($beforePrice, 6, '.', ''),
                    number_format((float) $synced->fresh()->current_price, 6, '.', ''),
                    number_format((float) $summary['reserve_value'], 2, '.', ''),
                    $historyEnabled ? (string) $historyRows : 'SKIPPED',
                ];
            } catch (\Throwable $e) {
                $failed++;
                $rows[] = [
                    $instrument->symbol,
                    'FAILED',
                    number_format((float) $instrument->current_price, 6, '.', ''),
                    '-',
                    '-',
                    $e->getMessage(),
                ];
            }
        }

        $this->table(
            ['Investment', 'Result', 'Previous price', 'Current price', 'Reserve value', 'History rows'],
            $rows
        );

        if ($failed > 0) {
            $this->error("PRIVATE_INVESTMENT_MARKET_RESERVE_SYNC_FAILURES={$failed}");
            return self::FAILURE;
        }

        $this->info('PRIVATE_INVESTMENT_MARKET_RESERVE_SYNC_OK');
        return self::SUCCESS;
    }
}
