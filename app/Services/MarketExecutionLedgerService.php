<?php

namespace App\Services;

use App\Models\MarketExecutionTransaction;
use App\Models\StockTransaction;
use RuntimeException;

final class MarketExecutionLedgerService
{
    public function mirrorStockTransaction(StockTransaction $transaction): MarketExecutionTransaction
    {
        if (! $transaction->exists || ! $transaction->getKey()) {
            throw new RuntimeException('Only persisted StockTransaction rows can enter the unified execution ledger.');
        }

        $transaction->loadMissing(['marketInstrument', 'stock.marketInstrument']);
        $instrument = $transaction->marketInstrument ?? $transaction->stock?->marketInstrument;

        if (! $instrument) {
            throw new RuntimeException('StockTransaction is missing canonical MarketInstrument identity.');
        }

        return MarketExecutionTransaction::updateOrCreate(
            [
                'native_type' => 'stock_transaction',
                'native_id' => $transaction->id,
            ],
            [
                'user_id' => $transaction->user_id,
                'market_instrument_id' => $instrument->id,
                'wallet_transaction_id' => $transaction->wallet_transaction_id,
                'trade_position_id' => $transaction->trade_position_id,
                'side' => $transaction->type,
                'execution_source' => $transaction->execution_source,
                'marketplace' => $transaction->marketplace ?: 'live',
                'quantity' => $transaction->quantity,
                'price' => $transaction->price_per_share,
                'gross_value' => $transaction->total_amount,
                'settlement_currency' => null,
                'settlement_amount' => null,
                'fee' => $transaction->fee ?? 0,
                'realized_profit_loss' => null,
                'status' => $transaction->status,
                'executed_at' => $transaction->executed_at,
                'metadata' => [
                    'stock_id' => $transaction->stock_id,
                    'copy_strategy_id' => $transaction->copy_strategy_id,
                    'compatibility_ledger' => 'stock_transactions',
                ],
            ]
        );
    }

    public function forStockTransaction(StockTransaction $transaction): MarketExecutionTransaction
    {
        if ($transaction->getKey()) {
            $existing = MarketExecutionTransaction::query()
                ->where('native_type', 'stock_transaction')
                ->where('native_id', $transaction->getKey())
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return $this->mirrorStockTransaction($transaction);
    }

    public function idempotentForUser(int $userId, ?string $key): ?MarketExecutionTransaction
    {
        if (! $key) {
            return null;
        }

        return MarketExecutionTransaction::query()
            ->where('user_id', $userId)
            ->where('idempotency_key', $key)
            ->first();
    }

    public function record(array $attributes): MarketExecutionTransaction
    {
        foreach (['user_id', 'market_instrument_id', 'side', 'marketplace', 'quantity', 'price', 'gross_value', 'status'] as $required) {
            if (! array_key_exists($required, $attributes)) {
                throw new RuntimeException("Unified execution receipt is missing {$required}.");
            }
        }

        $key = trim((string) ($attributes['idempotency_key'] ?? ''));
        if ($key !== '') {
            $existing = $this->idempotentForUser((int) $attributes['user_id'], $key);
            if ($existing) {
                return $existing;
            }
            $attributes['idempotency_key'] = $key;
        } else {
            $attributes['idempotency_key'] = null;
        }

        return MarketExecutionTransaction::create($attributes);
    }
}
