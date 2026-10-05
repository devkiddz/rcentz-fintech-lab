<?php

namespace App\Services;

use App\Models\BrokerOrder;
use App\Models\TradePosition;
use App\Models\User;
use RuntimeException;

final class BrokerPositionService
{
    public function __construct(
        private TradePositionService $stockPositions,
        private MarketPositionService $marketPositions,
        private BrokerOrderService $orders
    ) {}

    public function updateRisk(
        User $user,
        TradePosition $position,
        ?float $stopLossPercent,
        ?float $takeProfitPercent,
        ?int $durationMinutes,
        ?float $stopLossPrice = null,
        ?float $takeProfitPrice = null
    ): TradePosition {
        $this->assertOwned($user, $position);

        if (\App\Services\PaperTrading\PaperBrokerService::owns($position)) {
            return app(\App\Services\PaperTrading\PaperBrokerService::class)->updateRisk(
                $user, $position, $stopLossPercent, $takeProfitPercent, $durationMinutes, $stopLossPrice, $takeProfitPrice
            );
        }

        if ($stopLossPrice !== null || $takeProfitPrice !== null) { throw new \InvalidArgumentException('Exact prices are supported on the new position engine only.'); }

        if ($position->stock_id !== null) {
            return $this->stockPositions->updateRisk(
                $position,
                $stopLossPercent,
                $takeProfitPercent,
                $durationMinutes,
                'user',
                $user->id
            );
        }

        return $this->marketPositions->updateRisk(
            $position,
            $stopLossPercent,
            $takeProfitPercent,
            $durationMinutes,
            'user',
            $user->id
        );
    }

    public function close(
        User $user,
        TradePosition $position,
        ?float $quantity,
        string $idempotencyKey,
        array $context = []
    ): BrokerOrder {
        $this->assertOwned($user, $position);
        return $this->orders->placePositionClose($user, $position, $quantity, $idempotencyKey, $context);
    }

    private function assertOwned(User $user, TradePosition $position): void
    {
        if ((int) $position->user_id !== (int) $user->id) {
            throw new RuntimeException('Position ownership validation failed.');
        }
    }
}
