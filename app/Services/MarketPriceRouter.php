<?php

namespace App\Services;

use App\Models\MarketEnvironment;
use App\Models\Stock;
use App\Services\Market\ControlledMarketPriceProvider;
use App\Services\Market\LiveMarketPriceProvider;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class MarketPriceRouter
{
    private ?string $active = null;

    public function __construct(
        private LiveMarketPriceProvider $live,
        private ControlledMarketPriceProvider $controlled
    ) {}

    public function activeMarketplace(): string
    {
        if ($this->active !== null) {
            return $this->active;
        }

        if (! Schema::hasTable('market_environments')) {
            return $this->active = 'live';
        }

        $mode = MarketEnvironment::query()->value('active_marketplace') ?: 'live';

        return $this->active = $this->normalizeMarketplace($mode);
    }

    public function normalizeMarketplace(?string $marketplace): string
    {
        $marketplace = strtolower(trim((string) ($marketplace ?: 'live')));

        if (! in_array($marketplace, ['live', 'controlled'], true)) {
            throw new InvalidArgumentException('Unknown marketplace: '.$marketplace);
        }

        return $marketplace;
    }

    public function price(Stock $stock, ?string $marketplace = null): float
    {
        $marketplace = $this->normalizeMarketplace($marketplace ?: $this->activeMarketplace());

        return $marketplace === 'controlled'
            ? $this->controlled->price($stock)
            : $this->live->price($stock);
    }

    public function displayPrice(Stock $stock, float $rawLivePrice): float
    {
        if ($this->activeMarketplace() !== 'controlled') {
            return $rawLivePrice;
        }

        try {
            return $this->controlled->price($stock);
        } catch (\Throwable) {
            return $rawLivePrice;
        }
    }

    public function requiresRegularSession(?string $marketplace = null): bool
    {
        return $this->normalizeMarketplace($marketplace ?: $this->activeMarketplace()) === 'live';
    }

    public function setActiveMarketplace(string $marketplace, ?int $actorId = null): MarketEnvironment
    {
        $marketplace = $this->normalizeMarketplace($marketplace);
        $environment = MarketEnvironment::current();
        $environment->update([
            'active_marketplace' => $marketplace,
            'updated_by_user_id' => $actorId,
        ]);
        $this->active = $marketplace;

        return $environment->refresh();
    }

    public function forget(): void
    {
        $this->active = null;
    }
}
