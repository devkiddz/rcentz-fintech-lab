<?php

namespace App\Console\Commands;

use App\Models\MarketInstrument;
use App\Services\MarketExecutionRouter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InspectBrokerOrderAuthority extends Command
{
    protected $signature = 'broker:inspect-order-authority';
    protected $description = 'Inspect E5 broker order routing and audit authority without moving capital.';

    public function handle(MarketExecutionRouter $router): int
    {
        foreach (['broker_orders', 'broker_order_events', 'market_execution_transactions', 'market_instruments'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->error("{$table} is missing. Run migrations first.");
                return self::FAILURE;
            }
        }

        $required = [
            'public_id', 'user_id', 'market_instrument_id', 'market_execution_transaction_id',
            'side', 'order_type', 'quantity', 'quantity_mode', 'status', 'idempotency_key',
        ];
        foreach ($required as $column) {
            if (! Schema::hasColumn('broker_orders', $column)) {
                $this->error("broker_orders.{$column} is missing.");
                return self::FAILURE;
            }
        }

        $summary = [];
        $routingFailures = 0;
        $instruments = MarketInstrument::query()
            ->with(['canonicalStock', 'canonicalForexPair', 'canonicalCryptoPair'])
            ->whereIn('asset_class', ['stock', 'forex', 'crypto'])
            ->orderBy('asset_class')
            ->orderBy('symbol')
            ->get();

        foreach ($instruments as $instrument) {
            $asset = strtoupper((string) $instrument->asset_class);
            $summary[$asset] ??= ['total' => 0, 'ready' => 0];
            $summary[$asset]['total']++;
            try {
                if ($router->canExecute($instrument)) {
                    $summary[$asset]['ready']++;
                }
            } catch (\Throwable) {
                $routingFailures++;
            }
        }

        $orders = DB::table('broker_orders')->count();
        $filled = DB::table('broker_orders')->where('status', 'filled')->count();
        $open = DB::table('broker_orders')->whereIn('status', ['accepted', 'executing'])->count();
        $events = DB::table('broker_order_events')->count();
        $parentOrphans = DB::table('broker_orders as o')
            ->leftJoin('market_instruments as i', 'i.id', '=', 'o.market_instrument_id')
            ->whereNull('i.id')
            ->count();
        $receiptOrphans = DB::table('broker_orders as o')
            ->leftJoin('market_execution_transactions as e', 'e.id', '=', 'o.market_execution_transaction_id')
            ->whereNotNull('o.market_execution_transaction_id')
            ->whereNull('e.id')
            ->count();
        $filledWithoutReceipt = DB::table('broker_orders')
            ->where('status', 'filled')
            ->whereNull('market_execution_transaction_id')
            ->count();

        $this->table(['Asset class', 'Instruments', 'Execution ready'], collect($summary)
            ->map(fn ($v, $asset) => [$asset, $v['total'], $v['ready']])
            ->values()
            ->all());

        $this->table(['Broker order authority', 'Value'], [
            ['Orders', $orders],
            ['Filled orders', $filled],
            ['Open/in-flight orders', $open],
            ['Order lifecycle events', $events],
            ['Instrument parent orphans', $parentOrphans],
            ['Execution receipt orphans', $receiptOrphans],
            ['Filled orders missing receipt', $filledWithoutReceipt],
        ]);

        if ($routingFailures > 0 || $parentOrphans > 0 || $receiptOrphans > 0 || $filledWithoutReceipt > 0) {
            $this->error('BROKER_ORDER_AUTHORITY_E5_FAILED');
            return self::FAILURE;
        }

        $this->info('E5 authority rule: customer intent becomes a BrokerOrder before asset-specific execution. Orders are idempotent, lifecycle-audited and filled only through the unified execution ledger.');
        $this->info('No Signal, notification or presentation layer may bypass BrokerOrder for future customer-initiated cross-asset execution.');
        $this->info('BROKER_ORDER_AUTHORITY_E5_OK');
        return self::SUCCESS;
    }
}
