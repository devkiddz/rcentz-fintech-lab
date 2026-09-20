<?php

namespace App\Services;

use App\Models\MarketExecutionTransaction;
use App\Models\MarketInstrument;
use App\Models\TradePosition;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class MarketInstrumentTradeContractEngine
{
    public function __construct(
        private MarketExecutionRouter $execution,
        private MarketPositionService $positions,
        private MarketPriceRouter $prices
    ) {}

    public function openLong(
        User $user,
        MarketInstrument $instrument,
        float $quantity,
        string $executionSource = 'market_trade',
        string $contextType = 'market_trade',
        array $risk = [],
        ?int $contextId = null,
        ?int $executionSourceId = null,
        string $actorType = 'system',
        ?int $actorId = null,
        ?string $marketplace = null,
        string $quantityMode = 'units',
        ?string $idempotencyKey = null,
        ?int $sourcePositionId = null
    ): MarketExecutionTransaction {
        $marketplace = $this->prices->normalizeMarketplace($marketplace ?: $this->prices->activeMarketplace());

        return DB::transaction(function () use (
            $user,
            $instrument,
            $quantity,
            $executionSource,
            $contextType,
            $risk,
            $contextId,
            $executionSourceId,
            $actorType,
            $actorId,
            $marketplace,
            $quantityMode,
            $idempotencyKey,
            $sourcePositionId
        ) {
            $execution = $this->execution->execute($user, $instrument, 'buy', $quantity, [
                'source' => $executionSource,
                'source_id' => $executionSourceId,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'marketplace' => $marketplace,
                'quantity_mode' => $quantityMode,
                'idempotency_key' => $idempotencyKey,
                'context_type' => $contextType,
                'context_id' => $contextId,
            ]);

            if (! $execution->trade_position_id) {
                $this->positions->openLongFromExecution(
                    $execution,
                    $risk,
                    $contextType,
                    $contextId,
                    $sourcePositionId,
                    $actorType,
                    $actorId
                );
            }

            return $execution->refresh();
        });
    }

    public function sellExposure(
        User $user,
        MarketInstrument $instrument,
        float $quantity,
        string $executionSource = 'market_trade',
        string $exitReason = 'manual_close',
        ?string $contextType = null,
        ?int $contextId = null,
        ?int $executionSourceId = null,
        string $actorType = 'system',
        ?int $actorId = null,
        ?string $marketplace = null,
        string $quantityMode = 'units',
        ?string $idempotencyKey = null
    ): MarketExecutionTransaction {
        $marketplace = $this->prices->normalizeMarketplace($marketplace ?: $this->prices->activeMarketplace());

        return DB::transaction(function () use (
            $user,
            $instrument,
            $quantity,
            $executionSource,
            $exitReason,
            $contextType,
            $contextId,
            $executionSourceId,
            $actorType,
            $actorId,
            $marketplace,
            $quantityMode,
            $idempotencyKey
        ) {
            $execution = $this->execution->execute($user, $instrument, 'sell', $quantity, [
                'source' => $executionSource,
                'source_id' => $executionSourceId,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'marketplace' => $marketplace,
                'quantity_mode' => $quantityMode,
                'idempotency_key' => $idempotencyKey,
                'context_type' => $contextType,
                'context_id' => $contextId,
            ]);

            $this->positions->consumeSellExecution(
                $execution,
                $exitReason,
                $contextType,
                $contextId,
                $actorType,
                $actorId
            );

            return $execution->refresh();
        });
    }

    public function closePosition(
        TradePosition $position,
        string $reason = 'manual_close',
        ?float $quantity = null,
        string $actorType = 'system',
        ?int $actorId = null,
        ?string $idempotencyKey = null,
        string $executionSource = 'position_exit'
    ): MarketExecutionTransaction {
        return DB::transaction(fn () => $this->positions->close(
            $position,
            $reason,
            $quantity,
            $actorType,
            $actorId,
            $executionSource,
            $idempotencyKey
        ));
    }
}
