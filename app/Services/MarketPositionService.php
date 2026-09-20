<?php

namespace App\Services;

use App\Models\MarketExecutionTransaction;
use App\Models\TradePosition;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class MarketPositionService
{
    public function __construct(
        private MarketExecutionRouter $execution,
        private ForexExecutionQuoteService $forexQuotes,
        private ForexSessionService $forexSessions,
        private CryptoExecutionQuoteService $cryptoQuotes
    ) {}

    public function openLongFromExecution(
        MarketExecutionTransaction $entry,
        array $risk = [],
        string $contextType = 'market_trade',
        ?int $contextId = null,
        ?int $sourcePositionId = null,
        string $actorType = 'system',
        ?int $actorId = null
    ): TradePosition {
        if ($entry->side !== 'buy' || $entry->status !== 'completed') {
            throw new RuntimeException('Only a completed BUY execution can open a long market position.');
        }

        if ($entry->trade_position_id) {
            return TradePosition::findOrFail($entry->trade_position_id);
        }

        $entry->loadMissing('marketInstrument');
        $instrument = $entry->marketInstrument;
        if (! $instrument || $instrument->isStock()) {
            throw new RuntimeException('Generic MarketPositionService is reserved for non-Stock MarketInstrument positions.');
        }

        $entryPrice = (float) $entry->price;
        $slPct = $this->nullablePercent($risk['stop_loss_percent'] ?? null);
        $tpPct = $this->nullablePercent($risk['take_profit_percent'] ?? null);
        $duration = isset($risk['duration_minutes']) && (int) $risk['duration_minutes'] > 0
            ? (int) $risk['duration_minutes']
            : null;
        $openedAt = $entry->executed_at ?? now();
        $stop = $slPct ? round($entryPrice * (1 - ($slPct / 100)), 10) : null;
        $take = $tpPct ? round($entryPrice * (1 + ($tpPct / 100)), 10) : null;
        $expiresAt = $duration ? $openedAt->copy()->addMinutes($duration) : null;

        $metadata = array_merge($risk['metadata'] ?? [], [
            'asset_class' => $instrument->asset_class,
            'settlement_currency' => $entry->settlement_currency,
            'execution_model' => $entry->metadata['execution_model'] ?? null,
            'requested_duration_minutes' => $duration,
        ]);

        return DB::transaction(function () use (
            $entry,
            $instrument,
            $contextType,
            $contextId,
            $sourcePositionId,
            $entryPrice,
            $slPct,
            $tpPct,
            $duration,
            $openedAt,
            $stop,
            $take,
            $expiresAt,
            $metadata,
            $actorType,
            $actorId
        ) {
            $locked = MarketExecutionTransaction::query()->lockForUpdate()->findOrFail($entry->id);
            if ($locked->trade_position_id) {
                return TradePosition::findOrFail($locked->trade_position_id);
            }

            $position = TradePosition::create([
                'user_id' => $entry->user_id,
                'market_instrument_id' => $instrument->id,
                'stock_id' => null,
                'entry_transaction_id' => null,
                'entry_market_execution_transaction_id' => $entry->id,
                'source_position_id' => $sourcePositionId,
                'context_type' => $contextType,
                'context_id' => $contextId,
                'marketplace' => $entry->marketplace ?: 'live',
                'direction' => 'long',
                'initial_quantity' => $entry->quantity,
                'open_quantity' => $entry->quantity,
                'entry_price' => $entryPrice,
                'stop_loss_price' => $stop,
                'take_profit_price' => $take,
                'stop_loss_percent' => $slPct,
                'take_profit_percent' => $tpPct,
                'duration_minutes' => $duration,
                'opened_at' => $openedAt,
                'expires_at' => $expiresAt,
                'status' => 'open',
                'exit_reason' => null,
                'realized_profit_loss' => 0,
                'realized_return_percent' => 0,
                'metadata' => $metadata,
            ]);

            $locked->update(['trade_position_id' => $position->id]);
            $this->event($position, 'entry', (float) $entry->quantity, $entryPrice, 0, $entry, 'Position opened.', $actorType, $actorId);

            return $position;
        });
    }

    public function kill(TradePosition $position, string $actorType = 'user', ?int $actorId = null): MarketExecutionTransaction
    {
        return $this->close($position, 'manual_kill', null, $actorType, $actorId, 'position_kill');
    }

    public function close(
        TradePosition $position,
        string $reason = 'manual_close',
        ?float $quantity = null,
        string $actorType = 'system',
        ?int $actorId = null,
        string $executionSource = 'position_exit',
        ?string $idempotencyKey = null
    ): MarketExecutionTransaction {
        return DB::transaction(function () use (
            $position,
            $reason,
            $quantity,
            $actorType,
            $actorId,
            $executionSource,
            $idempotencyKey
        ) {
            $position->refresh();
            $position->loadMissing(['user', 'marketInstrument']);

            if (! $position->is_open || ! $position->marketInstrument || $position->marketInstrument->isStock()) {
                throw new RuntimeException('This non-Stock market position is not open.');
            }

            $qty = $quantity === null
                ? (float) $position->open_quantity
                : min((float) $quantity, (float) $position->open_quantity);
            if ($qty <= 0) {
                throw new RuntimeException('Close quantity must be greater than zero.');
            }

            $execution = $this->execution->execute(
                $position->user,
                $position->marketInstrument,
                'sell',
                $qty,
                [
                    'source' => $executionSource,
                    'source_id' => $position->id,
                    'actor_type' => $actorType,
                    'actor_id' => $actorId,
                    'position_id' => $position->id,
                    'marketplace' => $position->marketplace ?: 'live',
                    'quantity_mode' => 'units',
                    'idempotency_key' => $idempotencyKey,
                    'context_type' => $position->context_type,
                    'context_id' => $position->context_id,
                ]
            );

            $this->applyExitExecution($position, $execution, $reason, $actorType, $actorId);
            return $execution;
        });
    }

    public function consumeSellExecution(
        MarketExecutionTransaction $execution,
        string $reason = 'manual_close',
        ?string $contextType = null,
        ?int $contextId = null,
        string $actorType = 'system',
        ?int $actorId = null
    ): void {
        if ($execution->side !== 'sell' || $execution->status !== 'completed') {
            return;
        }

        $metadata = $execution->metadata ?? [];
        $already = (float) ($metadata['position_reconciled_quantity'] ?? 0);
        $remaining = max(0, (float) $execution->quantity - $already);
        if ($remaining <= 0) {
            return;
        }

        $query = TradePosition::query()
            ->where('user_id', $execution->user_id)
            ->where('market_instrument_id', $execution->market_instrument_id)
            ->where('marketplace', $execution->marketplace ?: 'live')
            ->whereNull('stock_id')
            ->whereIn('status', ['open', 'exit_queued'])
            ->where('open_quantity', '>', 0)
            ->orderBy('opened_at')
            ->orderBy('id');

        if ($contextType) {
            $query->where('context_type', $contextType);
        }
        if ($contextId !== null) {
            $query->where('context_id', $contextId);
        }

        foreach ($query->get() as $position) {
            if ($remaining <= 0) {
                break;
            }

            $consume = min($remaining, (float) $position->open_quantity);
            $this->applyExitExecution($position, $execution, $reason, $actorType, $actorId, $consume);
            $remaining -= $consume;
            $already += $consume;
        }

        $metadata['position_reconciled_quantity'] = round($already, 8);
        $execution->update(['metadata' => $metadata]);
    }

    public function updateRisk(
        TradePosition $position,
        ?float $stopLossPercent,
        ?float $takeProfitPercent,
        ?int $durationMinutes,
        string $actorType = 'user',
        ?int $actorId = null
    ): TradePosition {
        $position->refresh();
        if (! $position->is_open || $position->stock_id !== null) {
            throw new RuntimeException('Only open non-Stock positions can be managed here.');
        }

        $sl = $this->nullablePercent($stopLossPercent);
        $tp = $this->nullablePercent($takeProfitPercent);
        $entry = (float) $position->entry_price;

        $position->update([
            'stop_loss_percent' => $sl,
            'take_profit_percent' => $tp,
            'stop_loss_price' => $sl ? round($entry * (1 - ($sl / 100)), 10) : null,
            'take_profit_price' => $tp ? round($entry * (1 + ($tp / 100)), 10) : null,
            'duration_minutes' => $durationMinutes && $durationMinutes > 0 ? $durationMinutes : null,
            'expires_at' => $durationMinutes && $durationMinutes > 0 ? now()->addMinutes($durationMinutes) : null,
            'status' => 'open',
            'exit_reason' => null,
        ]);

        $this->event($position, 'risk_updated', null, null, null, null, 'Risk controls updated.', $actorType, $actorId);
        return $position->refresh();
    }

    public function processOpenNonStockPositions(): array
    {
        $forex = $this->processAssetClass('forex');
        $crypto = $this->processAssetClass('crypto');

        return [
            'forex' => $forex,
            'crypto' => $crypto,
            'total' => [
                'checked' => $forex['checked'] + $crypto['checked'],
                'take_profit' => $forex['take_profit'] + $crypto['take_profit'],
                'stop_loss' => $forex['stop_loss'] + $crypto['stop_loss'],
                'time_expiry' => $forex['time_expiry'] + $crypto['time_expiry'],
                'queued' => $forex['queued'] + $crypto['queued'],
                'closed_session' => $forex['closed_session'] + $crypto['closed_session'],
                'failed' => $forex['failed'] + $crypto['failed'],
            ],
        ];
    }

    public function processOpenForexPositions(): array
    {
        return $this->processAssetClass('forex');
    }

    public function processOpenCryptoPositions(): array
    {
        return $this->processAssetClass('crypto');
    }

    private function processAssetClass(string $assetClass): array
    {
        if (! in_array($assetClass, ['forex', 'crypto'], true)) {
            throw new RuntimeException('Unsupported non-Stock position asset class.');
        }

        $stats = [
            'checked' => 0,
            'take_profit' => 0,
            'stop_loss' => 0,
            'time_expiry' => 0,
            'queued' => 0,
            'closed_session' => 0,
            'failed' => 0,
        ];

        $with = ['user', 'marketInstrument'];
        $with[] = $assetClass === 'forex'
            ? 'marketInstrument.canonicalForexPair'
            : 'marketInstrument.canonicalCryptoPair';

        TradePosition::query()
            ->with($with)
            ->whereNull('stock_id')
            ->whereHas('marketInstrument', fn ($q) => $q->where('asset_class', $assetClass))
            ->whereIn('status', ['open', 'exit_queued'])
            ->where('open_quantity', '>', 0)
            ->orderBy('id')
            ->chunkById(100, function ($positions) use (&$stats, $assetClass) {
                foreach ($positions as $position) {
                    $stats['checked']++;

                    try {
                        $marketplace = $position->marketplace ?: 'live';

                        if (
                            $assetClass === 'forex'
                            && $marketplace === 'live'
                            && ! $this->forexSessions->isMarketOpen()
                        ) {
                            if (
                                $position->expires_at
                                && $position->expires_at->isPast()
                                && $position->status !== 'exit_queued'
                            ) {
                                $position->update([
                                    'status' => 'exit_queued',
                                    'exit_reason' => 'time_expiry_pending_market',
                                ]);
                                $this->event(
                                    $position,
                                    'exit_queued',
                                    null,
                                    null,
                                    null,
                                    null,
                                    'Time expiry queued until Forex market reopens.',
                                    'system',
                                    null
                                );
                                $stats['queued']++;
                            }

                            $stats['closed_session']++;
                            continue;
                        }

                        $quote = $assetClass === 'forex'
                            ? $this->forexQuotes->quote($position->marketInstrument, 'sell', $marketplace)
                            : $this->cryptoQuotes->quote($position->marketInstrument, 'sell', $marketplace);
                        $cmp = (float) $quote['price'];
                        $reason = null;

                        if ($position->status === 'exit_queued') {
                            $reason = str_contains((string) $position->exit_reason, 'time_expiry')
                                ? 'time_expiry'
                                : 'queued_exit';
                        } elseif ($position->stop_loss_price && $cmp <= (float) $position->stop_loss_price) {
                            $reason = 'stop_loss';
                        } elseif ($position->take_profit_price && $cmp >= (float) $position->take_profit_price) {
                            $reason = 'take_profit';
                        } elseif ($position->expires_at && $position->expires_at->isPast()) {
                            $reason = 'time_expiry';
                        }

                        if (! $reason) {
                            continue;
                        }

                        $key = 'auto-'.$assetClass.'-position-'.$position->id.'-'.$reason.'-'.$position->updated_at?->timestamp;
                        $execution = $this->close($position, $reason, null, 'system', null, 'position_exit', $key);

                        if ($position->context_type === 'copy_strategy' && (int) $position->context_id > 0) {
                            try {
                                app(CopyTradingService::class)->mirrorCompletedExecution(
                                    $execution,
                                    (int) $position->context_id
                                );
                            } catch (\Throwable $mirrorError) {
                                \Log::warning('Automatic multi-asset copy strategy exit mirroring failed', [
                                    'position_id' => $position->id,
                                    'market_execution_transaction_id' => $execution->id,
                                    'error' => $mirrorError->getMessage(),
                                ]);
                            }
                        }

                        $stats[$reason] = ($stats[$reason] ?? 0) + 1;
                    } catch (\Throwable $e) {
                        \Log::warning(ucfirst($assetClass).' position automatic lifecycle failed', [
                            'position_id' => $position->id,
                            'error' => $e->getMessage(),
                        ]);
                        $stats['failed']++;
                    }
                }
            });

        return $stats;
    }

    private function applyExitExecution(
        TradePosition $position,
        MarketExecutionTransaction $execution,
        string $reason,
        string $actorType,
        ?int $actorId,
        ?float $forcedQuantity = null
    ): void {
        DB::transaction(function () use ($position, $execution, $reason, $actorType, $actorId, $forcedQuantity) {
            $position = TradePosition::query()->lockForUpdate()->findOrFail($position->id);

            $alreadyLinked = $position->events()
                ->where('market_execution_transaction_id', $execution->id)
                ->whereIn('event_type', [$reason, 'partial_close'])
                ->exists();
            if ($alreadyLinked) {
                return;
            }

            $qty = min($forcedQuantity ?? (float) $execution->quantity, (float) $position->open_quantity);
            if ($qty <= 0) {
                return;
            }

            $oldOpen = (float) $position->open_quantity;
            $remaining = max(0, $oldOpen - $qty);
            $closedBefore = (float) $position->initial_quantity - $oldOpen;
            $closedAfter = $closedBefore + $qty;
            $price = (float) $execution->price;
            $averageExit = $closedAfter > 0
                ? (((float) ($position->average_exit_price ?? 0) * $closedBefore) + ($price * $qty)) / $closedAfter
                : $price;

            $executionRealized = (float) ($execution->realized_profit_loss ?? 0);
            $executionQty = max((float) $execution->quantity, 0.00000001);
            $realizedSlice = $executionRealized * ($qty / $executionQty);
            $realized = (float) $position->realized_profit_loss + $realizedSlice;
            $basis = (float) ($position->entry_market_execution_transaction_id
                ? ($position->entryMarketExecutionTransaction?->settlement_amount ?? 0)
                : 0);

            $position->update([
                'open_quantity' => $remaining,
                'average_exit_price' => $averageExit,
                'last_exit_market_execution_transaction_id' => $execution->id,
                'realized_profit_loss' => $realized,
                'realized_return_percent' => $basis > 0 ? ($realized / $basis) * 100 : 0,
                'status' => $remaining <= 0 ? 'closed' : 'open',
                'exit_reason' => $remaining <= 0 ? $reason : null,
                'closed_at' => $remaining <= 0 ? ($execution->executed_at ?? now()) : null,
            ]);

            if (! $execution->trade_position_id && $remaining <= 0) {
                $execution->update(['trade_position_id' => $position->id]);
            }

            $this->event(
                $position,
                $remaining <= 0 ? $reason : 'partial_close',
                $qty,
                $price,
                $realizedSlice,
                $execution,
                $remaining <= 0 ? 'Market position closed.' : 'Market position partially closed.',
                $actorType,
                $actorId
            );
        });
    }

    private function event(
        TradePosition $position,
        string $type,
        ?float $quantity,
        ?float $price,
        ?float $pnl,
        ?MarketExecutionTransaction $execution,
        ?string $note,
        string $actorType,
        ?int $actorId
    ): void {
        $position->events()->create([
            'stock_transaction_id' => null,
            'market_execution_transaction_id' => $execution?->id,
            'actor_id' => $actorId,
            'actor_type' => $actorType,
            'event_type' => $type,
            'quantity' => $quantity,
            'price' => $price,
            'profit_loss' => $pnl,
            'note' => $note,
            'metadata' => null,
        ]);
    }

    private function nullablePercent(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $percent = (float) $value;
        if ($percent <= 0 || $percent > 100) {
            throw new RuntimeException('Risk percentage must be greater than 0 and at most 100.');
        }

        return $percent;
    }
}
