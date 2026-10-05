<?php
declare(strict_types=1);

namespace App\Services\PaperTrading;

use App\Models\BrokerOrder;
use App\Models\CopyRelationship;
use App\Models\CopyStrategy;
use App\Models\CopyTradeExecution;
use App\Models\MarketExecutionTransaction;
use App\Models\TradePosition;
use App\Models\User;
use App\Services\MarketPriceRouter;
use App\Services\MarketSettlementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/** Explicit entry/exit intent; exact source-position linkage; atomic follower bookkeeping. */
final class PaperCopyTradingService
{
    public function __construct(private PaperBrokerService $broker, private PaperQuoteService $quotes,
        private MarketPriceRouter $prices, private MarketSettlementService $settlement) {}

    public function mirror(MarketExecutionTransaction $execution, ?int $strategyId = null): array
    {
        if (!config('paper_trading.copy_enabled', false)) {
            throw new RuntimeException('Paper copy trading is not enabled.');
        }
        // Use persisted authority rather than a mutable caller-supplied receipt object.
        $execution = MarketExecutionTransaction::with(['tradePosition', 'marketInstrument'])->findOrFail($execution->id);
        $position = $execution->tradePosition;
        $intent = $execution->metadata['order_intent'] ?? null;
        if ($execution->status !== 'completed' || ($execution->metadata['execution_model'] ?? null) !== PaperBrokerService::MODEL
            || !in_array($intent, ['open', 'close'], true) || !$position || !PaperBrokerService::owns($position)
            || (int)$position->user_id !== (int)$execution->user_id
            || (int)$position->market_instrument_id !== (int)$execution->market_instrument_id
            || $position->context_type !== 'copy_strategy') {
            throw new RuntimeException('Paper provider receipt authority is invalid.');
        }
        $expectedSide = $intent === 'open'
            ? ($position->direction === 'long' ? 'buy' : 'sell')
            : PositionMath::closingSide($position->direction);
        if ($execution->side !== $expectedSide || ($intent === 'open' && (int)$position->entry_market_execution_transaction_id !== (int)$execution->id)) {
            throw new RuntimeException('Paper provider receipt intent does not match position direction.');
        }
        $attributed = (int)$position->context_id;
        if ($strategyId !== null && $strategyId !== $attributed) {
            throw new RuntimeException('Copy strategy does not match the provider position.');
        }
        $strategy = CopyStrategy::with('profile')->findOrFail($attributed);
        if ((int)$strategy->profile?->user_id !== (int)$execution->user_id) {
            throw new RuntimeException('Copy strategy owner does not match the provider receipt.');
        }
        $stats = ['completed'=>0, 'replayed'=>0, 'skipped'=>0, 'failed'=>0];
        $query = CopyRelationship::where('provider_id', $execution->user_id)->where('copy_strategy_id', $strategy->id);
        // Exits must reach linked exposure even after a relationship expires or is paused.
        // Entry eligibility is checked again inside the follower transaction.
        if ($intent === 'open') {
            $query->where('status', 'active')->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at','>',now()));
        } else {
            $query->where(function ($q) use ($position, $execution) {
                $q->whereHas('positions', fn ($p) => $p->where('source_position_id',$position->id)
                    ->where('metadata->execution_model',PaperBrokerService::MODEL))
                  ->orWhereHas('executions', fn ($e) => $e->where('provider_market_execution_transaction_id',$execution->id));
            });
        }
        foreach ($query->get() as $relationship) {
            try {
                $outcome = $this->mirrorOne($relationship, $strategy, $execution);
                $stats[$outcome]++;
            } catch (Throwable $error) {
                $stats['failed']++;
                Log::warning('Paper copy execution deferred', ['relationship_id'=>$relationship->id,
                    'provider_execution_id'=>$execution->id, 'error_class'=>get_class($error)]);
                // A competing successful retry must not be overwritten with a failure.
                try { DB::transaction(function () use ($relationship, $execution) {
                    User::query()->lockForUpdate()->findOrFail($relationship->follower_id);
                    $locked = CopyRelationship::query()->lockForUpdate()->findOrFail($relationship->id);
                    $old = $this->recordQuery($locked, $execution)->first();
                    if ($old?->status !== 'completed') {
                        $this->record($locked, $execution, null, 0, 'failed', 'Paper copy execution deferred; retry after resolving quote, funds or account eligibility.');
                    }
                }, 3); } catch (Throwable $recordError) {
                    Log::warning('Paper copy failure record could not be stored', ['relationship_id'=>$relationship->id,
                        'provider_execution_id'=>$execution->id, 'error_class'=>get_class($recordError)]);
                }
            }
        }
        return $stats;
    }

