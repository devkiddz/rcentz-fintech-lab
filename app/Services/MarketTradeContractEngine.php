<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockTransaction;
use App\Models\TradePosition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * V5.10 canonical market-contract wiring.
 *
 * This is the orchestration boundary for NEW real trades. It deliberately
 * composes the financial executor and the living TradePosition engine inside
 * one outer database transaction, so a ledger mutation cannot survive without
 * its required contract mutation (and vice versa).
 *
 * Price policy remains owned by StockTradeExecutor. V5.10 does NOT introduce
 * the future live/manual-price contract switcher.
 */
final class MarketTradeContractEngine
{
    public function __construct(
        private StockTradeExecutor $executor,
        private TradePositionService $positions
    ) {}

    public function openLong(
        User $user,
        Stock $stock,
        float $quantity,
        string $executionSource,
        string $contextType,
        array $risk = [],
        ?int $contextId = null,
        ?int $executionSourceId = null,
        ?int $copyStrategyId = null,
        string $actorType = 'system',
        ?int $actorId = null,
        ?int $sourcePositionId = null
    ): StockTransaction {
        return DB::transaction(function () use (
            $user,
            $stock,
            $quantity,
            $executionSource,
            $contextType,
            $risk,
            $contextId,
            $executionSourceId,
            $copyStrategyId,
            $actorType,
            $actorId,
            $sourcePositionId
        ) {
            $trade = $this->executor->buy(
                $user,
                $stock,
                $quantity,
                $executionSource,
                $executionSourceId,
                $copyStrategyId,
                $actorType,
                $actorId
            );

            $position = $this->positions->openLongFromTrade(
                $trade,
                $risk,
                $contextType,
                $contextId,
                $sourcePositionId,
                $actorType,
                $actorId
            );

            $trade->refresh();

            if ((int) $trade->trade_position_id !== (int) $position->id) {
                throw new RuntimeException('Trade contract linkage failed; execution was rolled back.');
            }

            return $trade;
        });
    }

    /**
     * Raw user-facing SELL plus position reconciliation, atomically.
     *
     * Null context filters intentionally preserve the existing FIFO exposure
     * reconciliation behavior for legacy holdings while preventing a completed
     * financial sell from surviving a reconciliation exception.
     */
    public function sellExposure(
        User $user,
        Stock $stock,
        float $quantity,
        string $executionSource,
        string $exitReason = 'manual_close',
        ?string $contextType = null,
        ?int $contextId = null,
        ?int $executionSourceId = null,
        ?int $copyStrategyId = null,
        string $actorType = 'system',
        ?int $actorId = null
    ): StockTransaction {
        return DB::transaction(function () use (
            $user,
            $stock,
            $quantity,
            $executionSource,
            $exitReason,
            $contextType,
            $contextId,
            $executionSourceId,
            $copyStrategyId,
            $actorType,
            $actorId
        ) {
            $trade = $this->executor->sell(
                $user,
                $stock,
                $quantity,
                $executionSource,
                $executionSourceId,
                $copyStrategyId,
                $actorType,
                $actorId
            );

            $this->positions->consumeSellTransaction(
                $trade,
                $exitReason,
                $contextType,
                $contextId,
                $actorType,
                $actorId
            );

            return $trade->refresh();
        });
    }

    /**
     * Close a known living contract through the canonical position engine.
     */
    public function closePosition(
        TradePosition $position,
        string $reason = 'manual_close',
        ?float $quantity = null,
        string $actorType = 'system',
        ?int $actorId = null,
        bool $allowClosedSessionSettlement = false,
        ?float $settlementPrice = null,
        string $executionSource = 'position_exit'
    ): StockTransaction {
        return DB::transaction(fn () => $this->positions->close(
            $position,
            $reason,
            $quantity,
            $actorType,
            $actorId,
            $allowClosedSessionSettlement,
            $settlementPrice,
            $executionSource
        ));
    }
}
