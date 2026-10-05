<?php
declare(strict_types=1);

namespace App\Services\PaperTrading;

use App\Models\TradePosition;
use App\Services\MarketPriceRouter;

/** Read-only valuation. Missing marks are unavailable, never a zero profit. */
final class PositionPresentationService
{
    public function __construct(private MarketPriceRouter $prices, private PaperBrokerService $broker) {}

    public function describe(TradePosition $position): array
    {
        $currency = strtoupper((string) ($position->metadata['settlement_currency']
            ?? $position->user?->wallet?->currency ?? 'USD'));
        $instrument = $position->marketInstrument ?? $position->stock?->marketInstrument;
        $direction = $position->direction ?: 'long';
        $row = [
            'marketplace'=>$position->marketplace ?: 'live', 'direction'=>$direction,
            'settlement_currency'=>$currency, 'label'=>$position->is_open ? 'Trade P/L' : 'Realized P/L',
            'cmp'=>null, 'difference'=>null, 'pnl'=>null, 'return_percent'=>null,
            'unrealized_profit_loss'=>null, 'unavailable'=>true,
            'formatted_floating_pnl'=>'—', 'formatted_cmp'=>'—', 'formatted_difference'=>'—', 'formatted_pnl'=>'—', 'formatted_return'=>'—',
        ];
        try {
            if ($position->is_open) {
                if (!$instrument) { return $row; }
                $mark = (float) $this->prices->price($instrument, $row['marketplace']);
                if (!is_finite($mark) || $mark <= 0) { return $row; }
                if (PaperBrokerService::owns($position)) {
                    $unrealized = $this->broker->unrealized($position);
                    $pnl = (float) $position->realized_profit_loss + $unrealized;
                    $basis = (int) ($position->metadata['initial_collateral_minor'] ?? 0) / 100;
                    $return = $basis > 0 ? $pnl / $basis * 100 : 0;
                } else {
                    $pnl = (float) $position->current_profit_loss;
                    $unrealized = $pnl - (float) $position->realized_profit_loss;
                    $return = (float) $position->current_return_percent;
                }
            } else {
                $mark = $position->average_exit_price !== null ? (float) $position->average_exit_price : null;
                $pnl = (float) $position->realized_profit_loss;
                $return = (float) $position->realized_return_percent;
                $unrealized = 0.0;
            }
            if (!is_finite($pnl) || !is_finite($return)) { return $row; }
            $difference = $mark !== null ? $mark - (float) $position->entry_price : null;
            $precision = max(0, min(8, (int) ($instrument?->price_precision ?? 2)));
            $quote = strtoupper((string) ($instrument?->quote_asset ?: 'USD'));
            return array_merge($row, [
                'cmp'=>$mark, 'difference'=>$difference, 'pnl'=>$pnl, 'return_percent'=>$return,
                'unrealized_profit_loss'=>$unrealized, 'unavailable'=>false,
                'formatted_cmp'=>$mark !== null ? $quote.' '.number_format($mark, $precision) : '—',
                'formatted_difference'=>$difference !== null ? ($difference >= 0 ? '+' : '-').$quote.' '.number_format(abs($difference), $precision) : '—',
                'formatted_floating_pnl'=>($unrealized >= 0 ? '+' : '-').$currency.' '.number_format(abs($unrealized), 2),
                'formatted_pnl'=>($pnl >= 0 ? '+' : '-').$currency.' '.number_format(abs($pnl), 2),
                'formatted_return'=>($return >= 0 ? '+' : '').number_format($return, 2).'%',
            ]);
        } catch (\Throwable) {
            return $row;
        }
    }
}