    /** Receipts are durable retry authority even after the provider position is closed. */
    public function retryExits(?array $sourcePositionIds = null): array
    {
        if (!config('paper_trading.copy_enabled', false)) {
            throw new RuntimeException('Paper copy retry is not enabled.');
        }
        $stats = ['receipts'=>0, 'completed'=>0, 'replayed'=>0, 'skipped'=>0, 'failed'=>0];
        $query = MarketExecutionTransaction::where('status','completed')
            ->where('metadata->execution_model',PaperBrokerService::MODEL)
            ->where('metadata->order_intent','close')
            ->whereHas('tradePosition',fn ($p) => $p->where('context_type','copy_strategy'));
        if ($sourcePositionIds !== null) { $query->whereIn('trade_position_id',$sourcePositionIds); }
        $upperId = (clone $query)->max('id');
        if ($upperId === null) { return $stats; }
        $query->where('id','<=',$upperId)->chunkById(100,function ($receipts) use (&$stats) {
            foreach ($receipts as $receipt) {
                $stats['receipts']++;
                try {
                    $result = $this->mirror($receipt);
                    foreach (['completed','replayed','skipped','failed'] as $key) { $stats[$key]+=$result[$key]; }
                } catch (Throwable $error) {
                    $stats['failed']++;
                    Log::warning('Paper provider close retry deferred',['execution_id'=>$receipt->id,'error_class'=>get_class($error)]);
                }
            }
        });
        return $stats;
    }

