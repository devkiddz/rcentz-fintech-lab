<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Services\MarketPriceRouter;
use App\Services\SignalMarketContextService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InspectSignalFoundation extends Command
{
    protected $signature = 'signals:inspect-foundation {--marketplace= : live or controlled; defaults to the active marketplace}';
    protected $description = 'Verify the Signals S1 database foundation and market-context wiring without mutating signal state.';

    public function handle(
        SignalMarketContextService $contexts,
        MarketPriceRouter $marketPriceRouter
    ): int {
        $tables = [
            'signals',
            'signal_targets',
            'signal_analysis_runs',
            'signal_revisions',
            'signal_events',
            'signal_distributions',
            'signal_deliveries',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $this->error("Missing Signals S1 table: {$table}");
                return self::FAILURE;
            }
        }

        $this->table(
            ['Table', 'Rows'],
            collect($tables)
                ->map(fn (string $table) => [$table, DB::table($table)->count()])
                ->all()
        );

        $stock = Stock::query()->active()->orderBy('id')->first();

        if (! $stock) {
            $this->error('No active Stock instrument exists for Signals market wiring inspection.');
            return self::FAILURE;
        }

        try {
            $marketplace = $this->option('marketplace')
                ? $marketPriceRouter->normalizeMarketplace((string) $this->option('marketplace'))
                : $marketPriceRouter->activeMarketplace();

            $context = $contexts->forStock($stock, $marketplace);
        } catch (\Throwable $e) {
            $this->error('Signals market-context inspection failed: '.$e->getMessage());
            return self::FAILURE;
        }

        $this->table(
            ['Context', 'Value'],
            [
                ['Instrument', $context['symbol']],
                ['Marketplace', $context['marketplace']],
                ['Price', number_format((float) $context['current_price'], 8, '.', '')],
                ['Analysis source', $context['analysis_source']],
                ['Trend', $context['trend']],
                ['Momentum', number_format((float) $context['momentum_percent'], 4, '.', '').'%'],
                ['Support', $context['support'] ?? 'n/a'],
                ['Resistance', $context['resistance'] ?? 'n/a'],
                ['Risk / reward context', $context['risk_reward']],
                ['Session', $context['market_session']],
            ]
        );

        $this->info('SIGNALS_S1_FOUNDATION_OK');
        $this->line('No signal, trade, wallet or membership mutation was performed.');

        return self::SUCCESS;
    }
}
