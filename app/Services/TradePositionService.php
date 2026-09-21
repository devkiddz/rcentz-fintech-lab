<?php

namespace App\Services;

use App\Models\StockTransaction;
use App\Models\TradePosition;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TradePositionService
{
    public function __construct(
        private StockTradeExecutor $executor,
        private MarketSessionService $marketSession,
        private MarketPriceRouter $prices,
        private MarketExecutionLedgerService $executionLedger
    ) {}

    public function openLongFromTrade(
        StockTransaction $entry,
        array $risk = [],
        string $contextType = 'manual_trade',
        ?int $contextId = null,
        ?int $sourcePositionId = null,
        string $actorType = 'system',
        ?int $actorId = null
    ): TradePosition {
        if ($entry->type !== 'buy' || $entry->status !== 'completed') {
            throw new RuntimeException('Only a completed BUY can open a long position.');
        }

        if ($entry->trade_position_id) {
            return TradePosition::findOrFail($entry->trade_position_id);
        }

        $entry->loadMissing('stock.marketInstrument');
        if (! $entry->stock) {
            throw new RuntimeException('Stock execution entry is missing its Stock child.');
        }
        $instrument = $this->prices->instrument($entry->stock);

        $entryPrice = (float) $entry->price_per_share;
        $slPct = $this->nullablePercent($risk['stop_loss_percent'] ?? null);
        $tpPct = $this->nullablePercent($risk['take_profit_percent'] ?? null);
        $duration = isset($risk['duration_minutes']) && (int)$risk['duration_minutes'] > 0
            ? (int)$risk['duration_minutes']
            : null;

        $stop = $slPct ? round($entryPrice * (1 - ($slPct / 100)), 8) : null;
        $take = $tpPct ? round($entryPrice * (1 + ($tpPct / 100)), 8) : null;
        $openedAt = $entry->executed_at ?? now();
        $marketplace = $entry->marketplace ?: $this->prices->activeMarketplace();
        $end = $this->effectiveEndForMarketplace($marketplace, $openedAt, $duration);

        $metadata = array_merge(
            $risk['metadata'] ?? [],
            [
                'requested_duration_minutes' => $duration,
                'requested_end_et' => $end['requested_at_et']?->toIso8601String(),
                'session_close_et' => $end['session_close_et']?->toIso8601String(),
                'effective_end_reason' => $end['reason'],
            ]
        );

        return DB::transaction(function () use (
            $entry,$instrument,$contextType,$contextId,$sourcePositionId,$actorType,$actorId,
            $entryPrice,$slPct,$tpPct,$duration,$stop,$take,$openedAt,$end,$metadata,$marketplace
        ) {
            $position = TradePosition::create([
                'user_id'=>$entry->user_id,
                'market_instrument_id'=>$instrument->id,
                'stock_id'=>$entry->stock_id,
                'entry_transaction_id'=>$entry->id,
                'source_position_id'=>$sourcePositionId,
                'context_type'=>$contextType,
                'context_id'=>$contextId,
                'marketplace'=>$marketplace,
                'direction'=>'long',
                'initial_quantity'=>$entry->quantity,
                'open_quantity'=>$entry->quantity,
                'entry_price'=>$entryPrice,
                'stop_loss_price'=>$stop,
                'take_profit_price'=>$take,
                'stop_loss_percent'=>$slPct,
                'take_profit_percent'=>$tpPct,
                'duration_minutes'=>$duration,
                'opened_at'=>$openedAt,
                'expires_at'=>$end['effective_at'],
                'status'=>'open',
                'exit_reason'=>null,
                'realized_profit_loss'=>0,
                'realized_return_percent'=>0,
                'metadata'=>$metadata,
            ]);

            $entry->update(['trade_position_id'=>$position->id]);
            $execution = $this->executionLedger->forStockTransaction($entry);
            $execution->update(['trade_position_id'=>$position->id]);
            $position->update(['entry_market_execution_transaction_id'=>$execution->id]);

            $this->event(
                $position,
                'entry',
                (float)$entry->quantity,
                $entryPrice,
                0,
                $entry,
                'Position opened.',
                $actorType,
                $actorId,
                [
                    'emp'=>$entryPrice,
                    'stop_loss_price'=>$stop,
                    'take_profit_price'=>$take,
                    'effective_close_at'=>$position->expires_at?->toIso8601String(),
                    'effective_end_reason'=>$end['reason'],
                ]
            );

            return $position;
        });
    }

    /**
     * Full manual kill.
     *
     * This is irreversible at product level: it closes 100% of remaining
     * position quantity using the same sell/wallet/holding ledger as every exit.
     * CMP is the price written to the exit transaction.
     */
    public function kill(
        TradePosition $position,
        string $actorType = 'user',
        ?int $actorId = null
    ): StockTransaction {
        $position->refresh();
        $position->loadMissing(['user','stock','entryTransaction']);

        if (! $position->is_open) {
            throw new RuntimeException('This trade contract is already closed.');
        }

        $cmp = $this->prices->price($position->stock, $position->marketplace ?: 'live');

        if ($cmp <= 0) {
            throw new RuntimeException('Current Market Price is unavailable.');
        }

        return $this->close(
            $position,
            'manual_kill',
            null,
            $actorType,
            $actorId,
            true,
            $cmp,
            'position_kill'
        );
    }

    public function close(
        TradePosition $position,
        string $reason = 'manual_close',
        ?float $quantity = null,
        string $actorType = 'system',
        ?int $actorId = null,
        bool $allowClosedSessionSettlement = false,
        ?float $settlementPrice = null,
        string $executionSource = 'position_exit'
    ): StockTransaction {
        $position->refresh();
        $position->loadMissing(['user','stock','entryTransaction']);

        if (! $position->is_open) {
            throw new RuntimeException('Position is already closed.');
        }

        $marketplace = $position->marketplace ?: 'live';

        if (
            ! $allowClosedSessionSettlement
            && $this->prices->requiresRegularSession($marketplace)
            && ! $this->marketSession->isOpen()
        ) {
            throw new RuntimeException('Regular market session is closed for this Live contract.');
        }

        $qty = $quantity === null
            ? (float)$position->open_quantity
            : min((float)$quantity, (float)$position->open_quantity);

        if ($qty <= 0) {
            throw new RuntimeException('Close quantity must be greater than zero.');
        }

        $cmp = $settlementPrice !== null
            ? (float)$settlementPrice
            : $this->prices->price($position->stock, $marketplace);

        $trade = $this->executor->sell(
            $position->user,
            $position->stock,
            $qty,
            $executionSource,
            $position->id,
            $position->entryTransaction?->copy_strategy_id,
            $actorType,
            $actorId,
            $position->id,
            $allowClosedSessionSettlement,
            $cmp,
            $marketplace
        );

        $this->applyExitTransaction(
            $position,
            $trade,
            $reason,
            $actorType,
            $actorId
        );

        return $trade;
    }

    public function applyExitTransaction(
        TradePosition $position,
        StockTransaction $trade,
        string $reason,
        string $actorType = 'system',
        ?int $actorId = null,
        bool $linkTransaction = true
    ): void {
        DB::transaction(function () use (
            $position,$trade,$reason,$actorType,$actorId,$linkTransaction
        ) {
            $position = TradePosition::lockForUpdate()->findOrFail($position->id);

            $qty = min((float)$trade->quantity, (float)$position->open_quantity);
            if ($qty <= 0) return;

            $cmp = (float)$trade->price_per_share;
            $emp = (float)$position->entry_price;

            // LONG POSITION CORE:
            // P/L per share = CMP - EMP
            // Total realized P/L = (CMP - EMP) × closed quantity
            $pnl = ($cmp - $emp) * $qty;

            $oldOpen = (float)$position->open_quantity;
            $remaining = max(0, $oldOpen - $qty);
            $closedBefore = (float)$position->initial_quantity - $oldOpen;
            $closedAfter = $closedBefore + $qty;

            $avgExit = $closedAfter > 0
                ? (
                    ((float)($position->average_exit_price ?? 0) * $closedBefore)
                    + ($cmp * $qty)
                ) / $closedAfter
                : $cmp;

            $realized = (float)$position->realized_profit_loss + $pnl;
            $basis = $emp * (float)$position->initial_quantity;

            $position->update([
                'open_quantity'=>$remaining,
                'average_exit_price'=>$avgExit,
                'last_exit_transaction_id'=>$trade->id,
                'realized_profit_loss'=>$realized,
                'realized_return_percent'=>$basis > 0 ? ($realized/$basis)*100 : 0,
                'status'=>$remaining <= 0 ? 'closed' : 'open',
                'exit_reason'=>$remaining <= 0 ? $reason : $position->exit_reason,
                'closed_at'=>$remaining <= 0 ? ($trade->executed_at ?? now()) : null,
            ]);

            $execution = $this->executionLedger->forStockTransaction($trade);
            $position->update(['last_exit_market_execution_transaction_id'=>$execution->id]);

            if ($linkTransaction && $trade->exists && ! $trade->trade_position_id) {
                $trade->update(['trade_position_id'=>$position->id]);
                $execution->update(['trade_position_id'=>$position->id]);
            }

            $this->event(
                $position,
                $remaining <= 0 ? $reason : 'partial_close',
                $qty,
                $cmp,
                $pnl,
                $trade,
                $remaining <= 0 ? 'Trade contract closed.' : 'Position partially closed.',
                $actorType,
                $actorId,
                [
                    'emp'=>$emp,
                    'cmp'=>$cmp,
                    'price_difference'=>$cmp-$emp,
                    'remaining_quantity'=>$remaining,
                    'realized_profit_loss'=>$pnl,
                ]
            );
        });
    }

    public function consumeSellTransaction(
        StockTransaction $sell,
        string $reason = 'manual_close',
        ?string $contextType = null,
        ?int $contextId = null,
        string $actorType = 'system',
        ?int $actorId = null
    ): void {
        if ($sell->type !== 'sell' || $sell->status !== 'completed') return;

        $remaining = (float)$sell->quantity;

        $query = TradePosition::with('stock')
            ->where('user_id',$sell->user_id)
            ->where('stock_id',$sell->stock_id)
            ->where('marketplace',$sell->marketplace ?: 'live')
            ->whereIn('status',['open','exit_queued'])
            ->where('open_quantity','>',0)
            ->orderBy('opened_at');

        if ($contextType) $query->where('context_type',$contextType);
        if ($contextId !== null) $query->where('context_id',$contextId);

        foreach ($query->get() as $position) {
            if ($remaining <= 0) break;

            $consume = min($remaining,(float)$position->open_quantity);

            $shadow = clone $sell;
            $shadow->quantity = $consume;
            $shadow->total_amount = $consume * (float)$sell->price_per_share;

            $this->applyExitTransaction(
                $position,
                $shadow,
                $reason,
                $actorType,
                $actorId,
                false
            );

            $remaining -= $consume;
        }
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

        if (! $position->is_open) {
            throw new RuntimeException('Only open positions can be managed.');
        }

        $sl = $this->nullablePercent($stopLossPercent);
        $tp = $this->nullablePercent($takeProfitPercent);
        $emp = (float)$position->entry_price;

        $marketplace = $position->marketplace ?: 'live';
        $end = $this->effectiveEndForMarketplace(
            $marketplace,
            now(),
            $durationMinutes && $durationMinutes > 0 ? $durationMinutes : null
        );

        $metadata = array_merge(
            $position->metadata ?? [],
            [
                'requested_duration_minutes'=>$durationMinutes,
                'requested_end_et'=>$end['requested_at_et']?->toIso8601String(),
                'session_close_et'=>$end['session_close_et']?->toIso8601String(),
                'effective_end_reason'=>$end['reason'],
            ]
        );

        $position->update([
            'stop_loss_percent'=>$sl,
            'take_profit_percent'=>$tp,
            'stop_loss_price'=>$sl ? round($emp*(1-$sl/100),8) : null,
            'take_profit_price'=>$tp ? round($emp*(1+$tp/100),8) : null,
            'duration_minutes'=>$durationMinutes && $durationMinutes > 0
                ? $durationMinutes
                : null,
            'expires_at'=>$end['effective_at'],
            'status'=>'open',
            'exit_reason'=>null,
            'metadata'=>$metadata,
        ]);

        $this->event(
            $position,
            'risk_updated',
            null,
            $position->stock ? $this->prices->price($position->stock, $marketplace) : 0,
            null,
            null,
            'Risk controls updated.',
            $actorType,
            $actorId,
            [
                'stop_loss_percent'=>$sl,
                'take_profit_percent'=>$tp,
                'effective_close_at'=>$end['effective_at']?->toIso8601String(),
                'effective_end_reason'=>$end['reason'],
            ]
        );

        return $position->refresh();
    }

    public function reenter(
        TradePosition $closed,
        ?float $quantity = null,
        string $actorType = 'user',
        ?int $actorId = null
    ): TradePosition {
        if ($closed->status !== 'closed') {
            throw new RuntimeException('Only closed positions can be re-entered.');
        }

        $closed->loadMissing(['user','stock','entryTransaction']);

        $qty = $quantity && $quantity > 0
            ? $quantity
            : (float)$closed->initial_quantity;

        $trade = $this->executor->buy(
            $closed->user,
            $closed->stock,
            $qty,
            'position_reentry',
            $closed->id,
            $closed->entryTransaction?->copy_strategy_id,
            $actorType,
            $actorId,
            null,
            $closed->marketplace ?: 'live'
        );

        $new = $this->openLongFromTrade(
            $trade,
            [
                'stop_loss_percent'=>$closed->stop_loss_percent,
                'take_profit_percent'=>$closed->take_profit_percent,
                'duration_minutes'=>$closed->duration_minutes,
                'metadata'=>[
                    'reentered_from_position_id'=>$closed->id,
                ],
            ],
            $closed->context_type,
            $closed->context_id,
            $closed->id,
            $actorType,
            $actorId
        );

        $this->event(
            $closed,
            'reentered',
            null,
            (float)$trade->price_per_share,
            null,
            $trade,
            'New position opened from closed trade.',
            $actorType,
            $actorId,
            ['new_position_id'=>$new->id]
        );

        return $new;
    }

    public function processOpenPositions(): array
    {
        $stats=[
            'checked'=>0,
            'take_profit'=>0,
            'stop_loss'=>0,
            'time_expiry'=>0,
            'market_close'=>0,
            'failed'=>0,
        ];

        TradePosition::with(['user','stock','entryTransaction'])
            ->whereNotNull('stock_id')
            ->whereIn('status',['open','exit_queued'])
            ->where('open_quantity','>',0)
            // Copy followers are closed by provider mirroring or copy-contract
            // settlement, so they must not race the provider's automatic exit.
            ->where('context_type','!=','copy_relationship')
            ->orderBy('id')
            ->chunkById(100,function($positions) use (&$stats){
                foreach($positions as $position){
                    $position->refresh();

                    if (! $position->is_open) {
                        continue;
                    }

                    $stats['checked']++;

                    $cmp=$this->prices->price($position->stock, $position->marketplace ?: 'live');
                    $reason=null;

                    if($position->stop_loss_price && $cmp <= (float)$position->stop_loss_price){
                        $reason='stop_loss';
                    } elseif($position->take_profit_price && $cmp >= (float)$position->take_profit_price){
                        $reason='take_profit';
                    } elseif($position->expires_at && $position->expires_at->isPast()){
                        $reason=($position->metadata['effective_end_reason'] ?? null)==='market_close'
                            ? 'market_close'
                            : 'time_expiry';
                    }

                    if(! $reason) {
                        continue;
                    }

                    try{
                        // Once a valid end condition fires, the trade contract ends.
                        // If the scheduler observes it just after 16:00 ET, the stored
                        // CMP is the session's closing/current market value used for
                        // settlement rather than carrying the contract overnight.
                        $allowSessionSettlement=$this->prices->requiresRegularSession($position->marketplace ?: 'live')
                            && ! $this->marketSession->isOpen();

                        $trade=$this->close(
                            $position,
                            $reason,
                            null,
                            'system',
                            null,
                            $allowSessionSettlement,
                            $cmp,
                            'position_exit'
                        );

                        if($position->context_type==='copy_strategy'){
                            try{
                                app(CopyTradingService::class)
                                    ->mirrorCompletedTrade($trade);
                            }catch(\Throwable $mirrorError){
                                \Log::warning('Automatic strategy exit mirroring failed',[
                                    'position_id'=>$position->id,
                                    'trade_id'=>$trade->id,
                                    'error'=>$mirrorError->getMessage(),
                                ]);
                            }
                        }

                        $stats[$reason]=($stats[$reason] ?? 0)+1;
                    }catch(\Throwable $e){
                        \Log::warning('Position automatic exit failed',[
                            'position_id'=>$position->id,
                            'reason'=>$reason,
                            'error'=>$e->getMessage(),
                        ]);

                        $stats['failed']++;
                    }
                }
            });

        return $stats;
    }

    public function settleContext(
        string $contextType,
        int $contextId,
        string $reason='contract_expiry'
    ): array {
        $positions=TradePosition::with(['stock','user','entryTransaction'])
            ->where('context_type',$contextType)
            ->where('context_id',$contextId)
            ->whereIn('status',['open','exit_queued'])
            ->where('open_quantity','>',0)
            ->get();

        $result=[
            'open'=>$positions->count(),
            'closed'=>0,
            'failed'=>0,
        ];

        foreach($positions as $position){
            try{
                $this->close(
                    $position,
                    $reason,
                    null,
                    'system',
                    null,
                    true,
                    $this->prices->price($position->stock, $position->marketplace ?: 'live')
                );

                $result['closed']++;
            }catch(\Throwable $e){
                \Log::warning('Context settlement failed',[
                    'context_type'=>$contextType,
                    'context_id'=>$contextId,
                    'position_id'=>$position->id,
                    'error'=>$e->getMessage(),
                ]);

                $result['failed']++;
            }
        }

        return $result;
    }

    private function event(
        TradePosition $position,
        string $type,
        ?float $quantity,
        ?float $price,
        ?float $pnl,
        ?StockTransaction $tx,
        ?string $note,
        string $actorType,
        ?int $actorId,
        array $metadata=[]
    ): void {
        $marketExecutionTransactionId = null;
        if ($tx?->getKey()) {
            $marketExecutionTransactionId = $this->executionLedger->forStockTransaction($tx)->id;
        }

        $position->events()->create([
            'stock_transaction_id'=>$tx?->id,
            'market_execution_transaction_id'=>$marketExecutionTransactionId,
            'actor_id'=>$actorId,
            'actor_type'=>$actorType,
            'event_type'=>$type,
            'quantity'=>$quantity,
            'price'=>$price,
            'profit_loss'=>$pnl,
            'note'=>$note,
            'metadata'=>$metadata ?: null,
        ]);
    }

    private function effectiveEndForMarketplace(
        string $marketplace,
        \Carbon\CarbonInterface $openedAt,
        ?int $durationMinutes
    ): array {
        if ($this->prices->requiresRegularSession($marketplace)) {
            return $this->marketSession->effectiveEnd($openedAt, $durationMinutes);
        }

        $opened = \Carbon\Carbon::instance($openedAt)->copy();
        $requested = $durationMinutes && $durationMinutes > 0
            ? $opened->copy()->addMinutes($durationMinutes)
            : null;

        return [
            'effective_at' => $requested,
            'effective_at_et' => $requested?->copy()->setTimezone(MarketSessionService::TIMEZONE),
            'requested_at_et' => $requested?->copy()->setTimezone(MarketSessionService::TIMEZONE),
            'session_close_et' => null,
            'reason' => $requested ? 'time_expiry' : 'open_until_closed',
        ];
    }

    private function nullablePercent(mixed $value): ?float
    {
        if($value===null || $value==='') return null;

        $v=(float)$value;

        if($v<=0 || $v>100){
            throw new RuntimeException(
                'Risk percentage must be greater than 0 and at most 100.'
            );
        }

        return $v;
    }
}
