<?php
declare(strict_types=1);

namespace App\Services\PaperTrading;

use App\Models\BrokerOrder;
use App\Models\BotSubscription;
use App\Models\TradePosition;
use App\Models\TradingBot;
use App\Models\TradingBotExecution;
use App\Models\User;
use App\Services\BrokerOrderService;
use App\Services\FeatureAccessService;
use App\Services\MarketPriceRouter;
use App\Services\MarketSettlementService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Explicit intent; serialized bot budget, receipt, execution log and schedule state. */
final class PaperBotService
{
    public function __construct(private PaperBrokerService $paper, private BrokerOrderService $orders,
        private PaperQuoteService $quotes, private MarketPriceRouter $prices,
        private MarketSettlementService $settlement, private FeatureAccessService $features) {}

    public static function intent(TradingBot $bot): string
    {
        $intent = $bot->paper_intent;
        // Null preserves the existing Buy-entry / Sell-close interpretation.
        if ($intent === null) {
            return match ($bot->action) {
                'buy'=>'open_long', 'sell'=>'close',
                default=>throw new RuntimeException('Legacy bot action is invalid.'),
            };
        }
        if (!in_array($intent, ['open_long','open_short','close'], true)) {
            throw new RuntimeException('Paper bot intent must be open_long, open_short or close.');
        }
        return $intent;
    }

    public function run(TradingBot $bot, bool $manual = false, ?string $requestKey = null): TradingBotExecution
    {
        if (!config('paper_trading.enabled', false) || !config('paper_trading.bots_enabled', false)) {
            throw new RuntimeException('Paper bot execution is not enabled.');
        }
        $token = $requestKey ?? ($manual ? 'manual-'.now()->utc()->format('YmdHi')
            : 'due-'.($bot->next_run_at?->copy()->utc()->format('YmdHis') ?? now()->utc()->startOfMinute()->format('YmdHis')));
        $key = 'paper-bot:'.$bot->id.':'.$token;
        if (strlen($key)>120 || trim($token)==='') { throw new RuntimeException('Invalid paper bot request key.'); }
        return DB::transaction(function () use ($bot, $manual, $key) {
            $user = User::query()->lockForUpdate()->findOrFail($bot->user_id);
            $bot = TradingBot::query()->lockForUpdate()->findOrFail($bot->id);
            if ((int)$bot->user_id !== (int)$user->id) { throw new RuntimeException('Bot ownership changed.'); }
            $intent = self::intent($bot);
            $fingerprint = hash('sha256',json_encode([$intent,$bot->market_instrument_id,$bot->quantity_per_trade,
                $bot->amount_per_trade,$bot->stop_loss_percent,$bot->take_profit_percent,$bot->position_duration_minutes,
                $bot->strategy,$bot->trigger_price],JSON_THROW_ON_ERROR));
            $old = BrokerOrder::where('user_id',$user->id)->where('idempotency_key',$key)->first();
            if ($old) {
                if (($old->metadata['bot_request_fingerprint'] ?? null) !== $fingerprint) {
                    throw new RuntimeException('Bot request key was already used with different settings.');
                }
                return TradingBotExecution::where('broker_order_id',$old->id)->where('trading_bot_id',$bot->id)->firstOrFail();
            }
            if (!$manual && ($bot->status !== 'active' || ($bot->next_run_at && $bot->next_run_at->isFuture()))) {
                return $this->log($bot,null,'skipped','Bot is paused or not due.');
            }
            $subscription = BotSubscription::query()->lockForUpdate()->where('trading_bot_id',$bot->id)->first();
            $instrument = $bot->marketInstrument ?? $bot->stock?->marketInstrument;
            if (!$instrument) { throw new RuntimeException('Bot instrument is unavailable.'); }
            $wallet = $user->wallet()->lockForUpdate()->firstOrFail();
            $context = ['context_type'=>'trading_bot', 'context_id'=>$bot->id,
                'execution_source'=>'paper_bot', 'actor_type'=>'system', 'exit_reason'=>'bot_exit',
                'metadata'=>['bot_request_fingerprint'=>$fingerprint, 'trading_bot_id'=>$bot->id, 'bot_subscription_id'=>$subscription?->id, 'bot_intent'=>$intent]];
            if ($intent === 'close') {
                // An expired entitlement must not strand an existing attributed position.
                $position = TradePosition::where('user_id',$user->id)->where('context_type','trading_bot')
                    ->where('context_id',$bot->id)->where('market_instrument_id',$instrument->id)
                    ->whereIn('status',['open','exit_queued'])->where('open_quantity','>',0)->oldest('opened_at')->first();
                if (!$position) { return $this->finish($bot,$this->log($bot,null,'skipped','No bot-attributed exposure remains.')); }
                $price = $this->quotes->execution($instrument,PositionMath::closingSide($position->direction),$position->marketplace);
                if (!$this->triggered($bot,$price)) { return $this->finish($bot,$this->log($bot,null,'skipped','Price trigger not reached.')); }
                $quantity = min((float)$position->open_quantity,$this->quantity($bot,$instrument,$price,(string)$wallet->currency));
                $order = $this->orders->placePositionClose($user,$position,$quantity,$key,$context);
            } else {
                if (!$subscription || (int)$subscription->user_id !== (int)$user->id || !$subscription->is_usable
                    || !$subscription->product?->is_active || ($subscription->starts_at && $subscription->starts_at->isFuture())) {
                    return $this->finish($bot,$this->log($bot,null,'skipped','Bot subscription is unavailable for new entries.'));
                }
                if (!$user->kyc?->isApproved() || !$this->features->allows($user,FeatureAccessService::BOT_TRADER)) {
                    $bot->update(['status'=>'paused']);
                    return $this->finish($bot,$this->log($bot,null,'skipped','Approved KYC and Bot Trader entitlement are required for entries.'));
                }
                if ($bot->executions()->where('status','completed')->whereDate('executed_at',today())->count() >= (int)$bot->max_daily_trades) {
                    return $this->finish($bot,$this->log($bot,null,'skipped','Daily trade limit reached.'));
                }
                $side = $intent === 'open_long' ? 'buy' : 'sell';
                $price = $this->quotes->execution($instrument,$side,$this->prices->activeMarketplace());
                if (!$this->triggered($bot,$price)) { return $this->finish($bot,$this->log($bot,null,'skipped','Price trigger not reached.')); }
                $quantity = $this->quantity($bot,$instrument,$price,(string)$wallet->currency);
                $order = $this->paper->open($user,$instrument,$side,$quantity,'units',$key,
                    ['stop_loss_percent'=>$bot->stop_loss_percent, 'take_profit_percent'=>$bot->take_profit_percent,
                        'duration_minutes'=>$bot->position_duration_minutes],$context);
                $collateral = (float)$order->execution->settlement_amount;
                $spent = round((float)$bot->spent_total+$collateral,2);
                if ($bot->max_total_spend !== null && $spent > (float)$bot->max_total_spend+0.000001) {
                    throw new RuntimeException('Paper bot maximum total collateral budget reached.');
                }
                $bot->update(['spent_total'=>$spent]);
            }
            // All downstream failures roll back the order, wallet, spent counter and bot log.
            return $this->finish($bot,$this->log($bot,$order,'completed',null));
        },3);
    }

