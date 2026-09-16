<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockTransaction;
use App\Models\TradePosition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Canonical orchestration boundary for NEW financial trades.
 *
 * V5.11 keeps the trade rules shared while routing execution prices through
 * the selected marketplace authority (Live or Controlled).
 */
final class MarketTradeContractEngine
{
    public function __construct(
        private StockTradeExecutor $executor,
        private TradePositionService $positions,
        private MarketPriceRouter $prices
    ) {}

    public function activeMarketplace(): string
    {
        return $this->prices->activeMarketplace();
    }

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
        ?int $sourcePositionId = null,
        ?string $marketplace = null
    ): StockTransaction {
        $marketplace = $this->prices->normalizeMarketplace($marketplace ?: $this->prices->activeMarketplace());

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
            $sourcePositionId,
            $marketplace
        ) {
            $trade = $this->executor->buy(
                $user,
                $stock,
                $quantity,
                $executionSource,
                $executionSourceId,
                $copyStrategyId,
                $actorType,
                $actorId,
                null,
                $marketplace
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
        ?int $actorId = null,
        ?string $marketplace = null
    ): StockTransaction {
        $marketplace = $this->prices->normalizeMarketplace($marketplace ?: $this->prices->activeMarketplace());

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
            $actorId,
            $marketplace
        ) {
            $trade = $this->executor->sell(
                $user,
                $stock,
                $quantity,
                $executionSource,
                $executionSourceId,
                $copyStrategyId,
                $actorType,
                $actorId,
                null,
                false,
                null,
                $marketplace
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
     * Close a known living contract through its own marketplace authority.
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
