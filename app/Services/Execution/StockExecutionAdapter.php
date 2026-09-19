<?php

namespace App\Services\Execution;

use App\Contracts\MarketExecutionAdapter;
use App\Models\MarketInstrument;
use App\Models\Stock;
use App\Models\User;
use App\Services\StockTradeExecutor;
use InvalidArgumentException;
use RuntimeException;

final class StockExecutionAdapter implements MarketExecutionAdapter
{
    public function __construct(
        private readonly StockTradeExecutor $executor
    ) {}

    public function assetClass(): string
    {
        return MarketInstrument::ASSET_STOCK;
    }

    public function supports(MarketInstrument $instrument): bool
    {
        return $instrument->isStock() && $this->stockFor($instrument) !== null;
    }

    public function capabilities(MarketInstrument $instrument): array
    {
        $stock = $this->stockFor($instrument);

        return [
            'asset_class' => $this->assetClass(),
            'adapter' => self::class,
            'executable' => $instrument->isStock() && $stock !== null && (bool) $instrument->is_active && (bool) $stock->is_active,
            'quantity_unit' => 'shares',
            'session_model' => 'regular_us_equity_session',
            'settlement_model' => 'wallet_cash_and_stock_holding',
            'native_ledger' => 'stock_transactions',
            'reason' => $stock
                ? 'Mature StockTradeExecutor is available through MarketInstrument authority.'
                : 'Canonical Stock child is unavailable.',
        ];
    }

    public function execute(
        User $user,
        MarketInstrument $instrument,
        string $side,
        float $quantity,
        array $context = []
    ): mixed {
        $stock = $this->stockFor($instrument);

        if (! $stock || ! $instrument->is_active || ! $stock->is_active) {
            throw new RuntimeException('Stock execution is unavailable for this MarketInstrument.');
        }

        $side = strtolower(trim($side));
        if (! in_array($side, ['buy', 'sell'], true)) {
            throw new InvalidArgumentException('Execution side must be buy or sell.');
        }

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Execution quantity must be greater than zero.');
        }

        $source = (string) ($context['source'] ?? 'market_execution');
        $sourceId = isset($context['source_id']) ? (int) $context['source_id'] : null;
        $copyStrategyId = isset($context['copy_strategy_id']) ? (int) $context['copy_strategy_id'] : null;
        $actorType = (string) ($context['actor_type'] ?? 'system');
        $actorId = isset($context['actor_id']) ? (int) $context['actor_id'] : null;
        $positionId = isset($context['position_id']) ? (int) $context['position_id'] : null;
        $marketplace = isset($context['marketplace']) ? (string) $context['marketplace'] : null;

        if ($side === 'buy') {
            return $this->executor->buy(
                $user,
                $stock,
                $quantity,
                $source,
                $sourceId,
                $copyStrategyId,
                $actorType,
                $actorId,
                $positionId,
                $marketplace
            );
        }

        return $this->executor->sell(
            $user,
            $stock,
            $quantity,
            $source,
            $sourceId,
            $copyStrategyId,
            $actorType,
            $actorId,
            $positionId,
            (bool) ($context['allow_closed_session_settlement'] ?? false),
            isset($context['execution_price']) ? (float) $context['execution_price'] : null,
            $marketplace
        );
    }

    private function stockFor(MarketInstrument $instrument): ?Stock
    {
        if (! $instrument->isStock()) {
            return null;
        }

        if ($instrument->relationLoaded('canonicalStock') && $instrument->canonicalStock) {
            return $instrument->canonicalStock;
        }

        $stock = $instrument->canonicalStock()->first();
        if ($stock) {
            return $stock;
        }

        return $instrument->stock_id
            ? Stock::query()->find($instrument->stock_id)
            : null;
    }
}
