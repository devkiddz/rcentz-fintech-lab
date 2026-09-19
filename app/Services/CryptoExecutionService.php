<?php

namespace App\Services;

use App\Models\MarketExecutionTransaction;
use App\Models\MarketHolding;
use App\Models\MarketInstrument;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

final class CryptoExecutionService
{
    public function __construct(
        private FinancialActivityService $activity,
        private MarketPriceRouter $prices,
        private CryptoExecutionQuoteService $quotes,
        private MarketSettlementService $settlement,
        private MarketExecutionLedgerService $ledger
    ) {}

    public function execute(
        User $user,
        MarketInstrument $instrument,
        string $side,
        float $quantity,
        array $context = []
    ): MarketExecutionTransaction {
        if (! $instrument->isCrypto() || ! $instrument->is_active) {
            throw new RuntimeException('Crypto execution requires an active Crypto MarketInstrument.');
        }

        $pair = $instrument->canonicalCryptoPair()->first();
        if (! $pair || ! $pair->is_active) {
            throw new RuntimeException('Crypto execution adapter is unavailable for this instrument.');
        }

        $side = strtolower(trim($side));
        if (! in_array($side, ['buy', 'sell'], true)) {
            throw new InvalidArgumentException('Crypto execution side must be buy or sell.');
        }

        [$quantityMode, $requestedQuantity] = $this->requestedQuantity($quantity, $context);
        $marketplace = $this->prices->normalizeMarketplace($context['marketplace'] ?? $this->prices->activeMarketplace());
        $idempotencyKey = $this->idempotencyKey($context['idempotency_key'] ?? null);

        if ($idempotencyKey) {
            $existing = $this->ledger->idempotentForUser($user->id, $idempotencyKey);
            if ($existing) {
                $this->assertIdempotentRequest(
                    $existing,
                    $instrument,
                    $side,
                    $marketplace,
                    $quantityMode,
                    $requestedQuantity
                );
                return $existing;
            }
        }

        $walletSnapshot = $user->wallet;
        if (! $walletSnapshot) {
            throw new RuntimeException('Trading wallet is unavailable.');
        }

        $settlementCurrency = strtoupper((string) ($walletSnapshot->currency ?: 'USD'));
        $quote = $this->quotes->quote($instrument, $side, $marketplace);
        $price = (float) $quote['price'];
        $requireFreshRates = $marketplace === 'live';

        [$units, $settlementAmount] = $this->resolveUnitsAndSettlement(
            $instrument,
            $requestedQuantity,
            $quantityMode,
            $price,
            $settlementCurrency,
            $requireFreshRates
        );

        if ($settlementAmount < 0.01) {
            throw new RuntimeException('Crypto execution value is below the wallet settlement minimum.');
        }

        return DB::transaction(function () use (
            $user,
            $instrument,
            $side,
            $units,
            $requestedQuantity,
            $quantityMode,
            $marketplace,
            $idempotencyKey,
            $settlementCurrency,
            $settlementAmount,
            $price,
            $quote,
            $context,
            $requireFreshRates
        ) {
            $wallet = $user->wallet()->lockForUpdate()->firstOrFail();
            $lockedCurrency = strtoupper((string) ($wallet->currency ?: 'USD'));
            if ($lockedCurrency !== $settlementCurrency) {
                throw new RuntimeException('Wallet settlement currency changed during execution. Please retry.');
            }

            if ($idempotencyKey) {
                $existing = $this->ledger->idempotentForUser($user->id, $idempotencyKey);
                if ($existing) {
                    $this->assertIdempotentRequest(
                        $existing,
                        $instrument,
                        $side,
                        $marketplace,
                        $quantityMode,
                        $requestedQuantity
                    );
                    return $existing;
                }
            }

            $holding = MarketHolding::query()
                ->where('user_id', $user->id)
                ->where('market_instrument_id', $instrument->id)
                ->where('marketplace', $marketplace)
                ->lockForUpdate()
                ->first();

            if ($holding && strtoupper((string) $holding->settlement_currency) !== $settlementCurrency) {
                throw new RuntimeException('Existing Crypto holding uses a different settlement currency.');
            }

            $before = $this->activity->snapshot($wallet);
            $reference = $this->activity->reference('CRYPTO-'.strtoupper($side));
            $method = $this->internalMethod();
            $actorType = (string) ($context['actor_type'] ?? 'system');
            $actorId = isset($context['actor_id']) ? (int) $context['actor_id'] : null;
            $source = (string) ($context['source'] ?? 'crypto_trade');
            $sourceId = isset($context['source_id']) ? (int) $context['source_id'] : null;
            $positionId = isset($context['position_id']) ? (int) $context['position_id'] : null;

            if ($side === 'buy') {
                if (! $wallet->canWithdraw($settlementAmount)) {
                    throw new RuntimeException('Insufficient available balance for Crypto purchase.');
                }

                $walletTx = $wallet->transactions()->create([
                    'payment_method_id' => $method->id,
                    'type' => 'investment',
                    'direction' => 'debit',
                    'amount' => $settlementAmount,
                    'fee' => 0,
                    'status' => 'completed',
                    'reference_id' => $reference,
                    'description' => 'Crypto purchase: '.$instrument->display_symbol,
                ]);

                $execution = $this->ledger->record([
                    'user_id' => $user->id,
                    'market_instrument_id' => $instrument->id,
                    'wallet_transaction_id' => $walletTx->id,
                    'trade_position_id' => $positionId,
                    'native_type' => 'crypto_spot_execution',
                    'native_id' => null,
                    'side' => 'buy',
                    'execution_source' => $source,
                    'marketplace' => $marketplace,
                    'quantity' => $units,
                    'price' => $price,
                    'gross_value' => $settlementAmount,
                    'fee' => 0,
                    'realized_profit_loss' => null,
                    'settlement_currency' => $settlementCurrency,
                    'settlement_amount' => $settlementAmount,
                    'idempotency_key' => $idempotencyKey,
                    'status' => 'completed',
                    'executed_at' => now(),
                    'metadata' => $this->executionMetadata(
                        $instrument,
                        $quote,
                        $context,
                        $requestedQuantity,
                        $quantityMode,
                        $sourceId
                    ),
                ]);

                $oldQty = (float) ($holding?->quantity ?? 0);
                $oldInvested = (float) ($holding?->total_invested ?? 0);
                $newQty = $oldQty + $units;
                $newInvested = $oldInvested + $settlementAmount;
                $averageEntry = $newQty > 0
                    ? (((float) ($holding?->average_entry_price ?? 0) * $oldQty) + ($price * $units)) / $newQty
                    : $price;
                $currentValue = round($this->settlement->amountForBaseUnits(
                    $instrument,
                    $newQty,
                    $price,
                    $settlementCurrency,
                    $requireFreshRates
                ), 2);
                $unrealized = $currentValue - $newInvested;

                $values = [
                    'quantity' => $newQty,
                    'average_entry_price' => $averageEntry,
                    'total_invested' => $newInvested,
                    'current_value' => $currentValue,
                    'unrealized_gain_loss' => $unrealized,
                    'unrealized_gain_loss_percentage' => $newInvested > 0
                        ? ($unrealized / $newInvested) * 100
                        : 0,
                    'metadata' => $this->holdingMetadata($instrument),
                ];

                if ($holding) {
                    $holding->update($values);
                } else {
                    MarketHolding::create(array_merge($values, [
                        'user_id' => $user->id,
                        'market_instrument_id' => $instrument->id,
                        'marketplace' => $marketplace,
                        'settlement_currency' => $settlementCurrency,
                    ]));
                }

                $wallet->deductFunds($settlementAmount);

                $this->activity->record(
                    $user,
                    $source.'.buy',
                    'Crypto asset purchased',
                    'Purchased '.number_format($units, 8).' '.$instrument->base_asset.' through '.$instrument->display_symbol.'.',
                    $reference,
                    'completed',
                    'debit',
                    $settlementAmount,
                    $wallet,
                    $walletTx,
                    null,
                    $before,
                    [
                        'market_instrument_id' => $instrument->id,
                        'asset_class' => 'crypto',
                        'quantity_units' => $units,
                        'quantity_mode' => $quantityMode,
                        'execution_price' => $price,
                        'marketplace' => $marketplace,
                        'market_execution_transaction_id' => $execution->id,
                    ],
                    $actorType,
                    $actorId
                );

                return $execution;
            }

            if (! $holding || (float) $holding->quantity < $units) {
                throw new RuntimeException('Insufficient Crypto holding for this sale. Short selling is not enabled.');
            }

            $oldQty = (float) $holding->quantity;
            $oldInvested = (float) $holding->total_invested;
            $costBasisReleased = $oldQty > 0 ? $oldInvested * ($units / $oldQty) : 0;
            $walletCredit = round($this->settlement->amountForBaseUnits(
                $instrument,
                $units,
                $price,
                $settlementCurrency,
                $requireFreshRates
            ), 2);
            $realized = $walletCredit - $costBasisReleased;

            if ($walletCredit < 0.01) {
                throw new RuntimeException('Crypto sale value is below the wallet settlement minimum.');
            }

            $walletTx = $wallet->transactions()->create([
                'payment_method_id' => $method->id,
                'type' => 'investment',
                'direction' => 'credit',
                'amount' => $walletCredit,
                'fee' => 0,
                'status' => 'completed',
                'reference_id' => $reference,
                'description' => 'Crypto sale: '.$instrument->display_symbol,
            ]);

            $execution = $this->ledger->record([
                'user_id' => $user->id,
                'market_instrument_id' => $instrument->id,
                'wallet_transaction_id' => $walletTx->id,
                'trade_position_id' => $positionId,
                'native_type' => 'crypto_spot_execution',
                'native_id' => null,
                'side' => 'sell',
                'execution_source' => $source,
                'marketplace' => $marketplace,
                'quantity' => $units,
                'price' => $price,
                'gross_value' => $walletCredit,
                'fee' => 0,
                'realized_profit_loss' => $realized,
                'settlement_currency' => $settlementCurrency,
                'settlement_amount' => $walletCredit,
                'idempotency_key' => $idempotencyKey,
                'status' => 'completed',
                'executed_at' => now(),
                'metadata' => array_merge(
                    $this->executionMetadata(
                        $instrument,
                        $quote,
                        $context,
                        $requestedQuantity,
                        $quantityMode,
                        $sourceId
                    ),
                    [
                        'cost_basis_released' => round($costBasisReleased, 8),
                        'realized_profit_loss' => round($realized, 8),
                    ]
                ),
            ]);

            $remaining = max(0, $oldQty - $units);
            $remainingInvested = max(0, $oldInvested - $costBasisReleased);

            if ($remaining > 0) {
                $remainingValue = round($this->settlement->amountForBaseUnits(
                    $instrument,
                    $remaining,
                    $price,
                    $settlementCurrency,
                    $requireFreshRates
                ), 2);
                $remainingPnl = $remainingValue - $remainingInvested;

                $holding->update([
                    'quantity' => $remaining,
                    'total_invested' => $remainingInvested,
                    'current_value' => $remainingValue,
                    'unrealized_gain_loss' => $remainingPnl,
                    'unrealized_gain_loss_percentage' => $remainingInvested > 0
                        ? ($remainingPnl / $remainingInvested) * 100
                        : 0,
                ]);
            } else {
                $holding->delete();
            }

            $wallet->addFunds($walletCredit);

            $this->activity->record(
                $user,
                $source.'.sell',
                'Crypto asset sold',
                'Sold '.number_format($units, 8).' '.$instrument->base_asset.' through '.$instrument->display_symbol.'.',
                $reference,
                'completed',
                'credit',
                $walletCredit,
                $wallet,
                $walletTx,
                null,
                $before,
                [
                    'market_instrument_id' => $instrument->id,
                    'asset_class' => 'crypto',
                    'quantity_units' => $units,
                    'quantity_mode' => $quantityMode,
                    'execution_price' => $price,
                    'marketplace' => $marketplace,
                    'market_execution_transaction_id' => $execution->id,
                    'cost_basis_released' => round($costBasisReleased, 8),
                    'realized_profit_loss' => round($realized, 8),
                ],
                $actorType,
                $actorId
            );

            return $execution;
        });
    }

