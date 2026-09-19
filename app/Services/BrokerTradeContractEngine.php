<?php

namespace App\Services;

use App\Models\BrokerOrder;
use App\Models\MarketExecutionTransaction;
use App\Models\MarketInstrument;
use App\Models\TradePosition;
use App\Models\User;
use RuntimeException;

/**
 * Cross-asset financial trade boundary used by the Broker Order authority.
 *
 * Stock retains its mature native ledger/position engine. Forex and Crypto use
 * the parent MarketInstrument execution/position engine. The result is always
 * normalized to the unified MarketExecutionTransaction receipt.
 */
final class BrokerTradeContractEngine
{
    public function __construct(
        private MarketTradeContractEngine $stocks,
        private MarketInstrumentTradeContractEngine $markets,
        private MarketExecutionLedgerService $ledger,
        private MarketPriceRouter $prices
    ) {}

    public function buy(
        User $user,
        MarketInstrument $instrument,
        float $quantity,
        string $quantityMode,
        array $risk,
        BrokerOrder $order
    ): MarketExecutionTransaction {
        $marketplace = $this->prices->normalizeMarketplace($order->marketplace ?: $this->prices->activeMarketplace());

        if ($instrument->isStock()) {
            if ($quantityMode !== 'units') {
                throw new RuntimeException('Stock orders use share quantity only.');
            }

            $stock = $instrument->canonicalStock()->first() ?? $instrument->stock;
            if (! $stock) {
                throw new RuntimeException('Canonical Stock execution child is unavailable.');
            }

            $native = $this->stocks->openLong(
                $user,
                $stock,
                $quantity,
                'broker_order',
                'broker_order',
                $risk,
                $order->id,
                $order->id,
                null,
                'user',
                $user->id,
                null,
                $marketplace
            );

            return $this->ledger->forStockTransaction($native)->refresh();
        }

        return $this->markets->openLong(
            $user,
            $instrument,
            $quantity,
            'broker_order',
            'broker_order',
            $risk,
            $order->id,
            $order->id,
            'user',
            $user->id,
            $marketplace,
            $quantityMode,
            $order->idempotency_key
        );
    }

    public function closePosition(
        User $user,
        TradePosition $position,
        float $quantity,
        BrokerOrder $order
    ): MarketExecutionTransaction {
        $position->loadMissing(['marketInstrument', 'stock.marketInstrument']);

        if ((int) $position->user_id !== (int) $user->id || ! $position->is_open) {
            throw new RuntimeException('This position is not available for customer close execution.');
        }

        $instrument = $position->marketInstrument ?? $position->stock?->marketInstrument;
        if (! $instrument || (int) $instrument->id !== (int) $order->market_instrument_id) {
            throw new RuntimeException('Position MarketInstrument authority is unavailable or mismatched.');
        }

        $quantity = min($quantity, (float) $position->open_quantity);
        if ($quantity <= 0) {
            throw new RuntimeException('Position close quantity must be greater than zero.');
        }

        if ($instrument->isStock()) {
            $native = $this->stocks->closePosition(
                $position,
                'broker_order_close',
                $quantity,
                'user',
                $user->id,
                false,
                null,
                'broker_order_position_close'
            );

            return $this->ledger->forStockTransaction($native)->refresh();
        }

        return $this->markets->closePosition(
            $position,
            'broker_order_close',
            $quantity,
            'user',
            $user->id,
            $order->idempotency_key
        );
    }

    public function sell(
        User $user,
        MarketInstrument $instrument,
        float $quantity,
        string $quantityMode,
        BrokerOrder $order
    ): MarketExecutionTransaction {
        $marketplace = $this->prices->normalizeMarketplace($order->marketplace ?: $this->prices->activeMarketplace());

        if ($instrument->isStock()) {
            if ($quantityMode !== 'units') {
                throw new RuntimeException('Stock orders use share quantity only.');
            }

            $stock = $instrument->canonicalStock()->first() ?? $instrument->stock;
            if (! $stock) {
                throw new RuntimeException('Canonical Stock execution child is unavailable.');
            }

            $native = $this->stocks->sellExposure(
                $user,
                $stock,
                $quantity,
                'broker_order',
                'broker_order_sell',
                null,
                null,
                $order->id,
                null,
                'user',
                $user->id,
                $marketplace
            );

            return $this->ledger->forStockTransaction($native)->refresh();
        }

        return $this->markets->sellExposure(
            $user,
            $instrument,
            $quantity,
            'broker_order',
            'broker_order_sell',
            null,
            null,
            $order->id,
            'user',
            $user->id,
            $marketplace,
            $quantityMode,
            $order->idempotency_key
        );
    }
}
