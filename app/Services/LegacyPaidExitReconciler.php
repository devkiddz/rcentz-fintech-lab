<?php
declare(strict_types=1);
namespace App\Services;

use App\Models\MarketExecutionTransaction;
use App\Models\MarketHolding;
use App\Models\TradePosition;
use App\Models\TradePositionEvent;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Local record repair only: never submits a trade or writes a wallet transaction. */
final class LegacyPaidExitReconciler
{
    public function __construct(private MarketPositionService $positions) {}

    public function reconcile(User $user, int $positionId, int $exitId): array
    {
        $this->ensure(app()->environment('local'), 'Record repair is restricted to the local project.');
        return DB::transaction(function () use ($user, $positionId, $exitId) {
            $owner = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $wallet = $owner->wallet()->lockForUpdate()->firstOrFail();
            $position = TradePosition::whereKey($positionId)->lockForUpdate()->firstOrFail();
            $exit = MarketExecutionTransaction::whereKey($exitId)->lockForUpdate()->firstOrFail();
            $entry = MarketExecutionTransaction::whereKey($position->entry_market_execution_transaction_id)->lockForUpdate()->firstOrFail();
            $this->ensure((int)$position->user_id === (int)$owner->id && !$owner->isAdmin(), 'Position ownership mismatch.');
            $this->ensure($position->direction === 'long' && $position->context_type === 'broker_order'
                && ($position->metadata['execution_model'] ?? '') === 'cash_collateralized_long_no_leverage', 'Only legacy broker Forex Long records are eligible.');
            $this->receipt($entry, $position, $wallet, 'buy');
            $this->receipt($exit, $position, $wallet, 'sell');
            $marker = $position->metadata['paid_exit_reconciliation'] ?? null;
            if (($marker['execution_id'] ?? null) === $exitId) {
                $this->ensure($position->status === 'closed' && (float)$position->open_quantity === 0.0
                    && (int)$exit->trade_position_id === $positionId
                    && $position->events()->where('market_execution_transaction_id',$exitId)->where('event_type','legacy_reconciliation')->count() === 1,
                    'Previously repaired record is inconsistent.');
                return ['already_reconciled'=>true,'position_id'=>$positionId,'execution_id'=>$exitId,'wallet_unchanged'=>true];
            }
            $this->ensure($position->is_open && (float)$exit->quantity > 0
                && abs((float)$position->open_quantity-(float)$exit->quantity) < 0.00000001, 'Exit must exactly cover the remaining position.');
            $this->ensure($exit->trade_position_id === null && (float)($exit->metadata['position_reconciled_quantity'] ?? 0) === 0.0
                && !TradePositionEvent::where('market_execution_transaction_id',$exitId)->exists(), 'Paid exit has already been allocated.');
            $this->ensure(!MarketHolding::where('user_id',$owner->id)->where('market_instrument_id',$position->market_instrument_id)
                ->where('marketplace',$position->marketplace)->exists(), 'Existing holding makes this repair ambiguous.');
            $legacyOpen = TradePosition::where('user_id',$owner->id)->where('market_instrument_id',$position->market_instrument_id)
                ->where('marketplace',$position->marketplace)->whereIn('status',['open','exit_queued'])->where('open_quantity','>',0)
                ->where('metadata->execution_model','cash_collateralized_long_no_leverage')->count();
            $this->ensure($legacyOpen === 1, 'Multiple legacy positions require a separate allocation review.');
            $events = $position->events()->where('event_type','!=','entry')->get();
            $exitReceipts = [$exit];
            $closedQuantity = 0.0;
            $seen = [];
            foreach ($events as $event) {
                $id = (int)$event->market_execution_transaction_id;
                $this->ensure($id > 0 && !isset($seen[$id]) && (float)$event->quantity > 0, 'Exit events are ambiguous.');
                $seen[$id] = true;
                $paid = MarketExecutionTransaction::whereKey($id)->lockForUpdate()->firstOrFail();
                $this->receipt($paid, $position, $wallet, 'sell');
                $this->ensure(abs((float)$paid->quantity-(float)$event->quantity)<0.00000001, 'Existing exit allocation must cover its receipt exactly.');
                $closedQuantity += (float)$event->quantity;
                $exitReceipts[] = $paid;
            }
            $this->ensure(abs((float)$entry->quantity-(float)$position->initial_quantity)<0.00000001
                && abs($closedQuantity+(float)$exit->quantity-(float)$position->initial_quantity)<0.00000001,
                'Entry and all paid exits must conserve quantity.');
            $collateral = 0.0;
            foreach ($exitReceipts as $paid) { $collateral += (float)($paid->metadata['collateral_released'] ?? 0); }
            $this->ensure(abs($collateral-(float)$entry->settlement_amount)<0.00000001, 'Paid exits do not prove full collateral release.');
            $before = $this->walletSnapshot($wallet);
            $this->positions->consumeSellExecution($exit, 'legacy_reconciliation', 'broker_order',
                (int)$position->context_id, 'system', (int)$owner->id);
            $position->refresh(); $exit->refresh();
            $this->ensure($position->status === 'closed' && (float)$position->open_quantity === 0.0
                && (float)($exit->metadata['position_reconciled_quantity'] ?? 0) === (float)$exit->quantity,
                'Position reconciliation did not complete.');
            $latest = $exit;
            foreach ($exitReceipts as $paid) {
                if ($paid->executed_at->gt($latest->executed_at)) { $latest = $paid; }
            }
            $metadata = $position->metadata ?? [];
            $metadata['paid_exit_reconciliation'] = ['execution_id'=>$exitId,'reason'=>'already_paid_exit_missing_position_event',
                'reconciled_at'=>now()->toIso8601String(),'wallet_unchanged'=>true];
            $position->update(['closed_at'=>$latest->executed_at,'last_exit_market_execution_transaction_id'=>$latest->id,'metadata'=>$metadata]);
            $this->ensure($this->walletSnapshot($wallet) === $before, 'Wallet changed; record repair must be rolled back.');
            return ['already_reconciled'=>false,'position_id'=>$positionId,'execution_id'=>$exitId,
                'status'=>$position->status,'remaining_quantity'=>$position->open_quantity,'wallet_unchanged'=>true];
        });
    }

