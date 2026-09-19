<?php

namespace App\Console\Commands;

use App\Models\MarketInstrument;
use App\Services\ForexExecutionQuoteService;
use App\Services\MarketExecutionRouter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InspectForexExecutionAuthority extends Command
{
    protected $signature = 'markets:inspect-forex-execution';
    protected $description = 'Inspect E3 Forex execution, holding and position authority without moving capital.';

    public function handle(MarketExecutionRouter $router, ForexExecutionQuoteService $quotes): int
    {
        foreach (['market_holdings', 'market_execution_transactions', 'trade_positions'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->error("{$table} is missing. Run migrations first.");
                return self::FAILURE;
            }
        }

        foreach (['idempotency_key', 'settlement_currency', 'settlement_amount'] as $column) {
            if (! Schema::hasColumn('market_execution_transactions', $column)) {
                $this->error("market_execution_transactions.{$column} is missing. Run migrations first.");
                return self::FAILURE;
            }
        }

        $instruments = MarketInstrument::query()
            ->with('canonicalForexPair')
            ->where('asset_class', 'forex')
            ->orderBy('symbol')
            ->get();

        $rows = [];
        $failures = 0;
        foreach ($instruments as $instrument) {
            $pair = $instrument->canonicalForexPair;
            $ready = $router->canExecute($instrument);
            $expected = (bool) $instrument->is_active && $pair !== null && (bool) $pair?->is_active;
            if ($ready !== $expected) {
                $failures++;
            }

            $rows[] = [
                $instrument->display_symbol ?: $instrument->symbol,
                $instrument->is_active ? 'ACTIVE' : 'INACTIVE',
                $pair?->is_active ? 'ACTIVE' : ($pair ? 'INACTIVE' : 'MISSING'),
                $ready ? 'READY' : 'BLOCKED',
                $instrument->base_asset,
                $instrument->quote_asset,
            ];
        }

        $holdingOrphans = DB::table('market_holdings as h')
            ->leftJoin('market_instruments as i', 'i.id', '=', 'h.market_instrument_id')
            ->whereNull('i.id')
            ->count();

        $forexExecutions = DB::table('market_execution_transactions as e')
            ->join('market_instruments as i', 'i.id', '=', 'e.market_instrument_id')
            ->where('i.asset_class', 'forex')
            ->count();

        $forexPositions = DB::table('trade_positions as p')
            ->join('market_instruments as i', 'i.id', '=', 'p.market_instrument_id')
            ->where('i.asset_class', 'forex')
            ->count();

        $this->table(['Instrument', 'Parent', 'Pair', 'Execution', 'Base', 'Quote'], $rows);
        $this->table(['Authority', 'Value'], [
            ['Forex instruments', $instruments->count()],
            ['Execution quote provider configured', $quotes->providerAvailable() ? 'YES' : 'NO'],
            ['Forex execution receipts', $forexExecutions],
            ['Forex positions', $forexPositions],
            ['Holding parent orphans', $holdingOrphans],
        ]);

        if (! $quotes->providerAvailable()) {
            $this->warn('Live Forex execution remains fail-closed until the execution quote provider is configured. Controlled execution can still use Controlled Market authority.');
        }

        if ($holdingOrphans > 0 || $failures > 0) {
            $this->error('FOREX_EXECUTION_E3_FAILED');
            return self::FAILURE;
        }

        $this->info('E3 authority rule: Forex uses base-currency units/lots, execution-grade quotes, 1:1 cash collateral, MarketHolding exposure and unified MarketExecutionTransaction receipts.');
        $this->info('Live execution fails closed on stale quotes, stale cross-currency settlement rates or closed 24/5 sessions. Short selling and leverage remain disabled.');
        $this->info('FOREX_EXECUTION_E3_OK');
        return self::SUCCESS;
    }
}