    private function quantity(TradingBot $bot, $instrument, float $price, string $currency): float
    {
        if ((float)$bot->quantity_per_trade > 0) { return (float)$bot->quantity_per_trade; }
        $one = $instrument->isStock() ? $this->settlement->convertFiat($price,'USD',$currency,true)
            : $this->settlement->amountForBaseUnits($instrument,1.0,$price,$currency,true);
        $amount = (float)$bot->amount_per_trade;
        if (!is_finite($one) || $one <= 0 || !is_finite($amount) || $amount <= 0) {
            throw new RuntimeException('Bot sizing is unavailable.');
        }
        $quantity = floor(($amount/$one)*100000000)/100000000;
        if ($quantity<=0) { throw new RuntimeException('Bot size is below supported precision.'); }
        return $quantity;
    }

    private function triggered(TradingBot $bot, float $price): bool
    {
        return match ($bot->strategy) {
            'price_below'=>$bot->trigger_price !== null && $price <= (float)$bot->trigger_price,
            'price_above'=>$bot->trigger_price !== null && $price >= (float)$bot->trigger_price,
            'dca', 'interval'=>true,
            default=>throw new RuntimeException('Unsupported paper bot strategy.'),
        };
    }

    private function finish(TradingBot $bot, TradingBotExecution $record): TradingBotExecution
    {
        $bot->update(['last_run_at'=>now(), 'next_run_at'=>now()->addMinutes(max(5,(int)$bot->interval_minutes))]);
        return $record;
    }

    private function log(TradingBot $bot, ?BrokerOrder $order, string $status, ?string $reason): TradingBotExecution
    {
        $execution = $order?->execution;
        return TradingBotExecution::create(['trading_bot_id'=>$bot->id, 'bot_subscription_id'=>$bot->subscription?->id,
            'market_instrument_id'=>$execution?->market_instrument_id ?? $bot->market_instrument_id, 'broker_order_id'=>$order?->id,
            'market_execution_transaction_id'=>$execution?->id,
            'stock_transaction_id'=>$execution?->native_type === 'stock_transaction' ? $execution->native_id : null,
            'action'=>$execution?->side ?? $bot->action, 'quantity'=>$execution?->quantity ?? 0,
            'price'=>$execution?->price ?? 0, 'amount'=>$execution?->settlement_amount ?? 0,
            'status'=>$status, 'reason'=>$reason, 'executed_at'=>$execution?->executed_at ?? now()]);
    }
}
