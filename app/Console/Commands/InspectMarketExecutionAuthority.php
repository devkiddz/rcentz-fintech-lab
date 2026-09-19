<?php

namespace App\Console\Commands;

use App\Models\MarketInstrument;
use App\Services\MarketExecutionRouter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class InspectMarketExecutionAuthority extends Command
{
    protected $signature = 'markets:inspect-execution-authority {symbol? : Optional canonical symbol such as AAPL, EURUSD or BTCUSD}';
    protected $description = 'Inspect MarketInstrument execution routing without moving capital.';

    public function handle(MarketExecutionRouter $router): int
    {
        $symbol = strtoupper(trim((string) $this->argument('symbol')));

        $query = MarketInstrument::query()
            ->with(['canonicalStock', 'canonicalForexPair', 'canonicalCryptoPair'])
            ->orderBy('asset_class')
            ->orderBy('symbol');

        if ($symbol !== '') {
            $query->where(function ($q) use ($symbol) {
                $q->whereRaw('UPPER(symbol) = ?', [$symbol])
                    ->orWhereRaw("UPPER(REPLACE(display_symbol, '/', '')) = ?", [$symbol]);
            });
        }

        $instruments = $query->get();
        if ($instruments->isEmpty()) {
            $this->error('No MarketInstrument matched the requested symbol.');
            return self::FAILURE;
        }

        $sharedNonStockSchemaReady = Schema::hasTable('market_holdings')
            && Schema::hasColumn('market_execution_transactions', 'idempotency_key')
            && Schema::hasColumn('market_execution_transactions', 'settlement_currency')
            && Schema::hasColumn('market_execution_transactions', 'settlement_amount');
        $rows = [];
        $failures = 0;
        $summary = [];

        foreach ($instruments as $instrument) {
            try {
                $capabilities = $router->capabilities($instrument);
                $canExecute = $router->canExecute($instrument);
                $assetClass = strtoupper((string) $instrument->asset_class);

                $summary[$assetClass] ??= ['total' => 0, 'executable' => 0];
                $summary[$assetClass]['total']++;
                if ($canExecute) {
                    $summary[$assetClass]['executable']++;
                }

                if ($instrument->isStock()) {
                    $expectedExecutable = (bool) $instrument->is_active
                        && $instrument->canonicalStock !== null
                        && (bool) $instrument->canonicalStock->is_active;
                } elseif ($instrument->isForex()) {
                    $expectedExecutable = $sharedNonStockSchemaReady
                        && (bool) $instrument->is_active
                        && $instrument->canonicalForexPair !== null
                        && (bool) $instrument->canonicalForexPair->is_active;
                } elseif ($instrument->isCrypto()) {
                    $expectedExecutable = $sharedNonStockSchemaReady
                        && (bool) $instrument->is_active
                        && $instrument->canonicalCryptoPair !== null
                        && (bool) $instrument->canonicalCryptoPair->is_active;
                } else {
                    $expectedExecutable = false;
                }

                if ($canExecute !== $expectedExecutable) {
                    $failures++;
                }

                $rows[] = [
                    $instrument->id,
                    $instrument->display_symbol ?: $instrument->symbol,
                    $assetClass,
                    $instrument->is_active ? 'ACTIVE' : 'INACTIVE',
                    $canExecute ? 'READY' : 'BLOCKED',
                    class_basename((string) ($capabilities['adapter'] ?? '')),
                    (string) ($capabilities['quantity_unit'] ?? '-'),
                ];
            } catch (\Throwable $e) {
                $failures++;
                $rows[] = [
                    $instrument->id,
                    $instrument->display_symbol ?: $instrument->symbol,
                    strtoupper((string) $instrument->asset_class),
                    $instrument->is_active ? 'ACTIVE' : 'INACTIVE',
                    'ERROR',
                    '-',
                    $e->getMessage(),
                ];
            }
        }

        $this->table(['ID', 'Instrument', 'Asset', 'Runtime', 'Execution', 'Adapter', 'Quantity'], $rows);
        $summaryRows = collect($summary)->map(fn (array $values, string $assetClass) => [
            $assetClass,
            $values['total'],
            $values['executable'],
            $values['total'] - $values['executable'],
        ])->values()->all();
        $this->table(['Asset class', 'Instruments', 'Executable', 'Blocked'], $summaryRows);

        if ($failures > 0) {
            $this->error('MARKET_EXECUTION_AUTHORITY_FAILED');
            return self::FAILURE;
        }

        $this->info('Execution authority: Stock, Forex and active Crypto instruments route through MarketInstrument with asset-specific execution rules.');
        $this->info('MARKET_EXECUTION_AUTHORITY_OK');
        return self::SUCCESS;
    }
}
