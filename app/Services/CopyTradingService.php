<?php

namespace App\Services;

use App\Models\BrokerOrder;
use App\Models\CopyRelationship;
use App\Models\CopyStrategy;
use App\Models\CopyTradeExecution;
use App\Models\MarketExecutionTransaction;
use App\Models\MarketInstrument;
use App\Models\StockTransaction;
use App\Models\TradePosition;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CopyTradingService
{
    public function __construct(
        private BrokerOrderService $brokerOrders,
        private MarketExecutionLedgerService $ledger,
        private MarketPriceRouter $prices,
        private MarketSettlementService $settlement
    ) {}

    /**
     * Legacy Stock compatibility entry point. New copy mirroring is owned by the
     * unified MarketExecutionTransaction identity.
     */
    public function mirrorCompletedTrade(StockTransaction $providerTrade): void
    {
        if ($providerTrade->status !== 'completed') {
            return;
        }

        $execution = $this->ledger->forStockTransaction($providerTrade)->refresh();
        $strategyId = $providerTrade->copy_strategy_id
            ? (int) $providerTrade->copy_strategy_id
            : null;

        $this->mirrorCompletedExecution($execution, $strategyId);
    }

    public function mirrorCompletedExecution(
        MarketExecutionTransaction $providerExecution,
        ?int $strategyId = null
    ): void {
        if ($providerExecution->status !== 'completed') {
            return;
        }

        $providerExecution->loadMissing(['user', 'marketInstrument', 'tradePosition']);
        $instrument = $providerExecution->marketInstrument;

        if (! $instrument) {
            Log::warning('Copy mirroring skipped because provider execution has no MarketInstrument.', [
                'provider_market_execution_transaction_id' => $providerExecution->id,
            ]);
            return;
        }

        $strategyId = $strategyId ?: $this->strategyIdFromExecution($providerExecution);

        // Copyable provider trades must be explicitly attributed to one strategy.
        // Provider-wide fallback is intentionally removed because one provider may
        // own several independent strategies.
        if (! $strategyId) {
            Log::info('Provider execution is not strategy-attributed; copy mirroring skipped.', [
                'provider_market_execution_transaction_id' => $providerExecution->id,
                'provider_id' => $providerExecution->user_id,
            ]);
            return;
        }

        $strategy = CopyStrategy::query()->with('profile')->find($strategyId);
        if (! $strategy || (int) $strategy->profile?->user_id !== (int) $providerExecution->user_id) {
            Log::warning('Copy strategy attribution does not belong to provider execution owner.', [
                'provider_market_execution_transaction_id' => $providerExecution->id,
                'provider_id' => $providerExecution->user_id,
                'copy_strategy_id' => $strategyId,
            ]);
            return;
        }

        // New entries require an active strategy. Provider exits must still reach
        // existing followers even if the provider has since disabled the strategy.
        if ($providerExecution->side === 'buy' && ! $strategy->is_active) {
            Log::info('Inactive copy strategy rejected a new provider entry.', [
                'provider_market_execution_transaction_id' => $providerExecution->id,
                'copy_strategy_id' => $strategyId,
            ]);
            return;
        }

        $query = CopyRelationship::query()
            ->with(['follower.kyc', 'follower.wallet', 'strategy'])
            ->where('provider_id', $providerExecution->user_id)
            ->where('copy_strategy_id', $strategyId)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));

        foreach ($query->get() as $relationship) {
            $this->mirrorOne($relationship, $providerExecution, $instrument);
        }
    }

    private function mirrorOne(
        CopyRelationship $relationship,
        MarketExecutionTransaction $providerExecution,
        MarketInstrument $instrument
    ): void {
        $providerCapital = (float) ($providerExecution->settlement_amount ?? $providerExecution->gross_value ?? 0);
        $requested = min(
            $providerCapital * ((float) $relationship->copy_ratio_percent / 100),
            (float) $relationship->max_trade_amount
        );

        if ($providerExecution->side === 'buy') {
            $requested = min($requested, (float) $relationship->remaining_allocation);
        }

        if ($requested <= 0) {
            $this->execution($relationship, $providerExecution, null, null, $instrument, 0, 0, 'skipped', 'Allocation or trade cap reached.');
            return;
        }

        $follower = $relationship->follower;
        if (! $follower || ! $follower->kyc || ! $follower->kyc->isApproved()) {
            $this->execution($relationship, $providerExecution, null, null, $instrument, $requested, 0, 'skipped', 'Follower KYC is not approved.');
            return;
        }

        if (! $follower->wallet) {
            $this->execution($relationship, $providerExecution, null, null, $instrument, $requested, 0, 'skipped', 'Follower wallet is unavailable.');
            return;
        }

        $marketplace = $providerExecution->marketplace ?: 'live';
        if ($this->prices->activeMarketplace() !== $marketplace) {
            $this->execution(
                $relationship,
                $providerExecution,
                null,
                null,
                $instrument,
                $requested,
                0,
                'failed',
                'Provider marketplace no longer matches the active execution marketplace.'
            );
            return;
        }

        try {
            $providerPrice = (float) $providerExecution->price;
            if ($providerPrice <= 0) {
                throw new RuntimeException('Provider execution price is unavailable.');
            }

            $providerPosition = $providerExecution->tradePosition;
            $providerPositionId = $providerPosition?->id;

            $context = [
                'execution_source' => 'copy_trade',
                'execution_source_id' => $relationship->id,
                'context_type' => 'copy_relationship',
                'context_id' => $relationship->id,
                'source_position_id' => $providerPositionId,
                'actor_type' => 'system',
                'actor_id' => null,
                'exit_reason' => 'provider_exit',
                'metadata' => [
                    'copy_relationship_id' => $relationship->id,
                    'copy_strategy_id' => $relationship->copy_strategy_id,
                    'provider_market_execution_transaction_id' => $providerExecution->id,
                    'provider_position_id' => $providerPositionId,
                ],
            ];

            if ($providerExecution->side === 'buy') {
                $size = $this->sizeForCapital($follower, $instrument, $requested, $providerPrice, $marketplace, true);
                $risk = [
                    'stop_loss_percent' => $providerPosition?->stop_loss_percent,
                    'take_profit_percent' => $providerPosition?->take_profit_percent,
                    'duration_minutes' => $providerPosition?->duration_minutes,
                    'metadata' => [
                        'copy_relationship_id' => $relationship->id,
                        'copy_strategy_id' => $relationship->copy_strategy_id,
                        'provider_position_id' => $providerPositionId,
                    ],
                ];

                $order = $this->brokerOrders->placeMarketOrder(
                    $follower,
                    $instrument,
                    'buy',
                    $size['order_quantity'],
                    $size['quantity_mode'],
                    $this->idempotencyKey($relationship, $providerExecution),
                    $risk,
                    $context
                );
            } else {
                $position = $this->followerPosition(
                    $relationship,
                    $instrument,
                    $marketplace,
                    $providerPositionId
                );

                if (! $position) {
                    $this->execution(
                        $relationship,
                        $providerExecution,
                        null,
                        null,
                        $instrument,
                        $requested,
                        0,
                        'skipped',
                        'No relationship-attributed open position is available to mirror the provider exit.'
                    );
                    return;
                }

                $size = $this->sizeForCapital($follower, $instrument, $requested, $providerPrice, $marketplace, false);
                $qty = min((float) $position->open_quantity, (float) $size['display_quantity']);

                if ($qty <= 0) {
                    $this->execution($relationship, $providerExecution, null, null, $instrument, $requested, 0, 'skipped', 'Executable follower close quantity is zero.');
                    return;
                }

                $order = $this->brokerOrders->placePositionClose(
                    $follower,
                    $position,
                    $qty,
                    $this->idempotencyKey($relationship, $providerExecution),
                    $context
                );
            }

            $order->loadMissing('execution');
            $followerExecution = $order->execution;

            if ($order->status !== BrokerOrder::STATUS_FILLED || ! $followerExecution || $followerExecution->status !== 'completed') {
                throw new RuntimeException('Follower BrokerOrder did not produce a completed unified execution receipt.');
            }

            $executed = (float) ($followerExecution->settlement_amount ?? $followerExecution->gross_value ?? 0);

            $relationship->update([
                'used_amount' => $providerExecution->side === 'buy'
                    ? min((float) $relationship->allocation_limit, (float) $relationship->used_amount + $executed)
                    : max(0, (float) $relationship->used_amount - $executed),
            ]);

            $record = $this->execution(
                $relationship,
                $providerExecution,
                $order,
                $followerExecution,
                $instrument,
                $requested,
                $executed,
                'completed',
                null
            );

            NotificationService::createSystemNotification(
                $follower,
                'Copy trade executed',
                ($instrument->display_symbol ?: $instrument->symbol).' '.$providerExecution->side.' mirrored for $'.number_format($executed, 2).'.',
                [
                    'type' => 'copy_trade',
                    'relationship_id' => $relationship->id,
                    'copy_trade_execution_id' => $record->id,
                    'broker_order_id' => $order->id,
                    'market_execution_transaction_id' => $followerExecution->id,
                    'position_id' => $followerExecution->trade_position_id,
                    'market_instrument_id' => $instrument->id,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Copy trade failed', [
                'relationship_id' => $relationship->id,
                'provider_market_execution_transaction_id' => $providerExecution->id,
                'market_instrument_id' => $instrument->id,
                'error' => $e->getMessage(),
            ]);

            $existingOrder = BrokerOrder::query()
                ->where('user_id', $follower->id)
                ->where('idempotency_key', $this->idempotencyKey($relationship, $providerExecution))
                ->first();
            $existingOrder?->loadMissing('execution');

            $this->execution(
                $relationship,
                $providerExecution,
                $existingOrder,
                $existingOrder?->execution,
                $instrument,
                $requested,
                0,
                'failed',
                substr($e->getMessage(), 0, 255)
            );
        }
    }

    private function sizeForCapital(
        $follower,
        MarketInstrument $instrument,
        float $capital,
        float $price,
        string $marketplace,
        bool $isBuy
    ): array {
        if ($capital <= 0 || $price <= 0) {
            throw new RuntimeException('Copy trade sizing inputs are invalid.');
        }

        if ($instrument->isStock()) {
            $units = $capital / $price;
            if ($units <= 0) {
                throw new RuntimeException('Stock copy quantity resolved to zero.');
            }

            return [
                'order_quantity' => $units,
                'quantity_mode' => 'units',
                'display_quantity' => $units,
            ];
        }

        $walletCurrency = strtoupper((string) ($follower->wallet?->currency ?: 'USD'));
        $requireFresh = $marketplace === 'live';
        $oneUnit = $this->settlement->amountForBaseUnits(
            $instrument,
            1.0,
            $price,
            $walletCurrency,
            $requireFresh
        );

        if ($oneUnit <= 0) {
            throw new RuntimeException('Settlement conversion is unavailable for copy sizing.');
        }

        $units = round($capital / $oneUnit, 8);
        if ($instrument->isForex() && $units < 1) {
            throw new RuntimeException('Forex copy amount resolves below the minimum executable base-unit quantity.');
        }
        if ($units <= 0) {
            throw new RuntimeException('Copy amount resolves below supported execution precision.');
        }

        if ($instrument->isCrypto() && $isBuy) {
            return [
                'order_quantity' => round($capital, 2),
                'quantity_mode' => 'settlement_amount',
                'display_quantity' => $units,
            ];
        }

        return [
            'order_quantity' => $units,
            'quantity_mode' => 'units',
            'display_quantity' => $units,
        ];
    }

    private function followerPosition(
        CopyRelationship $relationship,
        MarketInstrument $instrument,
        string $marketplace,
        ?int $providerPositionId
    ): ?TradePosition {
        $base = TradePosition::query()
            ->where('user_id', $relationship->follower_id)
            ->where('market_instrument_id', $instrument->id)
            ->where('marketplace', $marketplace)
            ->where('context_type', 'copy_relationship')
            ->where('context_id', $relationship->id)
            ->whereIn('status', ['open', 'exit_queued'])
            ->where('open_quantity', '>', 0)
            ->oldest('opened_at')
            ->oldest('id');

        if ($providerPositionId) {
            $exact = (clone $base)->where('source_position_id', $providerPositionId)->first();
            if ($exact) {
                return $exact;
            }
        }

        return $base->first();
    }

    private function strategyIdFromExecution(MarketExecutionTransaction $execution): ?int
    {
        $metadata = $execution->metadata ?? [];

        if (isset($metadata['copy_strategy_id']) && (int) $metadata['copy_strategy_id'] > 0) {
            return (int) $metadata['copy_strategy_id'];
        }

        $requested = $metadata['requested_context'] ?? [];
        if (
            is_array($requested)
            && ($requested['context_type'] ?? null) === 'copy_strategy'
            && (int) ($requested['context_id'] ?? 0) > 0
        ) {
            return (int) $requested['context_id'];
        }

        return null;
    }

    private function idempotencyKey(
        CopyRelationship $relationship,
        MarketExecutionTransaction $providerExecution
    ): string {
        return 'copy-rel:'.$relationship->id.':provider-exec:'.$providerExecution->id.':'.$providerExecution->side;
    }

    private function execution(
        CopyRelationship $relationship,
        MarketExecutionTransaction $providerExecution,
        ?BrokerOrder $followerOrder,
        ?MarketExecutionTransaction $followerExecution,
        MarketInstrument $instrument,
        float $requested,
        float $executed,
        string $status,
        ?string $reason
    ): CopyTradeExecution {
        $providerOrderId = BrokerOrder::query()
            ->where('market_execution_transaction_id', $providerExecution->id)
            ->value('id');

        $attributes = [
            'copy_relationship_id' => $relationship->id,
            'market_instrument_id' => $instrument->id,
            'provider_stock_transaction_id' => $providerExecution->native_type === 'stock_transaction'
                ? $providerExecution->native_id
                : null,
            'provider_market_execution_transaction_id' => $providerExecution->id,
            'provider_broker_order_id' => $providerOrderId,
            'follower_stock_transaction_id' => $followerExecution?->native_type === 'stock_transaction'
                ? $followerExecution?->native_id
                : null,
            'follower_market_execution_transaction_id' => $followerExecution?->id,
            'follower_broker_order_id' => $followerOrder?->id,
            'action' => $providerExecution->side,
            'requested_amount' => round($requested, 2),
            'executed_amount' => round($executed, 2),
            'status' => $status,
            'failure_reason' => $reason,
            'executed_at' => $followerExecution?->executed_at ?? now(),
        ];

        return CopyTradeExecution::updateOrCreate(
            [
                'copy_relationship_id' => $relationship->id,
                'provider_market_execution_transaction_id' => $providerExecution->id,
            ],
            $attributes
        );
    }
}
