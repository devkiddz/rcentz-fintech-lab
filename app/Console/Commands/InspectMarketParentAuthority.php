<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InspectMarketParentAuthority extends Command
{
    protected $signature = 'markets:inspect-parent-authority';
    protected $description = 'Verify MarketInstrument parent IDs are linked consistently to existing child/runtime rows.';

    public function handle(): int
    {
        foreach (['stocks', 'forex_pairs', 'controlled_market_instruments'] as $table) {
            if (! Schema::hasColumn($table, 'market_instrument_id')) {
                $this->error($table.'.market_instrument_id is missing. Run migrations first.');
                return self::FAILURE;
            }
        }

        $stats = [
            ['Market instruments', DB::table('market_instruments')->count()],
            ['Stock parents', DB::table('market_instruments')->where('asset_class', 'stock')->count()],
            ['Forex parents', DB::table('market_instruments')->where('asset_class', 'forex')->count()],
            ['Stocks', DB::table('stocks')->count()],
            ['Forex pairs', DB::table('forex_pairs')->count()],
            ['Controlled instruments', DB::table('controlled_market_instruments')->count()],
        ];

        $this->table(['Scope', 'Count'], $stats);

        $missingStocks = DB::table('stocks')->whereNull('market_instrument_id')->count();
        $missingForex = DB::table('forex_pairs')->whereNull('market_instrument_id')->count();
        $missingControlled = DB::table('controlled_market_instruments')->whereNull('market_instrument_id')->count();

        $stockMismatch = DB::table('stocks as s')
            ->join('market_instruments as mi', 'mi.stock_id', '=', 's.id')
            ->whereColumn('s.market_instrument_id', '!=', 'mi.id')
            ->count();

        $forexMismatch = DB::table('forex_pairs as fp')
            ->join('market_instruments as mi', 'mi.forex_pair_id', '=', 'fp.id')
            ->whereColumn('fp.market_instrument_id', '!=', 'mi.id')
            ->count();

        $controlledMismatch = DB::table('controlled_market_instruments as cmi')
            ->join('market_instruments as mi', 'mi.stock_id', '=', 'cmi.stock_id')
            ->whereColumn('cmi.market_instrument_id', '!=', 'mi.id')
            ->count();

        $this->table(['Check', 'Count'], [
            ['Stocks missing parent ID', $missingStocks],
            ['Forex pairs missing parent ID', $missingForex],
            ['Controlled rows missing parent ID', $missingControlled],
            ['Stock parent mismatches', $stockMismatch],
            ['Forex parent mismatches', $forexMismatch],
            ['Controlled parent mismatches', $controlledMismatch],
        ]);

        $failed = $missingStocks + $missingForex + $missingControlled
            + $stockMismatch + $forexMismatch + $controlledMismatch;

        if ($failed > 0) {
            $this->error('MARKET_PARENT_AUTHORITY_M1_FAILED');
            return self::FAILURE;
        }

        $this->info('MARKET_PARENT_AUTHORITY_M1_OK');
        return self::SUCCESS;
    }
}
