<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InspectMarketExecutionIdentity extends Command
{
    protected $signature = 'markets:inspect-execution-identity';
    protected $description = 'Inspect E2 MarketInstrument parent identity across holdings, transactions and positions.';

    public function handle(): int
    {
        foreach (['stock_holdings', 'stock_transactions', 'trade_positions'] as $table) {
            if (! Schema::hasColumn($table, 'market_instrument_id')) {
                $this->error("{$table}.market_instrument_id is missing. Run migrations first.");
                return self::FAILURE;
            }
        }

        $rows = [];
        $failures = 0;

        foreach ([
            'stock_holdings' => 'Holdings',
            'stock_transactions' => 'Transactions',
            'trade_positions' => 'Positions',
        ] as $table => $label) {
            $total = DB::table($table)->count();
            $missing = DB::table($table)->whereNull('market_instrument_id')->count();
            $mismatch = DB::table($table.' as legacy')
                ->join('stocks as s', 's.id', '=', 'legacy.stock_id')
                ->whereNotNull('legacy.stock_id')
                ->whereColumn('legacy.market_instrument_id', '!=', 's.market_instrument_id')
                ->count();

            $rows[] = [$label, $total, $missing, $mismatch];
            $failures += $missing + $mismatch;
        }

        $this->table(['Authority', 'Rows', 'Missing parent', 'Stock mismatch'], $rows);

        $nullable = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', 'trade_positions')
            ->where('COLUMN_NAME', 'stock_id')
            ->value('IS_NULLABLE');

        $positionAssets = DB::table('trade_positions as p')
            ->join('market_instruments as mi', 'mi.id', '=', 'p.market_instrument_id')
            ->select('mi.asset_class', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('mi.asset_class')
            ->orderBy('mi.asset_class')
            ->get();

        $this->line('TradePosition stock_id nullable: '.($nullable === 'YES' ? 'YES' : 'NO'));

        if ($positionAssets->isEmpty()) {
            $this->line('Position assets: no persisted positions yet.');
        } else {
            $this->table(
                ['Position asset', 'Rows'],
                $positionAssets->map(fn ($row) => [strtoupper((string) $row->asset_class), $row->aggregate])->all()
            );
        }

        if ($nullable !== 'YES') {
            $failures++;
        }

        if ($failures > 0) {
            $this->error('MARKET_EXECUTION_IDENTITY_E2_FAILED');
            return self::FAILURE;
        }

        $this->info('E2 authority rule: MarketInstrument is canonical execution identity; stock_id remains a nullable compatibility child on TradePosition.');
        $this->info('MARKET_EXECUTION_IDENTITY_E2_OK');
        return self::SUCCESS;
    }
}
