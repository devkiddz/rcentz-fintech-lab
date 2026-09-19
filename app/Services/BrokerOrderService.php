<?php

namespace App\Services;

use App\Models\BrokerOrder;
use App\Models\MarketExecutionTransaction;
use App\Models\MarketInstrument;
use App\Models\TradePosition;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class BrokerOrderService
{
    public function __construct(
        private MarketExecutionRouter $execution,
        private MarketPriceRouter $prices,
        private BrokerTradeContractEngine $trades
    ) {}

    public function placeMarketOrder(
        User $user,
        MarketInstrument $instrument,
        string $side,
        float $quantity,
        string $quantityMode,
        string $idempotencyKey,
        array $risk = []
    ): BrokerOrder {
        $side = strtolower(trim($side));
        $quantityMode = strtolower(trim($quantityMode));
        $idempotencyKey = trim($idempotencyKey);

        if (! in_array($side, ['buy', 'sell'], true)) {
            throw new InvalidArgumentException('Broker order side must be buy or sell.');
        }
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Broker order quantity must be greater than zero.');
        }
        if ($idempotencyKey === '' || strlen($idempotencyKey) > 120) {
            throw new InvalidArgumentException('A valid execution idempotency key is required.');
        }

        $marketplace = $this->prices->normalizeMarketplace($this->prices->activeMarketplace());
        $this->assertQuantityMode($instrument, $quantityMode);

        if (! $instrument->is_active || ! $this->execution->canExecute($instrument)) {
            throw new RuntimeException('This instrument is not currently executable.');
        }

        $existing = BrokerOrder::query()
            ->where('user_id', $user->id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            $this->assertSameRequest($existing, $instrument, $side, $quantity, $quantityMode, $marketplace);
            return $existing;
        }

        try {
            $order = BrokerOrder::create([
                'public_id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'market_instrument_id' => $instrument->id,
                'marketplace' => $marketplace,
                'side' => $side,
                'order_type' => 'market',
                'quantity' => $quantity,
                'quantity_mode' => $quantityMode,
                'filled_quantity' => 0,
                'fee' => 0,
                'status' => BrokerOrder::STATUS_ACCEPTED,
                'time_in_force' => 'IOC',
                'idempotency_key' => $idempotencyKey,
                'execution_source' => 'broker_order',
                'risk_controls' => $risk ?: null,
                'accepted_at' => now(),
                'metadata' => [
                    'asset_class' => $instrument->asset_class,
                    'display_symbol' => $instrument->display_symbol,
                    'execution_policy' => 'market_order_immediate_or_fail',
                ],
            ]);
        } catch (QueryException $e) {
            $existing = BrokerOrder::query()
                ->where('user_id', $user->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if (! $existing) {
                throw $e;
            }

            $this->assertSameRequest($existing, $instrument, $side, $quantity, $quantityMode, $marketplace);
            return $existing;
        }

        $this->event($order, 'accepted', null, BrokerOrder::STATUS_ACCEPTED, 'Market order accepted for validation and execution.');
        $order->update([
            'status' => BrokerOrder::STATUS_EXECUTING,
            'submitted_at' => now(),
        ]);
        $this->event($order, 'submitted', BrokerOrder::STATUS_ACCEPTED, BrokerOrder::STATUS_EXECUTING, 'Order submitted to the asset execution adapter.');

        try {
            $execution = $side === 'buy'
                ? $this->trades->buy($user, $instrument, $quantity, $quantityMode, $risk, $order)
                : $this->trades->sell($user, $instrument, $quantity, $quantityMode, $order);

            $this->fill($order, $execution);
            return $order->refresh();
        } catch (Throwable $e) {
            $order->update([
                'status' => BrokerOrder::STATUS_FAILED,
                'failure_code' => class_basename($e),
                'failure_message' => Str::limit($e->getMessage(), 1000, ''),
                'failed_at' => now(),
            ]);
            $this->event(
                $order,
                'failed',
                BrokerOrder::STATUS_EXECUTING,
                BrokerOrder::STATUS_FAILED,
                'Order execution failed closed.',
                ['error' => Str::limit($e->getMessage(), 1000, '')]
            );
            throw $e;
        }
    }

    public function placePositionClose(
        User $user,
        TradePosition $position,
        ?float $quantity,
        string $idempotencyKey
    ): BrokerOrder {
        $idempotencyKey = trim($idempotencyKey);
        if ($idempotencyKey === '' || strlen($idempotencyKey) > 120) {
            throw new InvalidArgumentException('A valid execution idempotency key is required.');
        }

        $position->loadMissing(['marketInstrument', 'stock.marketInstrument']);
        if ((int) $position->user_id !== (int) $user->id || ! $position->is_open) {
            throw new RuntimeException('This position is not available for customer close execution.');
        }

        $instrument = $position->marketInstrument ?? $position->stock?->marketInstrument;
        if (! $instrument || ! $instrument->is_active || ! $this->execution->canExecute($instrument)) {
            throw new RuntimeException('The position instrument is not currently executable.');
        }

        $openQuantity = (float) $position->open_quantity;
        $quantity = $quantity === null ? $openQuantity : (float) $quantity;
        if ($quantity <= 0 || $quantity > $openQuantity) {
            throw new InvalidArgumentException('Position close quantity must be greater than zero and cannot exceed open exposure.');
        }

        $marketplace = $this->prices->normalizeMarketplace($position->marketplace ?: $this->prices->activeMarketplace());

        $existing = BrokerOrder::query()
            ->where('user_id', $user->id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            $this->assertSameRequest($existing, $instrument, 'sell', $quantity, 'units', $marketplace);
            if ((int) (($existing->metadata ?? [])['target_position_id'] ?? 0) !== (int) $position->id) {
                throw new RuntimeException('Idempotency key was already used for a different position close.');
            }
            return $existing;
        }

        try {
            $order = BrokerOrder::create([
                'public_id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'market_instrument_id' => $instrument->id,
                'marketplace' => $marketplace,
                'side' => 'sell',
                'order_type' => 'market',
                'quantity' => $quantity,
                'quantity_mode' => 'units',
                'filled_quantity' => 0,
                'fee' => 0,
                'status' => BrokerOrder::STATUS_ACCEPTED,
                'time_in_force' => 'IOC',
                'idempotency_key' => $idempotencyKey,
                'execution_source' => 'broker_position_close',
                'accepted_at' => now(),
                'metadata' => [
                    'asset_class' => $instrument->asset_class,
                    'display_symbol' => $instrument->display_symbol,
                    'target_position_id' => $position->id,
                    'execution_policy' => 'market_position_close_immediate_or_fail',
                ],
            ]);
        } catch (QueryException $e) {
            $existing = BrokerOrder::query()
                ->where('user_id', $user->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if (! $existing) {
                throw $e;
            }

            $this->assertSameRequest($existing, $instrument, 'sell', $quantity, 'units', $marketplace);
            if ((int) (($existing->metadata ?? [])['target_position_id'] ?? 0) !== (int) $position->id) {
                throw new RuntimeException('Idempotency key was already used for a different position close.');
            }
            return $existing;
        }

        $this->event($order, 'accepted', null, BrokerOrder::STATUS_ACCEPTED, 'Position close order accepted for validation and execution.');
        $order->update([
            'status' => BrokerOrder::STATUS_EXECUTING,
            'submitted_at' => now(),
        ]);
        $this->event($order, 'submitted', BrokerOrder::STATUS_ACCEPTED, BrokerOrder::STATUS_EXECUTING, 'Position close submitted to the asset execution adapter.');

        try {
            $execution = $this->trades->closePosition($user, $position, $quantity, $order);
            $this->fill($order, $execution);
            return $order->refresh();
        } catch (Throwable $e) {
            $order->update([
                'status' => BrokerOrder::STATUS_FAILED,
                'failure_code' => class_basename($e),
                'failure_message' => Str::limit($e->getMessage(), 1000, ''),
                'failed_at' => now(),
            ]);
            $this->event(
                $order,
                'failed',
                BrokerOrder::STATUS_EXECUTING,
                BrokerOrder::STATUS_FAILED,
                'Position close failed closed.',
                ['error' => Str::limit($e->getMessage(), 1000, '')]
            );
            throw $e;
        }
    }

    private function fill(BrokerOrder $order, MarketExecutionTransaction $execution): void
    {
        if ($execution->status !== 'completed') {
            throw new RuntimeException('Broker order did not receive a completed execution receipt.');
        }

        $order->update([
            'market_execution_transaction_id' => $execution->id,
            'filled_quantity' => $execution->quantity,
            'average_fill_price' => $execution->price,
            'gross_value' => $execution->gross_value,
            'fee' => $execution->fee ?? 0,
            'settlement_currency' => $execution->settlement_currency,
            'settlement_amount' => $execution->settlement_amount ?? $execution->gross_value,
            'status' => BrokerOrder::STATUS_FILLED,
            'filled_at' => $execution->executed_at ?? now(),
            'metadata' => array_merge($order->metadata ?? [], [
                'market_execution_transaction_id' => $execution->id,
                'native_type' => $execution->native_type,
                'native_id' => $execution->native_id,
            ]),
        ]);

        $this->event(
            $order,
            'filled',
            BrokerOrder::STATUS_EXECUTING,
            BrokerOrder::STATUS_FILLED,
            'Order filled and linked to the unified execution ledger.',
            ['market_execution_transaction_id' => $execution->id]
        );
    }

    private function assertQuantityMode(MarketInstrument $instrument, string $mode): void
    {
        $allowed = match ($instrument->asset_class) {
            MarketInstrument::ASSET_STOCK => ['units'],
            MarketInstrument::ASSET_FOREX => ['units', 'lots'],
            MarketInstrument::ASSET_CRYPTO => ['units', 'settlement_amount'],
            default => [],
        };

        if (! in_array($mode, $allowed, true)) {
            throw new InvalidArgumentException(
                'Unsupported quantity mode for '.strtoupper((string) $instrument->asset_class).'.'
            );
        }
    }

    private function assertSameRequest(
        BrokerOrder $order,
        MarketInstrument $instrument,
        string $side,
        float $quantity,
        string $quantityMode,
        string $marketplace
    ): void {
        $matches = (int) $order->market_instrument_id === (int) $instrument->id
            && $order->side === $side
            && $order->quantity_mode === $quantityMode
            && $order->marketplace === $marketplace
            && abs((float) $order->quantity - $quantity) < 0.00000001;

        if (! $matches) {
            throw new RuntimeException('Idempotency key was already used for a different broker order.');
        }
    }

    private function event(
        BrokerOrder $order,
        string $type,
        ?string $from,
        ?string $to,
        ?string $note = null,
        array $metadata = []
    ): void {
        $order->events()->create([
            'actor_id' => $order->user_id,
            'actor_type' => 'user',
            'event_type' => $type,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'metadata' => $metadata ?: null,
        ]);
    }
}
