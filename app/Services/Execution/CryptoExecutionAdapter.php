<?php

namespace App\Services\Execution;

use App\Contracts\MarketExecutionAdapter;
use App\Models\MarketInstrument;
use App\Models\User;
use App\Services\CryptoExecutionService;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class CryptoExecutionAdapter implements MarketExecutionAdapter
{
    public function __construct(
        private readonly CryptoExecutionService $executor
    ) {}

    public function assetClass(): string
    {
        return MarketInstrument::ASSET_CRYPTO;
    }

    public function supports(MarketInstrument $instrument): bool
    {
        return $instrument->isCrypto() && $instrument->canonicalCryptoPair()->exists();
    }

    public function capabilities(MarketInstrument $instrument): array
    {
        $pair = $instrument->canonicalCryptoPair()->first();
        $schemaReady = Schema::hasTable('market_holdings')
            && Schema::hasColumn('market_execution_transactions', 'idempotency_key')
            && Schema::hasColumn('market_execution_transactions', 'settlement_currency')
            && Schema::hasColumn('market_execution_transactions', 'settlement_amount');
        $ready = $instrument->isCrypto()
            && $instrument->is_active
            && $pair !== null
            && (bool) $pair->is_active
            && $schemaReady;

        return [
            'asset_class' => $this->assetClass(),
            'adapter' => self::class,
            'executable' => $ready,
            'quantity_unit' => 'asset_units_or_settlement_amount',
            'session_model' => 'continuous_24_7',
            'settlement_model' => 'wallet_cash_and_market_holding',
            'native_ledger' => 'market_execution_transactions',
            'execution_model' => 'spot_asset_ownership_no_leverage',
            'live_quote_model' => 'realtime_fail_closed',
            'reason' => $ready
                ? 'Crypto spot execution authority is installed; Live fills require a fresh execution quote and settlement conversion when needed. Short selling and leverage remain disabled.'
                : 'Crypto execution schema or active pair authority is unavailable.',
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
