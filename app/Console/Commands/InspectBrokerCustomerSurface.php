<?php

namespace App\Console\Commands;

use App\Models\MarketInstrument;
use App\Services\MarketExecutionRouter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class InspectBrokerCustomerSurface extends Command
{
    protected $signature = 'broker:inspect-customer-surface';
    protected $description = 'Inspect the unified customer brokerage surface without executing trades';

    public function handle(MarketExecutionRouter $execution): int
    {
        foreach (['broker_orders', 'broker_order_events', 'market_execution_transactions', 'trade_positions', 'market_holdings'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->error("{$table} is missing.");
                return self::FAILURE;
            }
        }

        $routes = [
            'broker.workstation',
            'broker.orders.submit',
            'broker.portfolio',
            'broker.orders',
            'broker.orders.show',
            'broker.activity',
            'broker.positions',
            'broker.positions.risk',
            'broker.positions.close',
        ];

        $missingRoutes = collect($routes)->reject(fn ($name) => Route::has($name))->values();
        $viewFiles = [
            'broker/workstation.blade.php',
            'broker/portfolio.blade.php',
            'broker/orders.blade.php',
            'broker/order-show.blade.php',
            'broker/activity.blade.php',
            'broker/positions.blade.php',
            'market-instruments/_registry.blade.php',
        ];
        $missingViews = collect($viewFiles)
            ->reject(fn ($file) => is_file(resource_path('views/'.$file)))
            ->values();

        $summary = [];
        $routingFailures = 0;
        $instruments = MarketInstrument::query()
            ->with(['canonicalStock', 'canonicalForexPair', 'canonicalCryptoPair'])
            ->where('is_active', true)
            ->whereIn('asset_class', ['stock', 'forex', 'crypto'])
            ->orderBy('asset_class')
            ->orderBy('symbol')
            ->get();

        foreach ($instruments as $instrument) {
            $asset = strtoupper((string) $instrument->asset_class);
            $summary[$asset] ??= ['total' => 0, 'ready' => 0];
            $summary[$asset]['total']++;
            try {
                if ($execution->canExecute($instrument)) {
                    $summary[$asset]['ready']++;
                }
            } catch (\Throwable) {
                $routingFailures++;
            }
        }

        $positionOrphans = DB::table('trade_positions as p')
            ->leftJoin('market_instruments as i', 'i.id', '=', 'p.market_instrument_id')
            ->whereNull('i.id')
            ->count();
        $executionOrphans = DB::table('market_execution_transactions as e')
            ->leftJoin('market_instruments as i', 'i.id', '=', 'e.market_instrument_id')
            ->whereNull('i.id')
            ->count();
        $orders = DB::table('broker_orders')->count();
        $executions = DB::table('market_execution_transactions')->count();
        $positions = DB::table('trade_positions')->count();

        $this->table(['Asset class', 'Active', 'Execution ready'], collect($summary)
            ->map(fn ($v, $asset) => [$asset, $v['total'], $v['ready']])
            ->values()
            ->all());

        $this->table(['Customer surface authority', 'Value'], [
            ['Broker routes present', count($routes) - $missingRoutes->count().' / '.count($routes)],
            ['Broker views present', count($viewFiles) - $missingViews->count().' / '.count($viewFiles)],
            ['Broker orders', $orders],
            ['Unified execution receipts', $executions],
            ['Trade positions', $positions],
            ['Position parent orphans', $positionOrphans],
            ['Execution parent orphans', $executionOrphans],
        ]);

        $failures = $missingRoutes->count() + $missingViews->count() + $routingFailures + $positionOrphans + $executionOrphans;
        foreach (['STOCK', 'FOREX', 'CRYPTO'] as $asset) {
            if (($summary[$asset]['total'] ?? 0) < 1 || ($summary[$asset]['ready'] ?? 0) < 1) {
                $failures++;
            }
        }

        if ($missingRoutes->isNotEmpty()) {
            $this->error('Missing routes: '.$missingRoutes->implode(', '));
        }
        if ($missingViews->isNotEmpty()) {
            $this->error('Missing views: '.$missingViews->implode(', '));
        }

        if ($failures > 0) {
            $this->error('BROKER_CUSTOMER_SURFACE_E6_FAILED');
            return self::FAILURE;
        }

        $this->info('E6 authority rule: customer trading uses BrokerOrder before capital execution; portfolio, positions, orders and executions are unified across Stocks, Forex and Crypto.');
        $this->info('Instrument overview remains market discovery; the workstation owns execution.');
        $this->info('BROKER_CUSTOMER_SURFACE_E6_OK');
        return self::SUCCESS;
    }
}
