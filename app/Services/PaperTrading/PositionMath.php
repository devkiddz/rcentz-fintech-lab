<?php
declare(strict_types=1);

namespace App\Services\PaperTrading;

use InvalidArgumentException;

/** Directional calculations only. No database, prices, wallet writes or order execution. */
final class PositionMath
{
    public static function directionForOpeningSide(string $side): string
    {
        return match ($side) {
            'buy' => 'long',
            'sell' => 'short',
            default => throw new InvalidArgumentException('Opening side must be buy or sell.'),
        };
    }

    public static function closingSide(string $direction): string
    {
        return self::sign($direction) === 1 ? 'sell' : 'buy';
    }

    /** Result is in instrument quote currency; settlement conversion belongs to the engine. */
    public static function quoteProfitLoss(string $direction, float $entry, float $exit, float $quantity): float
    {
        self::positive($entry, 'Entry price');
        self::positive($exit, 'Exit price');
        self::positive($quantity, 'Quantity');
        $result = self::sign($direction) * ($exit - $entry) * $quantity;
        if (!is_finite($result)) {
            throw new InvalidArgumentException('Profit/loss exceeds supported numeric range.');
        }
        return $result;
    }

    public static function riskLevels(string $direction, float $entry, ?float $stopPercent, ?float $takePercent): array
    {
        self::positive($entry, 'Entry price');
        $sign = self::sign($direction);
        foreach ([$stopPercent, $takePercent] as $percent) {
            if ($percent !== null && (!is_finite($percent) || $percent <= 0 || $percent >= 100)) {
                throw new InvalidArgumentException('Risk percentages must be greater than zero and below 100.');
            }
        }
        return [
            'stop_loss_price' => $stopPercent === null ? null : $entry * (1 - $sign * $stopPercent / 100),
            'take_profit_price' => $takePercent === null ? null : $entry * (1 + $sign * $takePercent / 100),
        ];
    }

    public static function exactRiskLevels(string $direction, float $entry, ?float $stop, ?float $take): array
    {
        self::positive($entry, 'Entry price');
        $sign = self::sign($direction);
        foreach ([$stop, $take] as $level) {
            if ($level !== null) {
                self::positive($level, 'Risk price');
                if ($level < 0.00000001 || $level > 1000000000000 || abs($level-round($level,8)) > 0.0000000001) {
                    throw new InvalidArgumentException('Risk prices support up to eight decimal places.');
                }
            }
        }
        if ($stop !== null && ($entry-$stop)*$sign <= 0) {
            throw new InvalidArgumentException('Stop loss must be below Long entry or above Short entry.');
        }
        if ($take !== null && ($take-$entry)*$sign <= 0) {
            throw new InvalidArgumentException('Take profit must be above Long entry or below Short entry.');
        }
        return ['stop_loss_price'=>$stop,'take_profit_price'=>$take];
    }

    public static function triggeredExit(string $direction, float $price, ?float $stop, ?float $take): ?string
    {
        self::positive($price, 'Current price');
        $sign = self::sign($direction);
        if ($stop !== null) {
            self::positive($stop, 'Stop price');
            if (self::atOrBelow(($price - $stop) * $sign, $price, $stop)) {
                return 'stop_loss';
            }
        }
        if ($take !== null) {
            self::positive($take, 'Take price');
            if (self::atOrBelow(($take - $price) * $sign, $price, $take)) {
                return 'take_profit';
            }
        }
        return null;
    }

    /** Split remaining collateral in integer minor units, preserving every cent on the final close. */
    public static function splitCollateral(int $remainingMinorUnits, float $openQuantity, float $closeQuantity): array
    {
        self::positive($openQuantity, 'Open quantity');
        self::positive($closeQuantity, 'Close quantity');
        if ($remainingMinorUnits < 0 || $closeQuantity > $openQuantity) {
            throw new InvalidArgumentException('Invalid remaining collateral or close quantity.');
        }
        $released = $closeQuantity === $openQuantity
            ? $remainingMinorUnits
            : (int) floor($remainingMinorUnits * ($closeQuantity / $openQuantity));
        return ['released' => $released, 'remaining' => $remainingMinorUnits - $released];
    }

    /** Allow only floating-point representation noise, not a market-price tick. */
    private static function atOrBelow(float $difference, float $price, float $threshold): bool
    {
        $tolerance = max(abs($price), abs($threshold)) * PHP_FLOAT_EPSILON * 4;
        return $difference <= $tolerance;
    }

    private static function sign(string $direction): int
    {
        return match ($direction) {
            'long' => 1,
            'short' => -1,
            default => throw new InvalidArgumentException('Position direction must be long or short.'),
        };
    }

    private static function positive(float $value, string $label): void
    {
        if (!is_finite($value) || $value <= 0) {
            throw new InvalidArgumentException($label.' must be finite and greater than zero.');
        }
    }
}
