<?php

namespace App\Services\Simulation;

/**
 * Presentation-only performance engine.
 *
 * It may replace the P/L and return values shown on bot/strategy cards, but it
 * never creates a StockTransaction, mutates a Wallet, changes a Holding, or
 * settles a TradePosition. The calculated values remain available as
 * actual_profit_loss and actual_return_percent.
 */
final class ManualPerformanceEngine
{
    public function apply(
        array $metrics,
        bool $enabled,
        mixed $manualProfitLoss,
        mixed $manualReturnPercent,
        ?string $label,
        ?string $note
    ): array {
        $metrics['actual_profit_loss'] = $metrics['profit_loss'];
        $metrics['actual_return_percent'] = $metrics['return_percent'];
        $metrics['is_manual_performance'] = $enabled;
        $metrics['performance_label'] = $enabled ? trim((string) $label) : null;
        $metrics['performance_note'] = $enabled ? $note : null;
        $metrics['performance_engine'] = $enabled ? 'manual_presentation' : 'calculated';

        if (! $enabled) {
            return $metrics;
        }

        if ($manualProfitLoss !== null) {
            $metrics['profit_loss'] = (float) $manualProfitLoss;
        }

        if ($manualReturnPercent !== null) {
            $metrics['return_percent'] = (float) $manualReturnPercent;
        }

        return $metrics;
    }
}