    private function receipt(MarketExecutionTransaction $receipt, TradePosition $position, $wallet, string $side): void
    {
        $this->ensure((int)$receipt->user_id === (int)$position->user_id
            && (int)$receipt->market_instrument_id === (int)$position->market_instrument_id
            && $receipt->marketplace === $position->marketplace && $receipt->side === $side
            && $receipt->status === 'completed' && $receipt->native_type === 'forex_cash_collateral_execution'
            && ($receipt->metadata['execution_model'] ?? '') === 'cash_collateralized_long_no_leverage'
            && $receipt->settlement_currency === $wallet->currency && $receipt->executed_at !== null,
            'Settlement receipt does not match the legacy position.');
        $tx = WalletTransaction::whereKey($receipt->wallet_transaction_id)->lockForUpdate()->firstOrFail();
        $this->ensure((int)$tx->wallet_id === (int)$wallet->id && $tx->status === 'completed'
            && $tx->direction === ($side === 'buy' ? 'debit' : 'credit')
            && abs((float)$tx->amount-round((float)$receipt->settlement_amount,2))<0.001,
            'Completed wallet payment is missing or mismatched.');
    }

    private function walletSnapshot($wallet): string
    {
        return json_encode([$wallet->fresh()->getAttributes(), WalletTransaction::where('wallet_id',$wallet->id)
            ->orderBy('id')->get()->map(fn($tx)=>$tx->getAttributes())->all()], JSON_THROW_ON_ERROR);
    }

    private function ensure(bool $condition, string $message): void
    {
        if (!$condition) { throw new RuntimeException($message); }
    }
}
