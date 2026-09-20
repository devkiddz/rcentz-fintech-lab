<?php

namespace App\Services;

use App\Models\CopyRelationship;
use App\Models\CopyStrategy;
use App\Models\CopyTradeExecution;
use App\Models\MarketExecutionTransaction;
use App\Models\MarketInstrument;
use App\Models\TradePosition;
use RuntimeException;

final class CopyTradingSurfaceService
{
    public function __construct(
        private MarketExecutionLedgerService $ledger,
        private MarketPriceRouter $prices,
        private MarketSettlementService $settlement,
        private MarketSessionService $stockSessions,
        private ForexSessionService $forexSessions
    ) {}

    public function decorateStrategy(CopyStrategy $strategy): void
    {
        $execution = CopyTradeExecution::query()
            ->with($this->executionRelations())
            ->whereHas('relationship', fn ($q) => $q->where('copy_strategy_id', $strategy->id))
            ->where('status', 'completed')
            ->latest('executed_at')
            ->latest('id')
            ->first();

        $this->decorate($strategy, $execution);
    }

    public function decorateRelationship(CopyRelationship $relationship): void
    {
        $execution = $relationship->executions()
            ->with($this->executionRelations())
            ->where('status', 'completed')
            ->latest('executed_at')
            ->latest('id')
            ->first();

        $this->decorate($relationship, $execution);
    }

    public function detail(CopyTradeExecution $execution): array
    {
        $execution->loadMissing(array_merge([
            'relationship.strategy.profile.user',
            'relationship.provider',
            'relationship.follower.wallet',
        ], $this->executionRelations()));

        $instrument = $this->instrument($execution);
        $followerExecution = $this->followerExecution($execution);
        $position = $followerExecution?->tradePosition;
        $marketplace = (string) ($followerExecution?->marketplace
            ?? $execution->followerTrade?->marketplace
            ?? $execution->providerMarketExecution?->marketplace
            ?? 'live');

        $currentPrice = $instrument ? ($this->safePrice($instrument, $marketplace) ?? 0.0) : 0.0;
        $entryPrice = (float) ($followerExecution?->price ?? $execution->followerTrade?->price_per_share ?? 0);
        $quantity = (float) ($followerExecution?->quantity ?? $execution->followerTrade?->quantity ?? 0);
        $side = (string) ($followerExecution?->side ?? $execution->followerTrade?->type ?? $execution->action ?? 'trade');
        $profitLoss = $this->profitLoss($execution, $instrument, $followerExecution, $position, $currentPrice, $marketplace);
        $basis = $this->performanceBasis($execution, $followerExecution, $position);

        return [
            'instrument' => $instrument,
            'symbol' => $instrument?->display_symbol ?: $instrument?->symbol ?: '—',
            'assetClass' => strtoupper((string) ($instrument?->asset_class ?: 'asset')),
            'marketplace' => strtoupper($marketplace),
            'marketStatus' => $instrument ? $this->marketStatus($instrument, $marketplace) : 'unavailable',
            'currentPrice' => $currentPrice,
            'currentPriceDisplay' => $this->displayPrice($instrument, $currentPrice),
            'entryPrice' => $entryPrice,
            'entryPriceDisplay' => $this->displayPrice($instrument, $entryPrice),
            'quantity' => $quantity,
            'side' => $side,
            'profitLoss' => $profitLoss,
            'returnPercent' => $basis > 0 ? ($profitLoss / $basis) * 100 : 0.0,
            'position' => $position,
            'followerExecution' => $followerExecution,
        ];
    }

    public function displayPrice(?MarketInstrument $instrument, float $price): string
    {
        if (! $instrument || $price <= 0) {
            return '—';
        }

        if ($instrument->isStock()) {
            return format_currency($price);
        }

        $precision = min(10, max(2, (int) ($instrument->price_precision ?? 5)));
        $quote = strtoupper((string) ($instrument->quote_asset ?: ''));
        $formatted = number_format($price, $precision, '.', ',');

        return $quote !== '' ? $formatted.' '.$quote : $formatted;
    }

    private function decorate(object $target, ?CopyTradeExecution $execution): void
    {
        $instrument = $execution ? $this->instrument($execution) : null;
        $marketplace = (string) ($execution?->followerMarketExecution?->marketplace
            ?? $execution?->followerTrade?->marketplace
            ?? $execution?->providerMarketExecution?->marketplace
            ?? 'live');
        $price = $instrument ? ($this->safePrice($instrument, $marketplace) ?? 0.0) : 0.0;

        $target->setAttribute('market_symbol', $instrument?->display_symbol ?: $instrument?->symbol);
        $target->setAttribute('market_asset_class', $instrument?->asset_class);
        $target->setAttribute('market_price_display', $this->displayPrice($instrument, $price));
        $target->setAttribute('market_status', $instrument ? $this->marketStatus($instrument, $marketplace) : 'unavailable');
        $target->setAttribute('market_marketplace', strtoupper($marketplace));
    }

