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
 * BrokerOrder remains the order authority. Optional execution context lets
 * trusted internal callers preserve strategy attribution without bypassing
 * the broker or asset execution adapters.
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
        BrokerOrder $order,
        array $context = []
    ): MarketExecutionTransaction {
        $marketplace = $this->prices->normalizeMarketplace($order->marketplace ?: $this->prices->activeMarketplace());
        $ctx = $this->context($user, $order, $context);
        $copyStrategyId = $this->copyStrategyId($ctx);

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
                $ctx['execution_source'],
                $ctx['context_type'],
                $risk,
                $ctx['context_id'],
                $ctx['execution_source_id'],
                $copyStrategyId,
                $ctx['actor_type'],
                $ctx['actor_id'],
                $ctx['source_position_id'],
                $marketplace
            );

            return $this->ledger->forStockTransaction($native)->refresh();
        }

        return $this->markets->openLong(
            $user,
            $instrument,
            $quantity,
            $ctx['execution_source'],
            $ctx['context_type'],
            $risk,
            $ctx['context_id'],
            $ctx['execution_source_id'],
            $ctx['actor_type'],
            $ctx['actor_id'],
            $marketplace,
            $quantityMode,
            $order->idempotency_key,
            $ctx['source_position_id']
        );
    }

    public function closePosition(
        User $user,
        TradePosition $position,
        float $quantity,
        BrokerOrder $order,
        array $context = []
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

        $ctx = $this->context($user, $order, $context);

        if ($instrument->isStock()) {
            $native = $this->stocks->closePosition(
                $position,
                $ctx['exit_reason'],
                $quantity,
                $ctx['actor_type'],
                $ctx['actor_id'],
                false,
                null,
                $ctx['execution_source']
            );

            return $this->ledger->forStockTransaction($native)->refresh();
        }

        return $this->markets->closePosition(
            $position,
            $ctx['exit_reason'],
            $quantity,
            $ctx['actor_type'],
            $ctx['actor_id'],
            $order->idempotency_key,
            $ctx['execution_source']
        );
    }

    public function sell(
        User $user,
        MarketInstrument $instrument,
        float $quantity,
        string $quantityMode,
        BrokerOrder $order,
        array $context = []
    ): MarketExecutionTransaction {
        $marketplace = $this->prices->normalizeMarketplace($order->marketplace ?: $this->prices->activeMarketplace());
        $ctx = $this->context($user, $order, $context);
        $copyStrategyId = $this->copyStrategyId($ctx);

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
                $ctx['execution_source'],
                $ctx['exit_reason'],
                $ctx['context_type'],
                $ctx['context_id'],
                $ctx['execution_source_id'],
                $copyStrategyId,
                $ctx['actor_type'],
                $ctx['actor_id'],
                $marketplace
            );

            return $this->ledger->forStockTransaction($native)->refresh();
        }

        return $this->markets->sellExposure(
            $user,
            $instrument,
            $quantity,
            $ctx['execution_source'],
            $ctx['exit_reason'],
            $ctx['context_type'],
            $ctx['context_id'],
            $ctx['execution_source_id'],
            $ctx['actor_type'],
            $ctx['actor_id'],
            $marketplace,
            $quantityMode,
            $order->idempotency_key
        );
    }

    private function context(User $user, BrokerOrder $order, array $context): array
    {
        $executionSource = trim((string) ($context['execution_source'] ?? $order->execution_source ?? 'broker_order'));
        if ($executionSource === '') {
            $executionSource = 'broker_order';
        }

        $contextType = trim((string) ($context['context_type'] ?? 'broker_order'));
        if ($contextType === '') {
            $contextType = 'broker_order';
        }

        $contextId = array_key_exists('context_id', $context) && $context['context_id'] !== null
            ? (int) $context['context_id']
            : ($contextType === 'broker_order' ? (int) $order->id : null);

        $executionSourceId = array_key_exists('execution_source_id', $context) && $context['execution_source_id'] !== null
            ? (int) $context['execution_source_id']
            : ($executionSource === 'broker_order' ? (int) $order->id : $contextId);

        $actorType = trim((string) ($context['actor_type'] ?? 'user'));
        if ($actorType === '') {
            $actorType = 'user';
        }

        $actorId = array_key_exists('actor_id', $context)
            ? ($context['actor_id'] === null ? null : (int) $context['actor_id'])
            : (int) $user->id;

        $sourcePositionId = array_key_exists('source_position_id', $context) && $context['source_position_id'] !== null
            ? (int) $context['source_position_id']
            : null;

        return [
            'execution_source' => $executionSource,
            'execution_source_id' => $executionSourceId,
            'context_type' => $contextType,
            'context_id' => $contextId,
            'source_position_id' => $sourcePositionId,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'exit_reason' => trim((string) ($context['exit_reason'] ?? ($executionSource === 'broker_order' ? 'broker_order_close' : $executionSource.'_exit'))),
        ];
    }

    private function copyStrategyId(array $context): ?int
    {
        return $context['context_type'] === 'copy_strategy' && (int) ($context['context_id'] ?? 0) > 0
            ? (int) $context['context_id']
            : null;
    }
}
