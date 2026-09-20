<?php

namespace App\Console\Commands;

use App\Models\CopyRelationship;
use App\Models\CopyTradeExecution;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InspectMultiAssetCopyAuthority extends Command
{
    protected $signature = 'copy-trading:inspect-multi-asset-authority';
    protected $description = 'Inspect E7 MS2A multi-asset Copy Trading execution authority';

    public function handle(): int
    {
        foreach ([
            'market_instrument_id',
            'provider_market_execution_transaction_id',
            'provider_broker_order_id',
            'follower_market_execution_transaction_id',
            'follower_broker_order_id',
        ] as $column) {
            if (! Schema::hasColumn('copy_trade_executions', $column)) {
                $this->error('Missing copy_trade_executions.'.$column);
                return self::FAILURE;
            }
            $this->line('[OK] copy_trade_executions.'.$column);
        }

        $checks = [
            'Copy executions missing MarketInstrument' => CopyTradeExecution::query()
                ->whereNotNull('provider_market_execution_transaction_id')
                ->whereNull('market_instrument_id')
                ->count(),
            'Legacy provider Stock rows missing unified execution' => CopyTradeExecution::query()
                ->whereNotNull('provider_stock_transaction_id')
                ->whereNull('provider_market_execution_transaction_id')
                ->count(),
            'Completed follower Stock rows missing unified execution' => CopyTradeExecution::query()
                ->where('status', 'completed')
                ->whereNotNull('follower_stock_transaction_id')
                ->whereNull('follower_market_execution_transaction_id')
                ->count(),
            'Active relationships missing strategy authority' => CopyRelationship::query()
                ->where('status', 'active')
                ->whereNull('copy_strategy_id')
                ->count(),
        ];

        $this->newLine();
        $this->table(
            ['Authority check', 'Count'],
            collect($checks)->map(fn ($count, $label) => [$label, $count])->values()->all()
        );

        $this->newLine();
        $universe = DB::table('copy_trade_executions as c')
            ->join('market_instruments as m', 'm.id', '=', 'c.market_instrument_id')
            ->selectRaw('UPPER(m.asset_class) as asset_class, COUNT(*) as total')
            ->groupBy('m.asset_class')
            ->orderBy('m.asset_class')
            ->get();

        $this->line('Copy execution universe:');
        $this->table(
            ['Asset class', 'Executions'],
            $universe->map(fn ($row) => [$row->asset_class, $row->total])->all()
        );

        $completed = CopyTradeExecution::query()->where('status', 'completed')->count();
        $brokerLinked = CopyTradeExecution::query()->where('status', 'completed')->whereNotNull('follower_broker_order_id')->count();
        $unifiedLinked = CopyTradeExecution::query()->where('status', 'completed')->whereNotNull('follower_market_execution_transaction_id')->count();

        $this->table([
            'Execution history', 'Count'
        ], [
            ['Completed copy executions', $completed],
            ['Completed linked to follower BrokerOrder', $brokerLinked],
            ['Completed linked to follower unified execution', $unifiedLinked],
        ]);

        if (collect($checks)->contains(fn ($count) => $count > 0)) {
            $this->warn('MS2A identity contains reconciliation items. Review before runtime acceptance.');
            return self::FAILURE;
        }

        $assetClasses = (int) $universe->count();
        $runtimeAccepted = $brokerLinked > 0 && $assetClasses >= 3;

        if ($runtimeAccepted) {
            $this->info('E7 MS2 multi-asset Copy Trading authority is READY with provider/follower BrokerOrder runtime evidence across Stock, Forex and Crypto.');
        } else {
            $this->info('E7 MS2A multi-asset Copy Trading authority is READY. Additional provider/follower runtime acceptance is still required.');
        }

        return self::SUCCESS;
    }
}
