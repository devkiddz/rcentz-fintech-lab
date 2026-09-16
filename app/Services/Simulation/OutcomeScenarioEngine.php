<?php

namespace App\Services\Simulation;

use InvalidArgumentException;

/**
 * Pure scenario calculator for demo/testing outcomes.
 *
 * This engine does not touch the database or any financial ledger. It can be
 * used to model WIN / LOSS / FLAT outcomes independently from the market
 * contract engine.
 */
final class OutcomeScenarioEngine
{
    public const OUTCOME_WIN = 'win';
    public const OUTCOME_LOSS = 'loss';
    public const OUTCOME_FLAT = 'flat';

    public const DIRECTION_LONG = 'long';
    public const DIRECTION_SHORT = 'short';

    public function resolve(
        float $entryPrice,
        float $quantity,
        string $outcome,
        float $movePercent = 1.0,
        string $direction = self::DIRECTION_LONG
    ): array {
        if ($entryPrice <= 0) {
            throw new InvalidArgumentException('Entry price must be greater than zero.');
        }

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }

        if (! in_array($outcome, [self::OUTCOME_WIN, self::OUTCOME_LOSS, self::OUTCOME_FLAT], true)) {
            throw new InvalidArgumentException('Outcome must be win, loss or flat.');
        }

        if (! in_array($direction, [self::DIRECTION_LONG, self::DIRECTION_SHORT], true)) {
            throw new InvalidArgumentException('Direction must be long or short.');
        }

        if ($movePercent < 0 || $movePercent > 100) {
            throw new InvalidArgumentException('Move percent must be between 0 and 100.');
        }

        $move = $movePercent / 100;
        $winningMultiplier = $direction === self::DIRECTION_LONG ? 1 + $move : 1 - $move;
        $losingMultiplier = $direction === self::DIRECTION_LONG ? 1 - $move : 1 + $move;

        $exitPrice = match ($outcome) {
            self::OUTCOME_WIN => $entryPrice * $winningMultiplier,
            self::OUTCOME_LOSS => $entryPrice * $losingMultiplier,
            self::OUTCOME_FLAT => $entryPrice,
        };

        $exitPrice = max(0, round($exitPrice, 8));
        $profitLoss = $direction === self::DIRECTION_LONG
            ? ($exitPrice - $entryPrice) * $quantity
            : ($entryPrice - $exitPrice) * $quantity;

        $basis = $entryPrice * $quantity;

        return [
            'engine' => 'scenario_outcome',
            'outcome' => $outcome,
            'direction' => $direction,
            'entry_price' => round($entryPrice, 8),
            'exit_price' => $exitPrice,
            'quantity' => $quantity,
            'move_percent' => $outcome === self::OUTCOME_FLAT ? 0.0 : $movePercent,
            'profit_loss' => round($profitLoss, 8),
            'return_percent' => $basis > 0 ? round(($profitLoss / $basis) * 100, 6) : 0.0,
            'mutates_financial_ledger' => false,
        ];
    }
}
