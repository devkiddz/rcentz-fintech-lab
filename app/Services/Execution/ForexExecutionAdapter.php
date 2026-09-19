<?php

namespace App\Services\Execution;

use App\Contracts\MarketExecutionAdapter;
use App\Models\MarketInstrument;
use App\Models\User;
use App\Services\ForexExecutionService;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class ForexExecutionAdapter implements MarketExecutionAdapter
{
    public function __construct(
        private readonly ForexExecutionService $executor
    ) {}

    public function assetClass(): string
    {
        return MarketInstrument::ASSET_FOREX;
    }

    public function supports(MarketInstrument $instrument): bool
    {
        if (! $instrument->isForex()) {
            return false;
        }

        return $instrument->canonicalForexPair()->exists()
            || ($instrument->forex_pair_id && $instrument->forexPair()->exists());
    }

    public function capabilities(MarketInstrument $instrument): array
    {
        $pair = $instrument->canonicalForexPair()->first() ?? $instrument->forexPair;
        $schemaReady = Schema::hasTable('market_holdings')
            && Schema::hasColumn('market_execution_transactions', 'idempotency_key')
            && Schema::hasColumn('market_execution_transactions', 'settlement_currency')
            && Schema::hasColumn('market_execution_transactions', 'settlement_amount');
        $ready = $instrument->isForex()
            && $instrument->is_active
            && $pair !== null
            && (bool) $pair->is_active
            && $schemaReady;

        return [
            'asset_class' => $this->assetClass(),
            'adapter' => self::class,
            'executable' => $ready,
            'quantity_unit' => 'base_units_or_standard_lots',
            'session_model' => 'forex_24_5_session_aware',
            'settlement_model' => 'wallet_cash_collateral_and_market_holding',
            'native_ledger' => 'market_execution_transactions',
            'execution_model' => 'cash_collateralized_long_no_leverage',
            'live_quote_model' => 'realtime_fail_closed',
            'reason' => $ready
                ? 'Forex execution authority is installed; Live fills require a fresh execution quote and fresh settlement rates. Positions are 1:1 cash-collateralized with no leverage.'
                : 'Forex execution schema or active pair authority is unavailable.',
        ];
    }

    public function execute(
        User $user,
        MarketInstrument $instrument,
        string $side,
        float $quantity,
        array $context = []
    ): mixed {
        $capabilities = $this->capabilities($instrument);
        if (! ($capabilities['executable'] ?? false)) {
            throw new RuntimeException((string) $capabilities['reason']);
        }

        return $this->executor->execute($user, $instrument, $side, $quantity, $context);
    }
}
