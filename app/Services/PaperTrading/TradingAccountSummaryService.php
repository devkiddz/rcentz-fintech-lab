<?php
declare(strict_types=1);

namespace App\Services\PaperTrading;

use App\Models\MarketHolding;
use App\Models\StockHolding;
use App\Models\TradePosition;
use App\Models\User;
use App\Services\MarketPriceRouter;
use App\Services\MarketSettlementService;

/** Shared-wallet liquid trading valuation across both marketplaces. */
final class TradingAccountSummaryService
{
    public function __construct(
        private MarketPriceRouter $prices,
        private MarketSettlementService $settlement,
        private PositionPresentationService $positions
    ) {}

    public function build(User $user): array
    {
        $wallet = $user->wallet()->firstOrFail();
        $currency = strtoupper((string) $wallet->currency);
        $balance = (float) $wallet->balance;
        $reserved = (float) $wallet->reserved_balance;
        $unrealized = 0.0;
        $collateral = 0.0;
        $legacyValue = 0.0;
        $complete = true;
        $open = TradePosition::query()->with(['marketInstrument', 'stock.marketInstrument', 'user.wallet'])
            ->where('user_id', $user->id)->whereIn('status', ['open', 'exit_queued'])
            ->where('open_quantity', '>', 0)->get();
        foreach ($open as $position) {
            if (!PaperBrokerService::owns($position)) { continue; }
            $collateral += (int) ($position->metadata['remaining_collateral_minor'] ?? 0) / 100;
            $row = $this->positions->describe($position);
            if ($row['unavailable'] || $row['settlement_currency'] !== $currency) {
                $complete = false;
            } else {
                $unrealized += $row['unrealized_profit_loss'];
            }
        }
        // Legacy purchases already deducted cash. Value owned assets once; legacy
        // positions merely describe those same holdings and must not be added again.
        foreach ([StockHolding::query()->with(['stock.marketInstrument','marketInstrument']),
            MarketHolding::query()->with('marketInstrument')] as $query) {
            foreach ($query->where('user_id', $user->id)->where('quantity', '>', 0)->get() as $holding) {
                try {
                    $instrument = $holding->marketInstrument ?? $holding->stock?->marketInstrument;
                    if (!$instrument) { throw new \RuntimeException('Missing instrument'); }
                    $mark = (float) $this->prices->price($instrument, $holding->marketplace ?: 'live');
                    if (!is_finite($mark) || $mark <= 0) { throw new \RuntimeException('Missing mark'); }
                    if ($holding instanceof StockHolding) {
                        $value = $this->settlement->convertFiat((float)$holding->quantity * $mark,
                            'USD', $currency, false);
                    } elseif ($instrument->isForex()) {
                        $ownCurrency = strtoupper((string)$holding->settlement_currency);
                        $pnl = $this->settlement->profitLossToSettlement($instrument,
                            ($mark-(float)$holding->average_entry_price)*(float)$holding->quantity,
                            $mark, $ownCurrency, false);
                        $value = $this->settlement->convertFiat(max(0, (float)$holding->total_invested+$pnl),
                            $ownCurrency, $currency, false);
                    } else {
                        $value = $this->settlement->amountForBaseUnits($instrument,
                            (float)$holding->quantity, $mark, $currency, false);
                    }
                    if (!is_finite($value)) { throw new \RuntimeException('Invalid value'); }
                    $legacyValue += $value;
                } catch (\Throwable) { $complete = false; }
            }
        }
        return [
            'currency'=>$currency, 'balance'=>round($balance,2),
            'available'=>round(max(0,$balance-$reserved),2), 'reserved'=>round($reserved,2),
            'position_collateral'=>round($collateral,2), 'open_positions'=>$open->count(),
            'unrealized_profit_loss'=>$complete ? round($unrealized,2) : null,
            'legacy_holding_value'=>$complete ? round($legacyValue,2) : null,
            // Collateral remains part of balance. Realized P/L is already settled.
            'equity'=>$complete ? round($balance+$unrealized+$legacyValue,2) : null,
            'valuation_complete'=>$complete,
        ];
    }
}
