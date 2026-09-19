<?php

namespace App\Console\Commands;

use App\Models\MarketExecutionTransaction;
use App\Models\MarketHolding;
use App\Models\MarketInstrument;
use App\Models\TradePosition;
use App\Services\CryptoExecutionQuoteService;
use App\Services\MarketExecutionRouter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class InspectCryptoExecutionAuthority extends Command
{
    protected $signature = 'markets:inspect-crypto-execution';
    protected $description = 'Inspect Crypto execution authority without moving capital or requesting a live fill.';

    public function handle(
        MarketExecutionRouter $router,
        CryptoExecutionQuoteService $quotes
    ): int {
        $schemaReady = Schema::hasTable('market_holdings')
            && Schema::hasColumn('market_execution_transactions', 'idempotency_key')
            && Schema::hasColumn('market_execution_transactions', 'settlement_currency')
            && Schema::hasColumn('market_execution_transactions', 'settlement_amount');

        if (! $schemaReady) {
            $this->error('E3 shared non-Stock execution schema is unavailable.');
            return self::FAILURE;
        }

        $instruments = MarketInstrument::query()
            ->where('asset_class', 'crypto')
            ->with('canonicalCryptoPair')
            ->orderBy('symbol')
            ->get();

        if ($instruments->isEmpty()) {
            $this->error('No Crypto MarketInstrument rows are registered.');
            return self::FAILURE;
        }

        $rows = [];
        $failures = 0;
        $readyCount = 0;

        foreach ($instruments as $instrument) {
            $pair = $instrument->canonicalCryptoPair;
            $expected = (bool) $instrument->is_active
                && $pair !== null
                && (bool) $pair->is_active;
            $actual = false;

            try {
                $actual = $router->canExecute($instrument);
            } catch (\Throwable $e) {
                $failures++;
            }

            if ($actual !== $expected) {
                $failures++;
            }
            if ($actual) {
                $readyCount++;
            }

            $rows[] = [
                $instrument->display_symbol ?: $instrument->symbol,
                $instrument->is_active ? 'ACTIVE' : 'INACTIVE',
                $pair ? ($pair->is_active ? 'ACTIVE' : 'INACTIVE') : 'MISSING',
                $pair ? ($pair->external_feed_enabled ? 'ENABLED' : 'DISABLED') : '-',
                $actual ? 'READY' : 'BLOCKED',
                $instrument->base_asset ?: '-',
                $instrument->quote_asset ?: '-',
            ];
        }

        $cryptoIds = $instruments->pluck('id');
        $receipts = MarketExecutionTransaction::query()
            ->whereIn('market_instrument_id', $cryptoIds)
            ->count();
        $positions = TradePosition::query()
            ->whereIn('market_instrument_id', $cryptoIds)
            ->whereNull('stock_id')
            ->count();
        $holdings = MarketHolding::query()
            ->whereIn('market_instrument_id', $cryptoIds)
            ->count();
        $orphans = MarketHolding::query()
            ->whereDoesntHave('marketInstrument')
            ->count();

        $this->table(
            ['Instrument', 'Parent', 'Pair', 'Live feed', 'Execution', 'Base', 'Quote'],
            $rows
        );
        $this->table(['Authority', 'Value'], [
            ['Crypto instruments', $instruments->count()],
            ['Structurally executable', $readyCount],
            ['Execution quote provider configured', $quotes->providerAvailable() ? 'YES' : 'NO'],
            ['Crypto execution receipts', $receipts],
            ['Crypto positions', $positions],
            ['Crypto holdings', $holdings],
            ['Holding parent orphans', $orphans],
        ]);

        if ($failures > 0 || $orphans > 0) {
            $this->error('CRYPTO_EXECUTION_E4_FAILED');
            return self::FAILURE;
        }

        $this->info('E4 authority rule: Crypto uses spot asset units, fresh 24/7 execution quotes, wallet settlement, MarketHolding ownership and unified MarketExecutionTransaction receipts.');
        $this->info('Short selling, leverage and automatic Signal execution remain disabled.');
        $this->info('CRYPTO_EXECUTION_E4_OK');
        return self::SUCCESS;
    }
}