    private function instrument(CopyTradeExecution $execution): ?MarketInstrument
    {
        $execution->loadMissing($this->executionRelations());

        return $execution->marketInstrument
            ?? $execution->followerMarketExecution?->marketInstrument
            ?? $execution->providerMarketExecution?->marketInstrument
            ?? $execution->followerTrade?->marketInstrument
            ?? $execution->providerTrade?->marketInstrument
            ?? $execution->followerTrade?->stock?->marketInstrument
            ?? $execution->providerTrade?->stock?->marketInstrument;
    }

    private function followerExecution(CopyTradeExecution $execution): ?MarketExecutionTransaction
    {
        if ($execution->followerMarketExecution) {
            return $execution->followerMarketExecution;
        }

        if ($execution->followerTrade) {
            try {
                return $this->ledger->forStockTransaction($execution->followerTrade)->refresh();
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function profitLoss(
        CopyTradeExecution $copy,
        ?MarketInstrument $instrument,
        ?MarketExecutionTransaction $execution,
        ?TradePosition $position,
        float $currentPrice,
        string $marketplace
    ): float {
        if ($copy->status !== 'completed' || ! $instrument || ! $execution) {
            return 0.0;
        }

        if ($position) {
            $realized = (float) $position->realized_profit_loss;
            $openQty = (float) $position->open_quantity;
            if ($openQty <= 0 || $currentPrice <= 0) {
                return $realized;
            }

            $quotePnl = ($currentPrice - (float) $position->entry_price) * $openQty;
            if ($instrument->isStock()) {
                return $realized + $quotePnl;
            }

            try {
                $currency = strtoupper((string) ($copy->relationship?->follower?->wallet?->currency ?: 'USD'));
                return $realized + $this->settlement->profitLossToSettlement(
                    $instrument,
                    $quotePnl,
                    $currentPrice,
                    $currency,
                    false
                );
            } catch (\Throwable) {
                return $realized;
            }
        }

        if ($execution->side === 'sell' && $execution->realized_profit_loss !== null) {
            return (float) $execution->realized_profit_loss;
        }

        if ($currentPrice <= 0 || (float) $execution->price <= 0 || (float) $execution->quantity <= 0) {
            return 0.0;
        }

        $quotePnl = ($currentPrice - (float) $execution->price) * (float) $execution->quantity;
        if ($instrument->isStock()) {
            return $execution->side === 'sell' ? -$quotePnl : $quotePnl;
        }

        try {
            $currency = strtoupper((string) ($copy->relationship?->follower?->wallet?->currency ?: 'USD'));
            $converted = $this->settlement->profitLossToSettlement($instrument, $quotePnl, $currentPrice, $currency, false);
            return $execution->side === 'sell' ? -$converted : $converted;
        } catch (\Throwable) {
            return 0.0;
        }
    }

    private function performanceBasis(
        CopyTradeExecution $copy,
        ?MarketExecutionTransaction $execution,
        ?TradePosition $position
    ): float {
        if ($position?->entryMarketExecutionTransaction) {
            return (float) ($position->entryMarketExecutionTransaction->settlement_amount
                ?? $position->entryMarketExecutionTransaction->gross_value
                ?? 0);
        }

        if ($execution) {
            return (float) ($execution->settlement_amount ?? $execution->gross_value ?? 0);
        }

        return (float) $copy->executed_amount;
    }

    private function marketStatus(MarketInstrument $instrument, string $marketplace): string
    {
        if ($marketplace === 'controlled') {
            return 'controlled';
        }

        if ($instrument->isCrypto()) {
            return '24_7';
        }

        if ($instrument->isForex()) {
            return $this->forexSessions->isMarketOpen() ? 'open' : 'closed';
        }

        return $this->stockSessions->status();
    }

    private function safePrice(MarketInstrument $instrument, string $marketplace): ?float
    {
        try {
            $price = (float) $this->prices->price($instrument, $marketplace);
            return $price > 0 ? $price : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function executionRelations(): array
    {
        return [
            'marketInstrument',
            'providerTrade.stock.marketInstrument',
            'providerTrade.marketInstrument',
            'followerTrade.stock.marketInstrument',
            'followerTrade.marketInstrument',
            'providerMarketExecution.marketInstrument',
            'providerMarketExecution.tradePosition',
            'followerMarketExecution.marketInstrument',
            'followerMarketExecution.tradePosition.entryMarketExecutionTransaction',
        ];
    }
}
