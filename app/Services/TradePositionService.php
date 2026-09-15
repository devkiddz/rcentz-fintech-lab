<?php

namespace App\Services;

use App\Models\StockTransaction;
use App\Models\TradePosition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TradePositionService
{
    public function __construct(
        private StockTradeExecutor $executor,
        private MarketSessionService $marketSession
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

        $entryPrice = (float)$entry->price_per_share;
        $slPct = $this->nullablePercent($risk['stop_loss_percent'] ?? null);
        $tpPct = $this->nullablePercent($risk['take_profit_percent'] ?? null);
        $duration = isset($risk['duration_minutes']) && (int)$risk['duration_minutes'] > 0
            ? (int)$risk['duration_minutes']
            : null;

        $stop = $slPct ? round($entryPrice * (1 - ($slPct / 100)), 8) : null;
        $take = $tpPct ? round($entryPrice * (1 + ($tpPct / 100)), 8) : null;
        $openedAt = $entry->executed_at ?? now();

        return DB::transaction(function () use (
            $entry,$risk,$contextType,$contextId,$sourcePositionId,$actorType,$actorId,
            $entryPrice,$slPct,$tpPct,$duration,$stop,$take,$openedAt
        ) {
            $position = TradePosition::create([
                'user_id'=>$entry->user_id,
                'stock_id'=>$entry->stock_id,
                'entry_transaction_id'=>$entry->id,
                'source_position_id'=>$sourcePositionId,
                'context_type'=>$contextType,
                'context_id'=>$contextId,
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
                'expires_at'=>$duration ? $openedAt->copy()->addMinutes($duration) : null,
                'status'=>'open',
                'realized_profit_loss'=>0,
                'realized_return_percent'=>0,
                'metadata'=>$risk['metadata'] ?? null,
            ]);

            $entry->update(['trade_position_id'=>$position->id]);

            $this->event(
                $position,'entry',(float)$entry->quantity,$entryPrice,0,
                $entry,'Position opened',$actorType,$actorId,
                [
                    'stop_loss_price'=>$stop,
                    'take_profit_price'=>$take,
                    'expires_at'=>$position->expires_at?->toIso8601String(),
                ]
            );

            return $position;
        });
    }

    public function close(
        TradePosition $position,
        string $reason = 'manual_close',
        ?float $quantity = null,
        string $actorType = 'system',
        ?int $actorId = null
    ): StockTransaction {
        $position->refresh();
        $position->loadMissing(['user','stock']);

        if (! $position->is_open) {
            throw new RuntimeException('Position is already closed.');
        }

        if (! $this->marketSession->isOpen()) {
            $position->update([
                'status'=>'exit_queued',
                'exit_reason'=>$reason,
            ]);
            throw new RuntimeException('Market is closed. Exit queued for the next regular session.');
        }

        $qty = $quantity === null
            ? (float)$position->open_quantity
            : min((float)$quantity, (float)$position->open_quantity);

        if ($qty <= 0) throw new RuntimeException('Close quantity must be greater than zero.');

        $trade = $this->executor->sell(
            $position->user,
            $position->stock,
            $qty,
            'position_exit',
            $position->id,
            $position->entryTransaction?->copy_strategy_id,
            $actorType,
            $actorId,
            $position->id
        );

        $this->applyExitTransaction($position,$trade,$reason,$actorType,$actorId);

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
        DB::transaction(function () use ($position,$trade,$reason,$actorType,$actorId,$linkTransaction) {
            $position = TradePosition::lockForUpdate()->findOrFail($position->id);
            $qty = min((float)$trade->quantity,(float)$position->open_quantity);
            if ($qty <= 0) return;

            $exitPrice = (float)$trade->price_per_share;
            $entryPrice = (float)$position->entry_price;
            $pnl = ($exitPrice - $entryPrice) * $qty;
            $oldOpen = (float)$position->open_quantity;
            $remaining = max(0,$oldOpen-$qty);
            $closedBefore = (float)$position->initial_quantity - $oldOpen;
            $closedAfter = $closedBefore + $qty;

            $avgExit = $closedAfter > 0
                ? (((float)($position->average_exit_price ?? 0) * $closedBefore) + ($exitPrice * $qty)) / $closedAfter
                : $exitPrice;

            $realized = (float)$position->realized_profit_loss + $pnl;
            $basis = (float)$position->entry_price * (float)$position->initial_quantity;

            $position->update([
                'open_quantity'=>$remaining,
                'average_exit_price'=>$avgExit,
                'last_exit_transaction_id'=>$trade->id,
                'realized_profit_loss'=>$realized,
                'realized_return_percent'=>$basis > 0 ? ($realized/$basis)*100 : 0,
                'status'=>$remaining <= 0 ? 'closed' : 'open',
                'exit_reason'=>$remaining <= 0 ? $reason : $position->exit_reason,
                'closed_at'=>$remaining <= 0 ? now() : null,
            ]);

            if ($linkTransaction && $trade->exists && ! $trade->trade_position_id) {
                $trade->update(['trade_position_id'=>$position->id]);
            }

            $this->event(
                $position,
                $remaining <= 0 ? $reason : 'partial_close',
                $qty,$exitPrice,$pnl,$trade,
                $remaining <= 0 ? 'Position closed' : 'Position partially closed',
                $actorType,$actorId,
                ['remaining_quantity'=>$remaining]
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
            ->whereIn('status',['open','exit_queued'])
            ->where('open_quantity','>',0)
            ->orderBy('opened_at');

        if ($contextType) $query->where('context_type',$contextType);
        if ($contextId !== null) $query->where('context_id',$contextId);

        foreach ($query->get() as $position) {
            if ($remaining <= 0) break;

            $consume = min($remaining,(float)$position->open_quantity);

            // Create a lightweight proportional view of the actual sell transaction
            // so one generic sell can close more than one position without another market order.
            $shadow = clone $sell;
            $shadow->quantity = $consume;
            $shadow->total_amount = $consume * (float)$sell->price_per_share;

            // A single manual SELL may span multiple FIFO positions. The event
            // ledger keeps the exact attribution; do not overwrite the one
            // StockTransaction with several mutually-exclusive position ids.
            $this->applyExitTransaction($position,$shadow,$reason,$actorType,$actorId,false);
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
        if (! $position->is_open) throw new RuntimeException('Only open positions can be updated.');

        $sl = $this->nullablePercent($stopLossPercent);
        $tp = $this->nullablePercent($takeProfitPercent);
        $entry=(float)$position->entry_price;

        $position->update([
            'stop_loss_percent'=>$sl,
            'take_profit_percent'=>$tp,
            'stop_loss_price'=>$sl ? round($entry*(1-$sl/100),8) : null,
            'take_profit_price'=>$tp ? round($entry*(1+$tp/100),8) : null,
            'duration_minutes'=>$durationMinutes && $durationMinutes > 0 ? $durationMinutes : null,
            'expires_at'=>$durationMinutes && $durationMinutes > 0
                ? $position->opened_at->copy()->addMinutes($durationMinutes)
                : null,
        ]);

        $this->event($position,'risk_updated',null,null,null,null,'Risk controls updated',$actorType,$actorId,[
            'stop_loss_percent'=>$sl,
            'take_profit_percent'=>$tp,
            'duration_minutes'=>$durationMinutes,
        ]);

        return $position->refresh();
    }

    public function reenter(
        TradePosition $closed,
        ?float $quantity = null,
        string $actorType = 'user',
        ?int $actorId = null
    ): TradePosition {
        if ($closed->status !== 'closed') throw new RuntimeException('Only closed positions can be re-entered.');

        $closed->loadMissing(['user','stock']);
        $qty = $quantity && $quantity > 0 ? $quantity : (float)$closed->initial_quantity;

        $trade = $this->executor->buy(
            $closed->user,$closed->stock,$qty,'position_reentry',$closed->id,
            $closed->entryTransaction?->copy_strategy_id,$actorType,$actorId
        );

        $new = $this->openLongFromTrade($trade,[
            'stop_loss_percent'=>$closed->stop_loss_percent,
            'take_profit_percent'=>$closed->take_profit_percent,
            'duration_minutes'=>$closed->duration_minutes,
            'metadata'=>['reentered_from_position_id'=>$closed->id],
        ],$closed->context_type,$closed->context_id,$closed->id,$actorType,$actorId);

        $this->event($closed,'reentered',null,(float)$trade->price_per_share,null,$trade,'New position opened from this closed position',$actorType,$actorId,[
            'new_position_id'=>$new->id
        ]);

        return $new;
    }

    public function processOpenPositions(): array
    {
        $stats=['checked'=>0,'take_profit'=>0,'stop_loss'=>0,'time_exit'=>0,'queued'=>0,'failed'=>0];

        TradePosition::with(['user','stock','entryTransaction'])
            ->whereIn('status',['open','exit_queued'])
            ->where('open_quantity','>',0)
            ->orderBy('id')
            ->chunkById(100,function($positions) use (&$stats){
                foreach($positions as $position){
                    $stats['checked']++;
                    $price=(float)$position->stock->current_price;
                    $reason=null;

                    if($position->status==='exit_queued'){
                        $reason=$position->exit_reason ?: 'time_expiry';
                    } elseif($position->stop_loss_price && $price <= (float)$position->stop_loss_price){
                        $reason='stop_loss';
                    } elseif($position->take_profit_price && $price >= (float)$position->take_profit_price){
                        $reason='take_profit';
                    } elseif($position->expires_at && $position->expires_at->isPast()){
                        $reason='time_expiry';
                    }

                    if(! $reason) continue;

                    if(! $this->marketSession->isOpen()){
                        $position->update(['status'=>'exit_queued','exit_reason'=>$reason]);
                        $stats['queued']++;
                        continue;
                    }

                    try{
                        $trade=$this->close($position,$reason,null,'system',null);

                        // Strategy-provider automatic exits (SL / TP / time stop)
                        // must propagate to the exact copy strategy just like a
                        // manually submitted provider exit.
                        if($position->context_type==='copy_strategy'){
                            try{
                                app(CopyTradingService::class)->mirrorCompletedTrade($trade);
                            }catch(\Throwable $mirrorError){
                                \Log::warning('Automatic strategy exit mirroring failed',[
                                    'position_id'=>$position->id,
                                    'trade_id'=>$trade->id,
                                    'error'=>$mirrorError->getMessage(),
                                ]);
                            }
                        }

                        $stats[$reason] = ($stats[$reason] ?? 0) + 1;
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

    public function settleContext(string $contextType,int $contextId,string $reason='contract_expiry'): array
    {
        $positions=TradePosition::where('context_type',$contextType)
            ->where('context_id',$contextId)
            ->whereIn('status',['open','exit_queued'])
            ->where('open_quantity','>',0)
            ->get();

        $result=['open'=>$positions->count(),'closed'=>0,'queued'=>0,'failed'=>0];

        foreach($positions as $position){
            try{
                $this->close($position,$reason);
                $result['closed']++;
            }catch(\Throwable $e){
                if(str_contains(strtolower($e->getMessage()),'market is closed')) $result['queued']++;
                else $result['failed']++;
            }
        }

        return $result;
    }

    private function event(
        TradePosition $position,string $type,?float $quantity,?float $price,?float $pnl,
        ?StockTransaction $tx,?string $note,string $actorType,?int $actorId,array $metadata=[]
    ): void {
        $position->events()->create([
            'stock_transaction_id'=>$tx?->id,
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

    private function nullablePercent(mixed $value): ?float
    {
        if($value===null || $value==='') return null;
        $v=(float)$value;
        if($v<=0 || $v>100) throw new RuntimeException('Risk percentage must be greater than 0 and at most 100.');
        return $v;
    }
}
