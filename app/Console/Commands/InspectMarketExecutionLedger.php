<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InspectMarketExecutionLedger extends Command
{
    protected $signature = 'markets:inspect-execution-ledger';
    protected $description = 'Inspect E2.1 unified MarketInstrument execution ledger compatibility.';

    public function handle(): int
    {
        if (! Schema::hasTable('market_execution_transactions')) {
            $this->error('market_execution_transactions is missing. Run migrations first.');
            return self::FAILURE;
        }

        foreach ([
            ['trade_positions', 'entry_market_execution_transaction_id'],
            ['trade_positions', 'last_exit_market_execution_transaction_id'],
            ['trade_position_events', 'market_execution_transaction_id'],
        ] as [$table, $column]) {
            if (! Schema::hasColumn($table, $column)) {
                $this->error("{$table}.{$column} is missing. Run migrations first.");
                return self::FAILURE;
            }
        }

        $stockTransactions = DB::table('stock_transactions')->count();
        $stockMirrors = DB::table('market_execution_transactions')->where('native_type', 'stock_transaction')->count();

        $missingMirrors = DB::table('stock_transactions as s')
            ->leftJoin('market_execution_transactions as m', function ($join) {
                $join->on('m.native_id', '=', 's.id')->where('m.native_type', '=', 'stock_transaction');
            })
            ->whereNull('m.id')
            ->count();

        $instrumentMismatch = DB::table('stock_transactions as s')
            ->join('market_execution_transactions as m', function ($join) {
                $join->on('m.native_id', '=', 's.id')->where('m.native_type', '=', 'stock_transaction');
            })
            ->whereColumn('s.market_instrument_id', '!=', 'm.market_instrument_id')
            ->count();

        $entryLinkMissing = DB::table('trade_positions')
            ->whereNotNull('entry_transaction_id')
            ->whereNull('entry_market_execution_transaction_id')
            ->count();

        $exitLinkMissing = DB::table('trade_positions')
            ->whereNotNull('last_exit_transaction_id')
            ->whereNull('last_exit_market_execution_transaction_id')
            ->count();

        $eventLinkMissing = DB::table('trade_position_events')
            ->whereNotNull('stock_transaction_id')
            ->whereNull('market_execution_transaction_id')
            ->count();

        $nonStock = DB::table('market_execution_transactions as m')
            ->join('market_instruments as i', 'i.id', '=', 'm.market_instrument_id')
            ->where('i.asset_class', '!=', 'stock')
            ->count();

        $this->table(
            ['Authority', 'Rows / Missing'],
            [
                ['Stock transactions', $stockTransactions],
                ['Unified Stock mirrors', $stockMirrors],
                ['Missing mirrors', $missingMirrors],
                ['Instrument mismatches', $instrumentMismatch],
                ['Position entry links missing', $entryLinkMissing],
                ['Position exit links missing', $exitLinkMissing],
                ['Position event links missing', $eventLinkMissing],
                ['Non-Stock executions', $nonStock],
            ]
        );

        $failures = $missingMirrors + $instrumentMismatch + $entryLinkMissing + $exitLinkMissing + $eventLinkMissing;

        if ($stockMirrors !== $stockTransactions) {
            $failures++;
        }

        if ($nonStock !== 0) {
            $this->warn('Non-Stock execution rows already exist; E2.1 compatibility is valid but the environment has progressed beyond the expected pre-E3 boundary.');
        }

        if ($failures > 0) {
            $this->error('MARKET_EXECUTION_LEDGER_E2_1_FAILED');
            return self::FAILURE;
        }

        $this->info('E2.1 authority rule: market_execution_transactions is the cross-asset execution receipt authority; stock_transactions remains the equity-native compatibility ledger.');
        $this->info('MARKET_EXECUTION_LEDGER_E2_1_OK');
        return self::SUCCESS;
    }
}
