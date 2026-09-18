<?php

namespace App\Services;

use Carbon\CarbonImmutable;

class SignalSetupBuilder
{
    public function build(array $context, array $analysis, array $qualification): ?array
    {
        $direction = (string) ($analysis['direction'] ?? 'neutral');
        if (! in_array($direction, ['buy', 'sell'], true)) {
            return null;
        }

        $price = (float) ($analysis['market_price'] ?? $context['current_price'] ?? 0);
        if ($price <= 0) {
            return null;
        }

        $atr = max((float) ($analysis['atr'] ?? 0), $price * 0.0025, 0.000001);
        $precision = $this->precision($price);
        $entryHalfWidth = max($atr * 0.15, $price * 0.0005);
        $baseRisk = max($atr * 1.2, $price * 0.0075);

        if ($direction === 'buy') {
            $entryMin = $price - $entryHalfWidth;
            $entryMax = $price + ($entryHalfWidth * 0.35);
            $stop = $price - $baseRisk;

            $support = (float) ($context['support'] ?? 0);
            if ($support > 0 && $support < $price && ($price - $support) <= ($atr * 3)) {
                $stop = min($stop, $support - ($atr * 0.15));
            }
        } else {
            $entryMin = $price - ($entryHalfWidth * 0.35);
            $entryMax = $price + $entryHalfWidth;
            $stop = $price + $baseRisk;

            $resistance = (float) ($context['resistance'] ?? 0);
            if ($resistance > $price && ($resistance - $price) <= ($atr * 3)) {
                $stop = max($stop, $resistance + ($atr * 0.15));
            }
        }

        $entryAnchor = ($entryMin + $entryMax) / 2;
        $risk = abs($entryAnchor - $stop);
        if ($risk <= 0) {
            return null;
        }

        $multiples = [1.5, 2.0, 3.0];
        $targets = [];
        foreach ($multiples as $index => $multiple) {
            $target = $direction === 'buy'
                ? $entryAnchor + ($risk * $multiple)
                : $entryAnchor - ($risk * $multiple);

            $targets[] = [
                'sequence' => $index + 1,
                'price' => round(max(0.00000001, $target), $precision),
                'r_multiple' => $multiple,
            ];
        }

        $timeframe = (string) ($analysis['timeframe'] ?? $context['default_timeframe'] ?? '15m');

        return [
            'direction' => $direction,
            'timeframe' => $timeframe,
            'entry_min' => round(max(0.00000001, $entryMin), $precision),
            'entry_max' => round(max(0.00000001, $entryMax), $precision),
            'stop_loss' => round(max(0.00000001, $stop), $precision),
            'targets' => $targets,
            'risk_reward' => 2.0,
            'expires_at' => $this->expiryFor($timeframe)->toIso8601String(),
            'strength' => $qualification['strength'] ?? 'watch',
            'confluence_score' => (float) ($analysis['confluence_score'] ?? 0),
            'signal_ready' => (bool) ($qualification['eligible_for_signal'] ?? false),
            'auto_generation_ready' => (bool) ($qualification['eligible_for_auto_generation'] ?? false),
            'rationale' => $analysis['rationale'] ?? [],
        ];
    }

    private function expiryFor(string $timeframe): CarbonImmutable
    {
        $now = CarbonImmutable::now();

        return match (strtolower($timeframe)) {
            '5m' => $now->addHours(2),
            '15m' => $now->addHours(6),
            '1h' => $now->addDay(),
            '4h' => $now->addDays(3),
            '1d' => $now->addDays(10),
            '1w' => $now->addDays(30),
            default => $now->addHours(12),
        };
    }

    private function precision(float $price): int
    {
        if ($price >= 100) return 2;
        if ($price >= 1) return 4;
        if ($price >= 0.01) return 6;
        return 8;
    }
}