    private function requestedQuantity(float $quantity, array $context): array
    {
        $mode = strtolower(trim((string) ($context['quantity_mode'] ?? 'units')));
        if (! in_array($mode, ['units', 'settlement_amount'], true)) {
            throw new InvalidArgumentException('Crypto quantity mode must be units or settlement_amount.');
        }

        if ($mode === 'settlement_amount') {
            if ($quantity < 1) {
                throw new InvalidArgumentException('Crypto settlement amount must be at least 1.00.');
            }
            return [$mode, round($quantity, 2)];
        }

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Crypto asset quantity must be greater than zero.');
        }

        $units = round($quantity, 8);
        if ($units <= 0) {
            throw new InvalidArgumentException('Crypto asset quantity is below supported precision.');
        }

        return [$mode, $units];
    }

    private function resolveUnitsAndSettlement(
        MarketInstrument $instrument,
        float $requestedQuantity,
        string $quantityMode,
        float $price,
        string $settlementCurrency,
        bool $requireFreshRates
    ): array {
        if ($quantityMode === 'units') {
            $units = round($requestedQuantity, 8);
        } else {
            $oneUnitValue = $this->settlement->amountForBaseUnits(
                $instrument,
                1.0,
                $price,
                $settlementCurrency,
                $requireFreshRates
            );
            if ($oneUnitValue <= 0) {
                throw new RuntimeException('Crypto settlement conversion is unavailable.');
            }
            $units = round($requestedQuantity / $oneUnitValue, 8);
        }

        if ($units <= 0) {
            throw new RuntimeException('Crypto execution quantity resolved below supported precision.');
        }

        $amount = round($this->settlement->amountForBaseUnits(
            $instrument,
            $units,
            $price,
            $settlementCurrency,
            $requireFreshRates
        ), 2);

        return [$units, $amount];
    }

    private function idempotencyKey(mixed $value): ?string
    {
        $key = trim((string) ($value ?? ''));
        if ($key === '') {
            return null;
        }

        if (strlen($key) > 120) {
            throw new InvalidArgumentException('Execution idempotency key is too long.');
        }

        return $key;
    }

    private function assertIdempotentRequest(
        MarketExecutionTransaction $existing,
        MarketInstrument $instrument,
        string $side,
        string $marketplace,
        string $quantityMode,
        float $requestedQuantity
    ): void {
        $metadata = $existing->metadata ?? [];
        $storedMode = (string) ($metadata['quantity_mode'] ?? '');
        $storedRequested = (float) ($metadata['requested_quantity'] ?? 0);
        $tolerance = $quantityMode === 'settlement_amount' ? 0.005 : 0.00000001;

        $matches = (int) $existing->market_instrument_id === (int) $instrument->id
            && $existing->side === $side
            && $existing->marketplace === $marketplace
            && $storedMode === $quantityMode
            && abs($storedRequested - $requestedQuantity) < $tolerance;

        if (! $matches) {
            throw new RuntimeException('Idempotency key was already used for a different execution request.');
        }
    }

    private function executionMetadata(
        MarketInstrument $instrument,
        array $quote,
        array $context,
        float $requestedQuantity,
        string $quantityMode,
        ?int $sourceId
    ): array {
        return [
            'asset_class' => 'crypto',
            'execution_model' => 'spot_asset_ownership_no_leverage',
            'base_asset' => $instrument->base_asset,
            'quote_asset' => $instrument->quote_asset,
            'requested_quantity' => $requestedQuantity,
            'quantity_mode' => $quantityMode,
            'quote_source' => $quote['source'] ?? null,
            'quote_captured_at' => isset($quote['captured_at']) && $quote['captured_at'] instanceof \DateTimeInterface
                ? $quote['captured_at']->format(DATE_ATOM)
                : null,
            'quote_age_seconds' => $quote['age_seconds'] ?? null,
            'bid' => $quote['bid'] ?? null,
            'ask' => $quote['ask'] ?? null,
            'source_id' => $sourceId,
            'requested_context' => array_intersect_key($context, array_flip([
                'context_type',
                'context_id',
                'signal_id',
            ])),
        ];
    }

    private function holdingMetadata(MarketInstrument $instrument): array
    {
        return [
            'asset_class' => 'crypto',
            'execution_model' => 'spot_asset_ownership_no_leverage',
            'base_asset' => $instrument->base_asset,
            'quote_asset' => $instrument->quote_asset,
        ];
    }

    private function internalMethod(): PaymentMethod
    {
        return PaymentMethod::firstOrCreate(
            ['name' => 'Internal Market Execution'],
            [
                'type' => 'traditional',
                'details' => 'Internal multi-asset execution settlement ledger method.',
                'is_active' => true,
                'allow_deposit' => false,
                'allow_withdraw' => false,
            ]
        );
    }
}
