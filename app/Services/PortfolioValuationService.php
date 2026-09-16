<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockHolding;
use App\Models\User;
use Illuminate\Support\Collection;

final class PortfolioValuationService
{
    public function __construct(
        private MarketPriceRouter $prices
    ) {}

    public function syncHolding(StockHolding $holding): StockHolding
    {
        $holding->loadMissing('stock');

        if (! $holding->stock) {
            return $holding;
        }

        $marketplace = $this->prices->normalizeMarketplace($holding->marketplace ?: 'live');
        $price = $this->prices->price($holding->stock, $marketplace);
        $quantity = (float) $holding->quantity;
        $invested = (float) $holding->total_invested;
        $current = round($quantity * $price, 2);
        $pnl = $current - $invested;
        $return = $invested > 0 ? ($pnl / $invested) * 100 : 0;

        $holding->updateQuietly([
            'current_value' => $current,
            'unrealized_gain_loss' => $pnl,
            'unrealized_gain_loss_percentage' => $return,
        ]);

        // Transient presentation value: never stored in the Live stock column.
        $holding->setAttribute('market_price', $price);

        return $holding;
    }

    public function syncStock(Stock $stock, string $marketplace): int
    {
        $marketplace = $this->prices->normalizeMarketplace($marketplace);
        $updated = 0;

        StockHolding::query()
            ->where('stock_id', $stock->id)
            ->where('marketplace', $marketplace)
            ->where('quantity', '>', 0)
            ->with('stock')
            ->chunkById(100, function ($holdings) use (&$updated) {
                foreach ($holdings as $holding) {
                    $this->syncHolding($holding);
                    $updated++;
                }
            });

        return $updated;
    }

    public function syncUser(User $user, string $marketplace): Collection
    {
        $marketplace = $this->prices->normalizeMarketplace($marketplace);

        $holdings = $user->stockHoldings()
            ->with('stock')
            ->where('marketplace', $marketplace)
            ->get();

        return $holdings
            ->map(fn (StockHolding $holding) => $this->syncHolding($holding))
            ->sortByDesc(fn (StockHolding $holding) => (float) $holding->current_value)
            ->values();
    }
}
