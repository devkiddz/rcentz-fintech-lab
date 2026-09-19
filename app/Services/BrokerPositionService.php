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
        ?int $durationMinutes
    ): TradePosition {
        $this->assertOwned($user, $position);

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
        string $idempotencyKey
    ): BrokerOrder {
        $this->assertOwned($user, $position);
        return $this->orders->placePositionClose($user, $position, $quantity, $idempotencyKey);
    }

    private function assertOwned(User $user, TradePosition $position): void
    {
        if ((int) $position->user_id !== (int) $user->id) {
            throw new RuntimeException('Position ownership validation failed.');
        }
    }
}
