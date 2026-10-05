<?php
declare(strict_types=1);

namespace App\Services\PaperTrading;

use App\Models\BrokerOrder;
use App\Models\BrokerOrderEvent;
use App\Models\MarketExecutionTransaction;
use App\Models\MarketInstrument;
use App\Models\TradePosition;
use App\Models\TradePositionEvent;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FinancialActivityService;
use App\Services\MarketPriceRouter;
use App\Services\MarketSettlementService;
use App\Support\ProductionDemoGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/** 1x isolated, collateral-limited synthetic positions. Existing spot records remain unchanged. */
final class PaperBrokerService
{
    public const MODEL = 'paper_v1';

    public function __construct(
        private PaperQuoteService $quotes,
        private MarketPriceRouter $prices,
        private MarketSettlementService $settlement,
        private ProductionDemoGuard $demo,
        private FinancialActivityService $activity
    ) {}

    public static function owns(TradePosition $position): bool
    {
        return ($position->metadata['execution_model'] ?? null) === self::MODEL;
    }

    /** Read-only estimate. Opening rechecks prices and funds under wallet locks. */
    public function estimate(User $user, MarketInstrument $instrument, string $side, float $size, string $mode): array
    {
        if (!config('paper_trading.enabled', false)) { throw new RuntimeException('Position opening is currently unavailable.'); }
        PositionMath::directionForOpeningSide($side);
        $this->positive($size, 'Order size');
        $wallet = $user->wallet()->firstOrFail();
        $currency = strtoupper((string)$wallet->currency);
        $price = $this->quotes->execution($instrument, $side, $this->prices->activeMarketplace());
        $quantity = $this->units($instrument, $size, $mode, $price, $currency);
        $collateral = $this->minor($this->notional($instrument, $quantity, $price, $currency), true);
        if ($collateral < 1) { throw new RuntimeException('Collateral must be at least one minor currency unit.'); }
        $fee = $this->fee($collateral);
        $available = $this->minor((float)$wallet->balance)-$this->minor((float)$wallet->reserved_balance);
        return ['currency'=>$currency,'quote_currency'=>$instrument->isStock() ? 'USD' : $instrument->quote_asset,'price'=>$price,'units'=>$quantity,'balance'=>(float)$wallet->balance,
            'reserved'=>(float)$wallet->reserved_balance,'available'=>$available/100,'collateral'=>$collateral/100,
            'entry_fee'=>$fee/100,'required'=>($collateral+$fee)/100,'remaining'=>($available-$collateral-$fee)/100,
            'sufficient'=>$available >= $collateral+$fee,'estimated_at'=>now()->toIso8601String()];
    }

