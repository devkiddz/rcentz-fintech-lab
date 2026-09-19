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

final class ForexExecutionService
{
    private const STANDARD_LOT_UNITS = 100000.0;

    public function __construct(
        private FinancialActivityService $activity,
        private MarketPriceRouter $prices,
        private ForexExecutionQuoteService $quotes,
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
        if (! $instrument->isForex() || ! $instrument->is_active) {
            throw new RuntimeException('Forex execution requires an active Forex MarketInstrument.');
        }

        $pair = $instrument->canonicalForexPair()->first() ?? $instrument->forexPair;
        if (! $pair || ! $pair->is_active) {
            throw new RuntimeException('Forex execution adapter is unavailable for this instrument.');
        }

        $side = strtolower(trim($side));
        if (! in_array($side, ['buy', 'sell'], true)) {
            throw new InvalidArgumentException('Forex execution side must be buy or sell.');
        }

        [$units, $quantityMode, $requestedQuantity] = $this->normalizeQuantity($quantity, $context);
        $marketplace = $this->prices->normalizeMarketplace($context['marketplace'] ?? $this->prices->activeMarketplace());
        $idempotencyKey = $this->idempotencyKey($context['idempotency_key'] ?? null);

        if ($idempotencyKey) {
            $existing = $this->ledger->idempotentForUser($user->id, $idempotencyKey);
            if ($existing) {
                $this->assertIdempotentMatch($existing, $instrument, $side, $units, $marketplace);
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

        // E3 is a 1:1 cash-collateralized FX exposure model, not leveraged margin.
        // Entry collateral equals the base-unit notional expressed in wallet currency.
        $entryCollateral = round($this->settlement->amountForBaseUnits(
            $instrument,
            $units,
            $price,
            $settlementCurrency,
            $requireFreshRates
        ), 2);

        if ($entryCollateral <= 0) {
            throw new RuntimeException('Forex collateral amount is invalid.');
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
            $entryCollateral,
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
                    $this->assertIdempotentMatch($existing, $instrument, $side, $units, $marketplace);
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
                throw new RuntimeException('Existing Forex exposure uses a different settlement currency.');
            }

            $before = $this->activity->snapshot($wallet);
            $reference = $this->activity->reference('FX-'.strtoupper($side));
            $method = $this->internalMethod();
            $actorType = (string) ($context['actor_type'] ?? 'system');
            $actorId = isset($context['actor_id']) ? (int) $context['actor_id'] : null;
            $source = (string) ($context['source'] ?? 'forex_trade');
            $sourceId = isset($context['source_id']) ? (int) $context['source_id'] : null;
            $positionId = isset($context['position_id']) ? (int) $context['position_id'] : null;

            if ($side === 'buy') {
                if (! $wallet->canWithdraw($entryCollateral)) {
                    throw new RuntimeException('Insufficient available balance for Forex collateral.');
                }

                $walletTx = $wallet->transactions()->create([
                    'payment_method_id' => $method->id,
                    'type' => 'investment',
                    'direction' => 'debit',
                    'amount' => $entryCollateral,
                    'fee' => 0,
                    'status' => 'completed',
                    'reference_id' => $reference,
                    'description' => 'Forex collateral: '.$instrument->display_symbol,
                ]);

                $execution = $this->ledger->record([
                    'user_id' => $user->id,
                    'market_instrument_id' => $instrument->id,
                    'wallet_transaction_id' => $walletTx->id,
                    'trade_position_id' => $positionId,
                    'native_type' => 'forex_cash_collateral_execution',
                    'native_id' => null,
                    'side' => 'buy',
                    'execution_source' => $source,
                    'marketplace' => $marketplace,
                    'quantity' => $units,
                    'price' => $price,
                    'gross_value' => $entryCollateral,
                    'fee' => 0,
                    'realized_profit_loss' => null,
                    'settlement_currency' => $settlementCurrency,
                    'settlement_amount' => $entryCollateral,
                    'idempotency_key' => $idempotencyKey,
                    'status' => 'completed',
                    'executed_at' => now(),
                    'metadata' => array_merge(
                        $this->executionMetadata($instrument, $quote, $context, $requestedQuantity, $quantityMode, $sourceId),
                        ['collateral_committed' => $entryCollateral]
                    ),
                ]);

                $oldQty = (float) ($holding?->quantity ?? 0);
                $oldInvested = (float) ($holding?->total_invested ?? 0);
                $newQty = $oldQty + $units;
                $newInvested = $oldInvested + $entryCollateral;
                $averageEntry = $newQty > 0
                    ? (((float) ($holding?->average_entry_price ?? 0) * $oldQty) + ($price * $units)) / $newQty
                    : $price;
                $unrealizedQuote = ($price - $averageEntry) * $newQty;
                $unrealizedSettlement = $this->settlement->profitLossToSettlement(
                    $instrument,
                    $unrealizedQuote,
                    $price,
                    $settlementCurrency,
                    $requireFreshRates
                );
                $currentValue = max(0, $newInvested + $unrealizedSettlement);

                $values = [
                    'quantity' => $newQty,
                    'average_entry_price' => $averageEntry,
                    'total_invested' => $newInvested,
                    'current_value' => $currentValue,
                    'unrealized_gain_loss' => $unrealizedSettlement,
                    'unrealized_gain_loss_percentage' => $newInvested > 0
                        ? ($unrealizedSettlement / $newInvested) * 100
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

                $wallet->deductFunds($entryCollateral);

                $this->activity->record(
                    $user,
                    $source.'.buy',
                    'Forex position opened',
                    'Opened '.number_format($units, 2).' base units on '.$instrument->display_symbol.' with 1:1 cash collateral.',
                    $reference,
                    'completed',
                    'debit',
                    $entryCollateral,
                    $wallet,
                    $walletTx,
                    null,
                    $before,
                    [
                        'market_instrument_id' => $instrument->id,
                        'asset_class' => 'forex',
                        'quantity_units' => $units,
                        'quantity_mode' => $quantityMode,
                        'execution_price' => $price,
                        'marketplace' => $marketplace,
                        'market_execution_transaction_id' => $execution->id,
                        'collateral_committed' => $entryCollateral,
                    ],
                    $actorType,
                    $actorId
                );

                return $execution;
            }

            if (! $holding || (float) $holding->quantity < $units) {
                throw new RuntimeException('Insufficient Forex exposure for this close. Short selling is not enabled.');
            }

            $oldQty = (float) $holding->quantity;
            $oldInvested = (float) $holding->total_invested;
            $averageEntry = (float) $holding->average_entry_price;
            $collateralReleased = $oldQty > 0 ? $oldInvested * ($units / $oldQty) : 0;
            $profitLossQuote = ($price - $averageEntry) * $units;
            $realized = $this->settlement->profitLossToSettlement(
                $instrument,
                $profitLossQuote,
                $price,
                $settlementCurrency,
                $requireFreshRates
            );
            $walletCredit = round(max(0, $collateralReleased + $realized), 2);

            $walletTx = $wallet->transactions()->create([
                'payment_method_id' => $method->id,
                'type' => 'investment',
                'direction' => 'credit',
                'amount' => $walletCredit,
                'fee' => 0,
                'status' => 'completed',
                'reference_id' => $reference,
                'description' => 'Forex settlement: '.$instrument->display_symbol,
            ]);

            $execution = $this->ledger->record([
                'user_id' => $user->id,
                'market_instrument_id' => $instrument->id,
                'wallet_transaction_id' => $walletTx->id,
                'trade_position_id' => $positionId,
                'native_type' => 'forex_cash_collateral_execution',
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
                    $this->executionMetadata($instrument, $quote, $context, $requestedQuantity, $quantityMode, $sourceId),
                    [
                        'collateral_released' => round($collateralReleased, 8),
                        'profit_loss_quote_currency' => round($profitLossQuote, 8),
                        'realized_profit_loss' => round($realized, 8),
                    ]
                ),
            ]);

            $remaining = max(0, $oldQty - $units);
            $remainingInvested = max(0, $oldInvested - $collateralReleased);

            if ($remaining > 0) {
                $remainingPnlQuote = ($price - $averageEntry) * $remaining;
                $remainingPnl = $this->settlement->profitLossToSettlement(
                    $instrument,
                    $remainingPnlQuote,
                    $price,
                    $settlementCurrency,
                    $requireFreshRates
                );
                $remainingValue = max(0, $remainingInvested + $remainingPnl);

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
                'Forex position reduced',
                'Closed '.number_format($units, 2).' base units on '.$instrument->display_symbol.'.',
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
                    'asset_class' => 'forex',
                    'quantity_units' => $units,
                    'quantity_mode' => $quantityMode,
                    'execution_price' => $price,
                    'marketplace' => $marketplace,
                    'market_execution_transaction_id' => $execution->id,
                    'collateral_released' => round($collateralReleased, 8),
                    'realized_profit_loss' => round($realized, 8),
                ],
                $actorType,
                $actorId
            );

            return $execution;
        });
    }

    private function normalizeQuantity(float $quantity, array $context): array
    {
        $mode = strtolower(trim((string) ($context['quantity_mode'] ?? 'units')));
        if (! in_array($mode, ['units', 'lots'], true)) {
            throw new InvalidArgumentException('Forex quantity mode must be units or lots.');
        }

        if ($mode === 'lots') {
            if ($quantity < 0.01) {
                throw new InvalidArgumentException('Forex lot quantity must be at least 0.01 lots.');
            }
            return [round($quantity * self::STANDARD_LOT_UNITS, 8), 'lots', $quantity];
        }

        if ($quantity < 1) {
            throw new InvalidArgumentException('Forex unit quantity must be at least 1 base-currency unit.');
        }

        return [round($quantity, 8), 'units', $quantity];
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

    private function assertIdempotentMatch(
        MarketExecutionTransaction $existing,
        MarketInstrument $instrument,
        string $side,
        float $units,
        string $marketplace
    ): void {
        $matches = (int) $existing->market_instrument_id === (int) $instrument->id
            && $existing->side === $side
            && $existing->marketplace === $marketplace
            && abs((float) $existing->quantity - $units) < 0.00000001;

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
            'asset_class' => 'forex',
            'execution_model' => 'cash_collateralized_long_no_leverage',
            'base_asset' => $instrument->base_asset,
            'quote_asset' => $instrument->quote_asset,
            'requested_quantity' => $requestedQuantity,
            'quantity_mode' => $quantityMode,
            'standard_lot_units' => self::STANDARD_LOT_UNITS,
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
            'asset_class' => 'forex',
            'execution_model' => 'cash_collateralized_long_no_leverage',
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
