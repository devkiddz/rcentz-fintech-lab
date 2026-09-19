<?php

namespace App\Services;

use App\Models\CryptoPair;
use App\Models\ForexPair;
use App\Models\MarketEnvironment;
use App\Models\MarketInstrument;
use App\Models\Stock;
use App\Services\Market\ControlledMarketPriceProvider;
use App\Services\Market\LiveMarketPriceProvider;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;

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

    public function instrument(MarketInstrument|Stock|ForexPair|CryptoPair $asset): MarketInstrument
    {
        if ($asset instanceof MarketInstrument) {
            return $asset;
        }

        $parentId = (int) ($asset->market_instrument_id ?? 0);

        if ($parentId > 0) {
            $parent = MarketInstrument::query()->find($parentId);
            if ($parent) {
                return $parent;
            }
        }

        if ($asset instanceof Stock) {
            $parent = MarketInstrument::query()->where('stock_id', $asset->id)->first();
        } elseif ($asset instanceof ForexPair) {
            $parent = MarketInstrument::query()->where('forex_pair_id', $asset->id)->first();
        } else {
            // Crypto is parent-first only: no MarketInstrument.crypto_pair_id reverse rail.
            $parent = null;
        }

        if (! $parent) {
            throw new RuntimeException('MarketInstrument parent is unavailable for '.($asset->symbol ?? 'asset').'.');
        }

        return $parent;
    }

    public function price(MarketInstrument|Stock|ForexPair|CryptoPair $asset, ?string $marketplace = null): float
    {
        $marketplace = $this->normalizeMarketplace($marketplace ?: $this->activeMarketplace());
        $instrument = $this->instrument($asset);

        return $marketplace === 'controlled'
            ? $this->controlled->price($instrument)
            : $this->live->price($instrument);
    }

    public function displayPrice(Stock $stock, float $rawLivePrice): float
    {
        if ($this->activeMarketplace() !== 'controlled') {
            return $rawLivePrice;
        }

        try {
            return $this->controlled->price($this->instrument($stock));
        } catch (\Throwable) {
            return $rawLivePrice;
        }
    }

    public function displayInstrumentPrice(MarketInstrument $instrument, float $rawLivePrice): float
    {
        if ($this->activeMarketplace() !== 'controlled') {
            return $rawLivePrice;
        }

        try {
            return $this->controlled->price($instrument);
        } catch (\Throwable) {
            return $rawLivePrice;
        }
    }

    public function requiresRegularSession(?string $marketplace = null): bool
    {
        return $this->normalizeMarketplace($marketplace ?: $this->activeMarketplace()) === 'live';
    }

    public function requiresRegularSessionFor(MarketInstrument|Stock|ForexPair|CryptoPair $asset, ?string $marketplace = null): bool
    {
        if ($this->normalizeMarketplace($marketplace ?: $this->activeMarketplace()) !== 'live') {
            return false;
        }

        return $this->instrument($asset)->isStock();
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
