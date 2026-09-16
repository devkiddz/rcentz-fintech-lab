<?php

namespace App\Console\Commands;

use App\Models\Stock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InspectMarketIntegrity extends Command
{
    protected $signature = 'market:integrity';

    protected $description = 'Inspect current marketplace/source-of-truth invariants';

    public function handle(): int
    {
        $this->info('Rcentz market source-of-truth integrity');

        $requiredTables = [
            'stocks',
            'stock_holdings',
            'stock_transactions',
            'trade_positions',
            'controlled_market_instruments',
        ];

        foreach ($requiredTables as $table) {
            if (! Schema::hasTable($table)) {
                $this->error('Missing required table: '.$table);
                return self::FAILURE;
            }
        }

        if (! Schema::hasColumn('stocks', 'external_feed_enabled')) {
            $this->error('Missing stocks.external_feed_enabled. V5.21 migration has not been applied.');
            return self::FAILURE;
        }

        $checks = [];

        $checks['Internal-only stocks with LIVE holdings'] = DB::table('stock_holdings as h')
            ->join('stocks as s', 's.id', '=', 'h.stock_id')
            ->where('h.marketplace', 'live')
            ->where('h.quantity', '>', 0)
            ->where('s.external_feed_enabled', false)
            ->count();

        $checks['Internal-only stocks with LIVE open positions'] = DB::table('trade_positions as p')
            ->join('stocks as s', 's.id', '=', 'p.stock_id')
            ->where('p.marketplace', 'live')
            ->whereIn('p.status', ['open', 'exit_queued'])
            ->where('p.open_quantity', '>', 0)
            ->where('s.external_feed_enabled', false)
            ->count();

        $checks['CONTROLLED holdings without instrument'] = DB::table('stock_holdings as h')
            ->leftJoin('controlled_market_instruments as i', 'i.stock_id', '=', 'h.stock_id')
            ->where('h.marketplace', 'controlled')
            ->where('h.quantity', '>', 0)
            ->whereNull('i.id')
            ->count();

        $checks['CONTROLLED open positions without instrument'] = DB::table('trade_positions as p')
            ->leftJoin('controlled_market_instruments as i', 'i.stock_id', '=', 'p.stock_id')
            ->where('p.marketplace', 'controlled')
            ->whereIn('p.status', ['open', 'exit_queued'])
            ->where('p.open_quantity', '>', 0)
            ->whereNull('i.id')
            ->count();

        $checks['Holdings with invalid marketplace'] = DB::table('stock_holdings')
            ->whereNotIn('marketplace', ['live', 'controlled'])
            ->count();

        $checks['Positions with invalid marketplace'] = DB::table('trade_positions')
            ->whereNotIn('marketplace', ['live', 'controlled'])
            ->count();

        $checks['Transactions with invalid marketplace'] = DB::table('stock_transactions')
            ->whereNotIn('marketplace', ['live', 'controlled'])
            ->count();

        $rows = [];
        $failed = 0;

        foreach ($checks as $label => $count) {
            $count = (int) $count;
            if ($count > 0) {
                $failed++;
            }

            $rows[] = [
                $label,
                $count,
                $count === 0 ? 'PASS' : 'VIOLATION',
            ];
        }

        $this->table(['Invariant', 'Rows', 'Status'], $rows);

        $summary = [
            ['External-feed eligible stocks', Stock::query()->where('external_feed_enabled', true)->count()],
            ['Internal-only stocks', Stock::query()->where('external_feed_enabled', false)->count()],
            ['Controlled instruments', DB::table('controlled_market_instruments')->count()],
        ];

        $this->table(['Source inventory', 'Count'], $summary);

        if ($failed > 0) {
            $this->error($failed.' market integrity invariant(s) failed.');
            $this->line('Do not repair rows blindly. Inspect the affected exposure and preserve its original financial history.');
            return self::FAILURE;
        }

        $this->info('All current marketplace source-of-truth invariants PASS.');
        return self::SUCCESS;
    }
}
