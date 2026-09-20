<?php

namespace App\Console\Commands;

use App\Models\BrokerOrder;
use App\Models\MarketHolding;
use App\Models\StockHolding;
use App\Models\TradePosition;
use App\Models\User;
use Illuminate\Console\Command;

class InspectLiquidBrokerageAcceptance extends Command
{
    protected $signature = 'broker:inspect-liquid-acceptance';
    protected $description = 'Inspect MS3 persistent manual Liquid Brokerage acceptance across Stock, Forex and Crypto.';

    public function handle(): int
    {
        $cases = [
            ['asset' => 'STOCK', 'email' => 'qa.broker.stock@rcentz.test', 'key' => 'stock-aapl', 'close' => true],
            ['asset' => 'FOREX', 'email' => 'qa.broker.fx@rcentz.test', 'key' => 'forex-eurusd', 'close' => false],
            ['asset' => 'CRYPTO', 'email' => 'qa.broker.crypto@rcentz.test', 'key' => 'crypto-btcusd', 'close' => true],
        ];

        $rows = [];
        $failed = false;

        foreach ($cases as $case) {
            $user = User::query()->with(['wallet', 'kyc'])->where('email', $case['email'])->first();
            if (! $user || ! $user->kyc?->isApproved()) {
                $rows[] = [$case['asset'], $case['email'], 'USER/KYC MISSING', '—', '—', '—', '—'];
                $failed = true;
                continue;
            }

            $buyKey = 'qa-broker-case:'.$case['key'].':buy:v1';
            $buy = BrokerOrder::query()->with(['execution', 'marketInstrument', 'events'])
                ->where('user_id', $user->id)
                ->where('idempotency_key', $buyKey)
                ->first();

            if (! $buy || $buy->status !== BrokerOrder::STATUS_FILLED || ! $buy->execution) {
                $rows[] = [$case['asset'], $case['email'], 'BUY MISSING', '—', '—', '—', number_format((float)($user->wallet?->balance ?? 0), 2)];
                $failed = true;
                continue;
            }

            $position = $buy->execution->trade_position_id
                ? TradePosition::query()->find($buy->execution->trade_position_id)
                : null;

            $asset = strtoupper((string) ($buy->marketInstrument?->asset_class ?: ''));
            $idempotentBuy = BrokerOrder::query()
                ->where('user_id', $user->id)
                ->where('idempotency_key', $buyKey)
                ->count() === 1;
            $eventsOk = $buy->events->whereIn('event_type', ['accepted', 'submitted', 'filled'])->count() >= 3;
            $sourceOk = $buy->execution_source === 'broker_order'
                && $buy->execution->execution_source === 'broker_order';
            $positionOk = $position
                && $position->context_type === 'broker_order'
                && (int) $position->context_id === (int) $buy->id;

            $close = null;
            $closeOk = true;
            if ($case['close']) {
                $closeKey = 'qa-broker-case:'.$case['key'].':close:v1';
                $close = BrokerOrder::query()->with(['execution', 'events'])
                    ->where('user_id', $user->id)
                    ->where('idempotency_key', $closeKey)
                    ->first();

                $closeOk = $close
                    && $close->status === BrokerOrder::STATUS_FILLED
                    && $close->execution
                    && BrokerOrder::query()->where('user_id', $user->id)->where('idempotency_key', $closeKey)->count() === 1
                    && (int) (($close->metadata ?? [])['target_position_id'] ?? 0) === (int) $position?->id
                    && $position?->fresh()?->status === 'closed'
                    && (float) ($position?->fresh()?->open_quantity ?? 0) <= 0.00000001;
            } else {
                $closeOk = $position?->is_open
                    && (float) $position->open_quantity > 0
                    && $position->stop_loss_percent !== null
                    && $position->take_profit_percent !== null;
            }

            $holdingQty = 0.0;
            if ($buy->marketInstrument?->isStock()) {
                $holdingQty = (float) (StockHolding::query()
                    ->where('user_id', $user->id)
                    ->where('market_instrument_id', $buy->market_instrument_id)
                    ->where('marketplace', 'controlled')
                    ->value('quantity') ?? 0);
            } else {
                $holdingQty = (float) (MarketHolding::query()
                    ->where('user_id', $user->id)
                    ->where('market_instrument_id', $buy->market_instrument_id)
                    ->where('marketplace', 'controlled')
                    ->value('quantity') ?? 0);
            }

            $holdingOk = $case['close'] ? $holdingQty <= 0.00000001 : $holdingQty > 0;
            $caseOk = $asset === $case['asset']
                && $idempotentBuy
                && $eventsOk
                && $sourceOk
                && $positionOk
                && $closeOk
                && $holdingOk;

            if (! $caseOk) {
                $failed = true;
            }

            $rows[] = [
                $asset ?: '—',
                $case['email'],
                $buy->marketInstrument?->display_symbol ?: '—',
                $buy->status,
                $close?->status ?: 'OPEN',
                $position?->fresh()?->status ?: '—',
                number_format((float) ($user->wallet?->balance ?? 0), 2),
            ];
        }

        $this->table(
            ['Asset', 'Customer', 'Instrument', 'BUY', 'CLOSE', 'Position', 'Wallet'],
            $rows
        );

        $filledMissingReceipt = BrokerOrder::query()
            ->where('status', BrokerOrder::STATUS_FILLED)
            ->whereNull('market_execution_transaction_id')
            ->count();
        $qaFilledWrongSource = BrokerOrder::query()
            ->whereHas('user', fn ($q) => $q->where('email', 'like', 'qa.broker.%@rcentz.test'))
            ->where('status', BrokerOrder::STATUS_FILLED)
            ->where('execution_source', '!=', 'broker_order')
            ->count();
        $qaDuplicateKeys = BrokerOrder::query()
            ->whereHas('user', fn ($q) => $q->where('email', 'like', 'qa.broker.%@rcentz.test'))
            ->selectRaw('user_id, idempotency_key, COUNT(*) as total')
            ->groupBy('user_id', 'idempotency_key')
            ->having('total', '>', 1)
            ->count();

        $checks = [
            'Filled BrokerOrders missing unified receipt' => $filledMissingReceipt,
            'QA filled orders with non-manual execution source' => $qaFilledWrongSource,
            'QA duplicate user/idempotency keys' => $qaDuplicateKeys,
        ];

        $this->newLine();
        $this->table(
            ['Liquid Brokerage authority check', 'Count'],
            collect($checks)->map(fn ($count, $label) => [$label, $count])->values()->all()
        );

        if ($failed || collect($checks)->contains(fn ($count) => $count > 0)) {
            $this->error('MS3 Liquid Brokerage acceptance has unresolved items.');
            return self::FAILURE;
        }

        $this->info('MS3 Liquid Brokerage is READY with persistent manual BrokerOrder runtime evidence across Stock, Forex and Crypto, including replay-safe idempotency and exact-position closes.');
        return self::SUCCESS;
    }
}