    public function open(User $user, MarketInstrument $instrument, string $side, float $size,
        string $mode, string $key, array $risk = [], array $context = []): BrokerOrder
    {
        if (!config('paper_trading.enabled', false)) {
            throw new RuntimeException('Position opening is currently unavailable.');
        }
        $key = trim($key);
        $this->guard($user, $key);
        $direction = PositionMath::directionForOpeningSide($side);
        $this->positive($size, 'Order size');
        $marketplace = $this->prices->activeMarketplace();
        $fingerprint = $this->fingerprint(['open', $instrument->id, $side, $size, $mode, $marketplace, $risk, $context]);
        if ($replay = $this->replay($user, $key, $fingerprint)) { return $replay; }
        $price = $this->quotes->execution($instrument, $side, $marketplace);
        $stopPercent = isset($risk['stop_loss_percent']) ? (float)$risk['stop_loss_percent'] : null;
        $takePercent = isset($risk['take_profit_percent']) ? (float)$risk['take_profit_percent'] : null;
        $stopPrice = isset($risk['stop_loss_price']) ? (float)$risk['stop_loss_price'] : null;
        $takePrice = isset($risk['take_profit_price']) ? (float)$risk['take_profit_price'] : null;
        $levels = $this->selectedRiskLevels($direction, $price, $stopPercent, $takePercent, $stopPrice, $takePrice);
        $duration = $this->duration($risk['duration_minutes'] ?? null);

        return DB::transaction(function () use ($user, $instrument, $side, $size, $mode, $key, $risk,
            $context, $direction, $marketplace, $fingerprint, $price, $levels, $duration) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->guard($lockedUser, $key);
            if ($replay = $this->replay($lockedUser, $key, $fingerprint)) { return $replay; }
            $wallet = $lockedUser->wallet()->lockForUpdate()->firstOrFail();
            $currency = strtoupper((string)$wallet->currency);
            $quantity = $this->units($instrument, $size, $mode, $price, $currency);
            $collateral = $this->minor($this->notional($instrument, $quantity, $price, $currency), true);
            if ($collateral < 1) { throw new RuntimeException('Collateral must be at least one minor currency unit.'); }
            $fee = $this->fee($collateral);
            $balance = $this->minor((float)$wallet->balance);
            $reserved = $this->minor((float)$wallet->reserved_balance);
            if ($balance - $reserved < $collateral + $fee) {
                throw new RuntimeException('Insufficient available funds for collateral and entry fee.');
            }
            $before = $this->snapshot($wallet);
            $wallet->update(['balance'=>($balance-$fee)/100, 'reserved_balance'=>($reserved+$collateral)/100]);
            $order = $this->order($lockedUser, $instrument, $side, $size, $mode, $key, $marketplace,
                $fingerprint, 'open', $context, $risk);
            $metadata = array_merge($risk['metadata'] ?? [], $context['metadata'] ?? [], [
                'execution_model'=>self::MODEL, 'order_intent'=>'open', 'asset_class'=>$instrument->asset_class,
                'settlement_currency'=>$currency, 'initial_collateral_minor'=>$collateral,
                'remaining_collateral_minor'=>$collateral, 'entry_fee_minor'=>$fee,
                'margin_policy'=>'isolated_1x_collateral_limited',
            ]);
            $position = TradePosition::create(array_merge([
                'user_id'=>$lockedUser->id, 'market_instrument_id'=>$instrument->id, 'stock_id'=>null,
                'context_type'=>$context['context_type'] ?? 'broker_order',
                'context_id'=>$context['context_id'] ?? $order->id,
                'source_position_id'=>$context['source_position_id'] ?? null,
                'marketplace'=>$marketplace, 'direction'=>$direction, 'initial_quantity'=>$quantity,
                'open_quantity'=>$quantity, 'entry_price'=>$price,
                'stop_loss_percent'=>$risk['stop_loss_percent'] ?? null,
                'take_profit_percent'=>$risk['take_profit_percent'] ?? null,
                'duration_minutes'=>$duration, 'opened_at'=>now(),
                'expires_at'=>$duration ? now()->addMinutes($duration) : null,
                'status'=>'open', 'realized_profit_loss'=>-$fee/100,
                'realized_return_percent'=>-$fee/$collateral*100, 'metadata'=>$metadata,
            ], $levels));
            $walletTx = $fee > 0 ? $this->walletDelta($wallet, -$fee, 'Position entry fee', $order) : null;
            $execution = $this->receipt($order, $position, $quantity, $price, $collateral/100,
                $fee/100, -$fee/100, $walletTx?->id, ['collateral_reserved_minor'=>$collateral]);
            $position->update(['entry_market_execution_transaction_id'=>$execution->id]);
            $this->event($position, $execution, 'opened', $context);
            $this->activity->record($lockedUser, 'paper_position_opened', ucfirst($direction).' position opened',
                'Position opened and collateral reserved.', $order->public_id, 'completed',
                null, $collateral/100, $wallet, $walletTx, null, $before,
                ['position_id'=>$position->id, 'broker_order_id'=>$order->id, 'execution_model'=>self::MODEL]);
            return $this->fill($order, $execution);
        }, 3);
    }

    public function close(User $user, TradePosition $position, ?float $quantity, string $key,
        array $context = []): BrokerOrder
    {
        return $this->closeOrder($user, $position, $quantity, $key, $context, false)
            ?? throw new RuntimeException('The position close could not be completed.');
    }

    /** Recheck the trigger under the same locks as financial settlement. */
    public function closeTriggered(User $user, TradePosition $position, string $key): ?BrokerOrder
    {
        if (!config('paper_trading.lifecycle_enabled', false)) {
            throw new RuntimeException('Automatic position management is currently unavailable.');
        }
        return $this->closeOrder($user, $position, null, $key,
            ['actor_type'=>'system', 'execution_source'=>'paper_lifecycle'], true);
    }

    private function closeOrder(User $user, TradePosition $position, ?float $quantity, string $key,
        array $context, bool $automatic): ?BrokerOrder
    {
        $key = trim($key);
        $this->guard($user, $key);
        if (!self::owns($position) || (int)$position->user_id !== (int)$user->id) {
            throw new RuntimeException('This position is not available to your account.');
        }
        $fingerprint = $this->fingerprint($automatic
            ? ['automatic_close', $position->id, $context]
            : ['close', $position->id, $quantity, $context]);
        if ($replay = $this->replay($user, $key, $fingerprint)) { return $replay; }
        $instrument = $position->marketInstrument()->firstOrFail();
        return DB::transaction(function () use ($user, $position, $quantity, $key, $context,
            $fingerprint, $instrument, $automatic) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->guard($lockedUser, $key);
            if ($replay = $this->replay($lockedUser, $key, $fingerprint)) { return $replay; }
            $wallet = $lockedUser->wallet()->lockForUpdate()->firstOrFail();
            $locked = TradePosition::query()->lockForUpdate()->findOrFail($position->id);
            if (!self::owns($locked) || (int)$locked->user_id !== (int)$lockedUser->id) {
                throw new RuntimeException('This position is no longer available to close.');
            }
            if (!$locked->is_open) {
                if ($automatic) { return null; }
                throw new RuntimeException('This position is no longer available to close.');
            }
            $contexts = ['broker_order', 'manual_trade'];
            if (config('paper_trading.copy_enabled', false)) { $contexts = array_merge($contexts, ['copy_strategy','copy_relationship']); }
            if (config('paper_trading.bots_enabled', false)) { $contexts[] = 'trading_bot'; }
            if ($automatic && !in_array($locked->context_type, $contexts, true)) {
                throw new RuntimeException('Automatic exits for this position are currently unavailable.');
            }
            $side = PositionMath::closingSide($locked->direction);
            $price = $this->quotes->execution($instrument, $side, $locked->marketplace);
            if ($automatic) {
                $reason = $this->automaticReason($locked, $instrument, $price);
                if ($reason === null) { return null; }
                $context['exit_reason'] = $reason;
            }
            $open = (float)$locked->open_quantity;
            $close = $quantity ?? $open;
            $this->positive($close, 'Close quantity');
            if ($close > $open || abs(round($close, 8)-$close) > 1e-12) {
                throw new RuntimeException('Close quantity exceeds exposure or supported precision.');
            }
            $meta = $locked->metadata;
            if (strtoupper((string)$wallet->currency) !== $meta['settlement_currency']) {
                throw new RuntimeException('Wallet currency changed while the paper position was open.');
            }
            $split = PositionMath::splitCollateral((int)$meta['remaining_collateral_minor'], $open, $close);
            $released = $split['released'];
            $rawPnl = $this->pnlMinor($locked, $instrument, $price, $close, true);
            // Synthetic isolated margin deliberately limits losses to released collateral.
            // The uncollectible gap remains explicit in the execution receipt.
            $grossPnl = max(-$released, $rawPnl);
            $requestedFee = $this->fee($this->minor($this->notional($instrument, $close, $price, $meta['settlement_currency'])));
            $fee = min($requestedFee, max(0, $released+$grossPnl));
            $netPnl = $grossPnl-$fee;
            $balance = $this->minor((float)$wallet->balance);
            $reserved = $this->minor((float)$wallet->reserved_balance);
            if ($reserved < $released || $balance < $reserved) {
                throw new RuntimeException('Wallet reservation invariant failed; reconciliation is required.');
            }
            $before = $this->snapshot($wallet);
            $wallet->update(['balance'=>($balance+$netPnl)/100, 'reserved_balance'=>($reserved-$released)/100]);
            if ($locked->context_type === 'copy_relationship') {
                $relationship = \App\Models\CopyRelationship::query()->lockForUpdate()
                    ->where('follower_id', $lockedUser->id)->findOrFail($locked->context_id);
                $used = $this->minor((float)$relationship->used_amount);
                if ($used < $released) {
                    throw new RuntimeException('Copy allocation invariant failed; reconciliation is required.');
                }
                $relationship->update(['used_amount'=>($used-$released)/100]);
            }
            $order = $this->order($lockedUser, $instrument, $side, $close, 'units', $key,
                $locked->marketplace, $fingerprint, 'close', $context, [], $locked->id);
            $remaining = round($open-$close, 8);
            $closedBefore = (float)$locked->initial_quantity-$open;
            $averageExit = (($closedBefore*(float)($locked->average_exit_price ?? 0))+$close*$price)/($closedBefore+$close);
            $realized = round((float)$locked->realized_profit_loss+$netPnl/100, 2);
            $meta['remaining_collateral_minor'] = $split['remaining'];
            $locked->update([
                'open_quantity'=>$remaining, 'average_exit_price'=>$averageExit,
                'realized_profit_loss'=>$realized,
                'realized_return_percent'=>$realized/((int)$meta['initial_collateral_minor']/100)*100,
                'status'=>$remaining > 0 ? 'open' : 'closed',
                'closed_at'=>$remaining > 0 ? null : now(),
                'exit_reason'=>$remaining > 0 ? null : ($context['exit_reason'] ?? 'customer_close'),
                'metadata'=>$meta,
            ]);
            $walletTx = $netPnl !== 0 ? $this->walletDelta($wallet, $netPnl, 'Position settlement', $order) : null;
            $execution = $this->receipt($order, $locked, $close, $price, $released/100,
                $fee/100, $netPnl/100, $walletTx?->id, [
                    'collateral_released_minor'=>$released, 'raw_profit_loss_minor'=>$rawPnl,
                    'position_open_quantity_before'=>$open, 'position_open_quantity_after'=>$remaining,
                    'uncollectible_gap_minor'=>max(0, -$released-$rawPnl),
                ]);
            $locked->update(['last_exit_market_execution_transaction_id'=>$execution->id]);
            $this->event($locked, $execution, $remaining > 0 ? 'partially_closed' : 'closed', $context);
            $this->activity->record($lockedUser, 'paper_position_closed', 'Position settled',
                'Collateral released and net profit/loss settled.', $order->public_id, 'completed',
                $netPnl >= 0 ? 'credit' : 'debit', abs($netPnl)/100, $wallet, $walletTx,
                null, $before, ['position_id'=>$locked->id, 'broker_order_id'=>$order->id, 'execution_model'=>self::MODEL]);
            return $this->fill($order, $execution);
        }, 3);
    }

    private function automaticReason(TradePosition $position, MarketInstrument $instrument, float $price): ?string
    {
        $remaining = (int)($position->metadata['remaining_collateral_minor'] ?? 0);
        if ($remaining < 1) {
            throw new RuntimeException('Position collateral is unavailable. Please contact support.');
        }
        if ($this->pnlMinor($position, $instrument, $price, (float)$position->open_quantity, true) <= -$remaining) {
            return 'margin_exhausted';
        }
        $risk = PositionMath::triggeredExit($position->direction, $price,
            $position->stop_loss_price === null ? null : (float)$position->stop_loss_price,
            $position->take_profit_price === null ? null : (float)$position->take_profit_price);
        if ($risk !== null) { return $risk; }
        if ($position->expires_at && $position->expires_at->lte(now())) { return 'time_expiry'; }
        if ($position->context_type === 'trading_bot') {
            $subscription = \App\Models\BotSubscription::where('trading_bot_id',$position->context_id)->first();
            if (!$subscription || (int)$subscription->user_id !== (int)$position->user_id
                || in_array($subscription->status,['cancelled','expired'],true)
                || ($subscription->ends_at && $subscription->ends_at->lte(now()))) {
                return 'bot_subscription_expiry';
            }
        }
        if ($position->context_type === 'copy_relationship') {
            $relationship = \App\Models\CopyRelationship::find($position->context_id);
            if (!$relationship || (int)$relationship->follower_id !== (int)$position->user_id) {
                throw new RuntimeException('Copy relationship authority is unavailable.');
            }
            if (in_array($relationship->status,['stopped','completed','settling','settlement_failed'],true)
                || ($relationship->ends_at && $relationship->ends_at->lte(now()))) {
                return 'copy_contract_expiry';
            }
        }
        return null;
    }

    public function unrealized(TradePosition $position): float
    {
        if (!$position->is_open) { return 0.0; }
        $instrument = $position->marketInstrument()->firstOrFail();
        $price = $this->quotes->mark($instrument, $position->marketplace);
        $minor = $this->pnlMinor($position, $instrument, $price, (float)$position->open_quantity, false);
        return max(-(int)$position->metadata['remaining_collateral_minor'], $minor)/100;
    }

    public function updateRisk(User $user, TradePosition $position, ?float $sl, ?float $tp, ?int $duration, ?float $stopPrice = null, ?float $takePrice = null): TradePosition
    {
        $this->guard($user, 'risk-update');
        $duration = $this->duration($duration);
        return DB::transaction(function () use ($user, $position, $sl, $tp, $duration, $stopPrice, $takePrice) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->guard($lockedUser, 'risk-update');
            $locked = TradePosition::query()->lockForUpdate()->findOrFail($position->id);
            if (!self::owns($locked) || !$locked->is_open || (int)$locked->user_id !== (int)$user->id) {
                throw new RuntimeException('Position is unavailable for risk update.');
            }
            $levels = $this->selectedRiskLevels($locked->direction, (float)$locked->entry_price, $sl, $tp, $stopPrice, $takePrice);
            $locked->update(array_merge($levels, ['stop_loss_percent'=>$sl, 'take_profit_percent'=>$tp,
                'duration_minutes'=>$duration, 'expires_at'=>$duration ? $locked->opened_at->copy()->addMinutes($duration) : null]));
            return $locked->refresh();
        }, 3);
    }

    private function selectedRiskLevels(string $direction, float $entry, ?float $sl, ?float $tp, ?float $stopPrice, ?float $takePrice): array
    {
        if ($stopPrice !== null || $takePrice !== null) {
            if ($sl !== null || $tp !== null) { throw new InvalidArgumentException('Choose either percentages or exact prices for risk controls.'); }
            return PositionMath::exactRiskLevels($direction, $entry, $stopPrice, $takePrice);
        }
        return PositionMath::riskLevels($direction, $entry, $sl, $tp);
    }

    private function guard(User $user, string $key): void
    {
        $this->demo->assertMutationAllowed($user, 'paper trading');
        if ($user->isAdmin() || ($user->account_status ?? 'active') !== 'active') {
            throw new RuntimeException('Trading requires an active customer account.');
        }
        if (trim($key) === '' || strlen($key) > 120) {
            throw new InvalidArgumentException('A valid paper order idempotency key is required.');
        }
    }

    private function replay(User $user, string $key, string $fingerprint): ?BrokerOrder
    {
        $order = BrokerOrder::query()->where('user_id', $user->id)->where('idempotency_key', $key)->first();
        if ($order && ($order->metadata['request_fingerprint'] ?? null) !== $fingerprint) {
            throw new RuntimeException('Idempotency key is already associated with another trading request.');
        }
        return $order;
    }

    private function fingerprint(array $request): string
    {
        return hash('sha256', json_encode($request, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
    }

    private function units(MarketInstrument $instrument, float $size, string $mode, float $price, string $currency): float
    {
        $units = match ($mode) {
            'units' => $size,
            'lots' => $instrument->isForex() ? $size*100000 : throw new RuntimeException('Lots are Forex-only.'),
            'settlement_amount' => $instrument->isCrypto()
                ? $size/$this->notional($instrument, 1.0, $price, $currency)
                : throw new RuntimeException('Settlement-amount sizing is Crypto-only.'),
            default => throw new RuntimeException('Unsupported quantity mode.'),
        };
        $units = floor($units*100000000)/100000000;
        $this->positive($units, 'Executable quantity');
        if ($units > 1000000000000) { throw new RuntimeException('Order quantity exceeds the supported limit.'); }
        return $units;
    }

    private function notional(MarketInstrument $instrument, float $quantity, float $price, string $currency): float
    {
        return $instrument->isStock()
            ? $this->settlement->convertFiat($quantity*$price, 'USD', $currency)
            : $this->settlement->amountForBaseUnits($instrument, $quantity, $price, $currency);
    }

    private function pnlMinor(TradePosition $position, MarketInstrument $instrument, float $price, float $quantity, bool $fresh): int
    {
        $quote = PositionMath::quoteProfitLoss($position->direction, (float)$position->entry_price, $price, $quantity);
        $currency = $position->metadata['settlement_currency'];
        $settled = $instrument->isStock()
            ? $this->settlement->convertFiat($quote, 'USD', $currency, $fresh)
            : $this->settlement->profitLossToSettlement($instrument, $quote, $price, $currency, $fresh);
        return $this->minor($settled);
    }

    private function minor(float $amount, bool $roundUp = false): int
    {
        if (!is_finite($amount) || abs($amount) > 1000000000000) {
            throw new RuntimeException('Settlement amount exceeds the supported range.');
        }
        return (int)($roundUp ? ceil(round($amount, 8)*100-1e-8) : round($amount*100, 0, PHP_ROUND_HALF_UP));
    }

    private function fee(int $notional): int
    {
        $bps = (float)config('paper_trading.fee_basis_points', 0);
        if (!is_finite($bps) || $bps < 0 || $bps > 100) {
            throw new RuntimeException('Trading fee configuration is unavailable.');
        }
        return (int)round($notional*$bps/10000, 0, PHP_ROUND_HALF_UP);
    }

    private function positive(float $value, string $name): void
    {
        if (!is_finite($value) || $value <= 0) { throw new RuntimeException($name.' must be finite and positive.'); }
    }

    private function duration(mixed $duration): ?int
    {
        if ($duration === null || $duration === '') { return null; }
        if (filter_var($duration, FILTER_VALIDATE_INT) === false || (int)$duration < 1 || (int)$duration > 43200) {
            throw new RuntimeException('Duration must be between 1 and 43200 minutes.');
        }
        return (int)$duration;
    }

    private function order(User $user, MarketInstrument $instrument, string $side, float $size, string $mode,
        string $key, string $marketplace, string $fingerprint, string $intent, array $context, array $risk,
        ?int $positionId = null): BrokerOrder
    {
        return BrokerOrder::create([
            'public_id'=>(string)Str::uuid(), 'user_id'=>$user->id, 'market_instrument_id'=>$instrument->id,
            'side'=>$side, 'quantity'=>$size, 'quantity_mode'=>$mode, 'order_type'=>'market',
            'status'=>'executing', 'time_in_force'=>'IOC', 'idempotency_key'=>$key,
            'marketplace'=>$marketplace, 'execution_source'=>$context['execution_source'] ?? 'broker_order',
            'risk_controls'=>$risk, 'accepted_at'=>now(), 'submitted_at'=>now(),
            'metadata'=>array_merge($context['metadata'] ?? [], [
                'execution_model'=>self::MODEL, 'order_intent'=>$intent,
                'request_fingerprint'=>$fingerprint, 'target_position_id'=>$positionId,
                'asset_class'=>$instrument->asset_class, 'display_symbol'=>$instrument->display_symbol,
            ]),
        ]);
    }

    private function receipt(BrokerOrder $order, TradePosition $position, float $quantity, float $price,
        float $collateral, float $fee, float $pnl, ?int $walletTx, array $extra): MarketExecutionTransaction
    {
        return MarketExecutionTransaction::create([
            'user_id'=>$order->user_id, 'market_instrument_id'=>$order->market_instrument_id,
            'trade_position_id'=>$position->id, 'wallet_transaction_id'=>$walletTx,
            'idempotency_key'=>$order->idempotency_key, 'side'=>$order->side,
            'execution_source'=>$order->execution_source, 'marketplace'=>$order->marketplace,
            'quantity'=>$quantity, 'price'=>$price, 'gross_value'=>$quantity*$price,
            'settlement_currency'=>$position->metadata['settlement_currency'], 'settlement_amount'=>$collateral,
            'fee'=>$fee, 'realized_profit_loss'=>$pnl, 'status'=>'completed', 'executed_at'=>now(),
            'metadata'=>array_merge($extra, [
                'execution_model'=>self::MODEL, 'order_intent'=>$order->metadata['order_intent'],
                'direction'=>$position->direction, 'context_type'=>$position->context_type,
                'context_id'=>$position->context_id, 'source_position_id'=>$position->source_position_id,
                'margin_policy'=>'isolated_1x_collateral_limited', 'broker_order_id'=>$order->id,
            ]),
        ]);
    }

    private function fill(BrokerOrder $order, MarketExecutionTransaction $execution): BrokerOrder
    {
        $order->update(['market_execution_transaction_id'=>$execution->id,
            'filled_quantity'=>$execution->quantity, 'average_fill_price'=>$execution->price,
            'gross_value'=>$execution->gross_value, 'settlement_currency'=>$execution->settlement_currency,
            'settlement_amount'=>$execution->settlement_amount, 'fee'=>$execution->fee,
            'status'=>'filled', 'filled_at'=>$execution->executed_at]);
        BrokerOrderEvent::create(['broker_order_id'=>$order->id, 'actor_type'=>'system',
            'event_type'=>'filled', 'from_status'=>'executing', 'to_status'=>'filled',
            'note'=>'Order completed and account updated.',
            'metadata'=>['market_execution_transaction_id'=>$execution->id]]);
        return $order->refresh();
    }

    private function walletDelta(Wallet $wallet, int $minor, string $label, BrokerOrder $order)
    {
        return $wallet->transactions()->create(['payment_method_id'=>null, 'type'=>'investment',
            'direction'=>$minor >= 0 ? 'credit' : 'debit', 'amount'=>abs($minor)/100,
            'fee'=>0, 'status'=>'completed', 'reference_id'=>'PAPER-'.$order->public_id,
            'description'=>$label.' · '.$order->public_id]);
    }

    private function snapshot(Wallet $wallet): array
    {
        return ['balance'=>(float)$wallet->balance, 'reserved'=>(float)$wallet->reserved_balance,
            'available'=>(float)$wallet->available_balance];
    }

    private function event(TradePosition $position, MarketExecutionTransaction $execution, string $type, array $context): void
    {
        TradePositionEvent::create(['trade_position_id'=>$position->id,
            'market_execution_transaction_id'=>$execution->id, 'event_type'=>$type,
            'actor_type'=>$context['actor_type'] ?? 'user', 'actor_id'=>($context['actor_type'] ?? 'user') === 'user' ? ($context['actor_id'] ?? $position->user_id) : null,
            'quantity'=>$execution->quantity, 'price'=>$execution->price,
            'profit_loss'=>$execution->realized_profit_loss, 'metadata'=>$execution->metadata]);
    }
}
