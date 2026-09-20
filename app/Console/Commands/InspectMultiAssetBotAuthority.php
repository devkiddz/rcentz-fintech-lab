<?php

namespace App\Console\Commands;

use App\Models\MarketInstrument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InspectMultiAssetBotAuthority extends Command
{
    protected $signature = 'bots:inspect-multi-asset-authority';
    protected $description = 'Inspect E7 multi-asset bot identity, BrokerOrder routing, and legacy Stock compatibility';

    public function handle(): int
    {
        $required = [
            ['bot_products', 'market_instrument_id'],
            ['trading_bots', 'market_instrument_id'],
            ['trading_bot_executions', 'market_instrument_id'],
            ['trading_bot_executions', 'broker_order_id'],
            ['trading_bot_executions', 'market_execution_transaction_id'],
        ];

        $schemaReady = true;
        foreach ($required as [$table, $column]) {
            $ok = Schema::hasTable($table) && Schema::hasColumn($table, $column);
            $schemaReady = $schemaReady && $ok;
            $this->line(sprintf('[%s] %s.%s', $ok ? 'OK' : 'MISSING', $table, $column));
        }

        if (! $schemaReady) {
            $this->error('E7 bot schema is not ready. Run migrations first.');
            return self::FAILURE;
        }

        $productMissing = DB::table('bot_products')->whereNull('market_instrument_id')->count();
        $botMissing = DB::table('trading_bots')->whereNull('market_instrument_id')->count();
        $stockProductMismatch = DB::table('bot_products as b')
            ->join('stocks as s', 's.id', '=', 'b.stock_id')
            ->whereNotNull('b.stock_id')
            ->whereColumn('b.market_instrument_id', '!=', 's.market_instrument_id')
            ->count();
        $stockBotMismatch = DB::table('trading_bots as b')
            ->join('stocks as s', 's.id', '=', 'b.stock_id')
            ->whereNotNull('b.stock_id')
            ->whereColumn('b.market_instrument_id', '!=', 's.market_instrument_id')
            ->count();

        $this->newLine();
        $this->table(
            ['Authority check', 'Count'],
            [
                ['Bot products missing MarketInstrument', $productMissing],
                ['Trading bots missing MarketInstrument', $botMissing],
                ['Legacy Stock product parent mismatches', $stockProductMismatch],
                ['Legacy Stock bot parent mismatches', $stockBotMismatch],
            ]
        );

        $assetRows = DB::table('trading_bots as b')
            ->join('market_instruments as m', 'm.id', '=', 'b.market_instrument_id')
            ->select('m.asset_class', DB::raw('COUNT(*) as bots'))
            ->groupBy('m.asset_class')
            ->orderBy('m.asset_class')
            ->get()
            ->map(fn ($row) => [strtoupper((string) $row->asset_class), (int) $row->bots])
            ->all();

        $this->newLine();
        $this->line('Trading bot universe:');
        $this->table(['Asset class', 'Bots'], $assetRows ?: [['NONE', 0]]);

        $completed = DB::table('trading_bot_executions')->where('status', 'completed')->count();
        $brokerLinked = DB::table('trading_bot_executions')->where('status', 'completed')->whereNotNull('broker_order_id')->count();
        $ledgerLinked = DB::table('trading_bot_executions')->where('status', 'completed')->whereNotNull('market_execution_transaction_id')->count();
        $legacyStockCompleted = DB::table('trading_bot_executions')->where('status', 'completed')->whereNotNull('stock_transaction_id')->count();

        $this->newLine();
        $this->table(
            ['Execution history', 'Count'],
            [
                ['Completed bot executions', $completed],
                ['Completed linked to BrokerOrder', $brokerLinked],
                ['Completed linked to unified execution ledger', $ledgerLinked],
                ['Completed retaining Stock compatibility link', $legacyStockCompleted],
            ]
        );

        $invalidAsset = DB::table('trading_bots as b')
            ->join('market_instruments as m', 'm.id', '=', 'b.market_instrument_id')
            ->whereNotIn('m.asset_class', [
                MarketInstrument::ASSET_STOCK,
                MarketInstrument::ASSET_FOREX,
                MarketInstrument::ASSET_CRYPTO,
            ])->count();

        $failed = $productMissing + $botMissing + $stockProductMismatch + $stockBotMismatch + $invalidAsset;

        if ($failed > 0) {
            $this->error('E7 multi-asset bot authority FAILED integrity inspection.');
            return self::FAILURE;
        }

        $this->info('E7 multi-asset bot identity/backfill authority is READY. Runtime bot execution acceptance is still required.');
        return self::SUCCESS;
    }
}