    private function mirrorOne(CopyRelationship $relationship, CopyStrategy $strategy, MarketExecutionTransaction $provider): string
    {
        return DB::transaction(function () use ($relationship, $strategy, $provider) {
            $follower = User::query()->lockForUpdate()->findOrFail($relationship->follower_id);
            $relationship = CopyRelationship::query()->lockForUpdate()->findOrFail($relationship->id);
            if ((int)$relationship->provider_id !== (int)$provider->user_id || (int)$relationship->copy_strategy_id !== (int)$strategy->id) {
                throw new RuntimeException('Copy relationship attribution changed.');
            }
            if ($this->recordQuery($relationship, $provider)->where('status','completed')->exists()) { return 'replayed'; }
            $wallet = $follower->wallet()->lockForUpdate()->firstOrFail();
            $instrument = $provider->marketInstrument;
            $source = $provider->tradePosition;
            $intent = $provider->metadata['order_intent'];
            $key = 'paper-copy:'.$relationship->id.':provider:'.$provider->id;
            $context = ['context_type'=>'copy_relationship', 'context_id'=>$relationship->id,
                'source_position_id'=>$source->id, 'actor_type'=>'system', 'execution_source'=>'paper_copy',
                'exit_reason'=>'provider_exit', 'metadata'=>['copy_strategy_id'=>$strategy->id,
                    'provider_market_execution_transaction_id'=>$provider->id, 'provider_position_id'=>$source->id]];
            if ($intent === 'open') {
                $strategy->refresh()->load('profile');
                if ($relationship->status !== 'active' || ($relationship->ends_at && $relationship->ends_at->lte(now()))
                    || !$source->is_open || !$strategy->is_active || !$strategy->profile?->approved_at
                    || !$strategy->profile?->is_accepting_copiers || !$follower->kyc?->isApproved()) {
                    $this->record($relationship,$provider,null,0,'skipped','New paper copy entry is not eligible.');
                    return 'skipped';
                }
                if ($this->prices->activeMarketplace() !== $provider->marketplace) {
                    throw new RuntimeException('Provider marketplace differs from active opening marketplace.');
                }
                $currency = strtoupper((string)$wallet->currency);
                $capital = $this->settlement->convertFiat((float)$provider->settlement_amount,
                    $provider->settlement_currency,$currency,true);
                $budget = min($capital*(float)$relationship->copy_ratio_percent/100,
                    (float)$relationship->max_trade_amount, max(0,(float)$relationship->allocation_limit-(float)$relationship->used_amount));
                $budgetMinor = (int)floor(round($budget,8)*100+1e-8);
                if ($budgetMinor < 1) {
                    $this->record($relationship,$provider,null,0,'skipped','Copy allocation or trade cap reached.');
                    return 'skipped';
                }
                $price = $this->quotes->execution($instrument,$provider->side,$provider->marketplace);
                $oneUnit = $instrument->isStock()
                    ? $this->settlement->convertFiat($price,'USD',$currency,true)
                    : $this->settlement->amountForBaseUnits($instrument,1.0,$price,$currency,true);
                if (!is_finite($oneUnit) || $oneUnit <= 0) { throw new RuntimeException('Paper copy sizing is invalid.'); }
                $quantity = floor(($budgetMinor/100/$oneUnit)*100000000)/100000000;
                $order = $this->broker->open($follower,$instrument,$provider->side,$quantity,'units',$key,
                    ['stop_loss_percent'=>$source->stop_loss_percent, 'take_profit_percent'=>$source->take_profit_percent,
                        'duration_minutes'=>$source->duration_minutes],$context);
                $spentMinor = (int)round((float)$order->execution->settlement_amount*100);
                if ($spentMinor > $budgetMinor) { throw new RuntimeException('Execution moved beyond the copy collateral budget.'); }
                $relationship->update(['used_amount'=>round((float)$relationship->used_amount+$spentMinor/100,2)]);
                $requested = $budgetMinor/100;
            } else {
                $position = TradePosition::query()->lockForUpdate()->where('user_id',$follower->id)
                    ->where('context_type','copy_relationship')->where('context_id',$relationship->id)
                    ->where('source_position_id',$source->id)->where('market_instrument_id',$instrument->id)
                    ->where('marketplace',$provider->marketplace)->where('metadata->execution_model',PaperBrokerService::MODEL)
                    ->whereIn('status',['open','exit_queued'])->where('open_quantity','>',0)->first();
                if (!$position) {
                    $this->record($relationship,$provider,null,0,'skipped','No exact linked paper follower exposure remains.');
                    return 'skipped';
                }
                if ($position->direction !== $source->direction) { throw new RuntimeException('Follower direction differs from source position.'); }
                $before = (float)($provider->metadata['position_open_quantity_before'] ?? 0);
                $after = (float)($provider->metadata['position_open_quantity_after'] ?? -1);
                if ($before <= 0 || $after < 0 || (float)$provider->quantity > $before
                    || abs(($before-$after)-(float)$provider->quantity) > 0.000000011) {
                    throw new RuntimeException('Provider close lacks an authoritative exposure snapshot.');
                }
                // Close proportionally to provider exposure, not current trade caps or price notional.
                $quantity = $after === 0.0 ? (float)$position->open_quantity
                    : floor(((float)$position->open_quantity*(float)$provider->quantity/$before)*100000000)/100000000;
                $requested = (float)($position->metadata['remaining_collateral_minor'] ?? 0)/100;
                $order = $this->broker->close($follower,$position,$quantity,$key,$context);
                // PaperBrokerService releases relationship allocation in the same settlement transaction.
            }
            $this->record($relationship,$provider,$order,$requested,'completed',null);
            return 'completed';
        }, 3);
    }

    private function recordQuery(CopyRelationship $relationship, MarketExecutionTransaction $provider)
    {
        return CopyTradeExecution::where('copy_relationship_id',$relationship->id)
            ->where('provider_market_execution_transaction_id',$provider->id);
    }

    private function record(CopyRelationship $relationship, MarketExecutionTransaction $provider,
        ?BrokerOrder $order, float $requested, string $status, ?string $reason): CopyTradeExecution
    {
        $execution = $order?->execution;
        return CopyTradeExecution::updateOrCreate(['copy_relationship_id'=>$relationship->id,
            'provider_market_execution_transaction_id'=>$provider->id], [
                'market_instrument_id'=>$provider->market_instrument_id,
                'provider_broker_order_id'=>BrokerOrder::where('market_execution_transaction_id',$provider->id)->value('id'),
                'follower_broker_order_id'=>$order?->id, 'follower_market_execution_transaction_id'=>$execution?->id,
                'provider_stock_transaction_id'=>null, 'follower_stock_transaction_id'=>null,
                'action'=>$provider->side, 'requested_amount'=>round($requested,2),
                'executed_amount'=>$execution ? (float)$execution->settlement_amount : 0,
                'status'=>$status, 'failure_reason'=>$reason, 'executed_at'=>$execution?->executed_at ?? now(),
            ]);
    }
}
