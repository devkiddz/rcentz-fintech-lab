<?php

namespace App\Services;

use App\Contracts\MarketExecutionAdapter;
use App\Models\MarketInstrument;
use App\Models\User;
use App\Services\Execution\CryptoExecutionAdapter;
use App\Services\Execution\ForexExecutionAdapter;
use App\Services\Execution\StockExecutionAdapter;
use RuntimeException;

final class MarketExecutionRouter
{
    public function __construct(
        private readonly StockExecutionAdapter $stocks,
        private readonly ForexExecutionAdapter $forex,
        private readonly CryptoExecutionAdapter $crypto
    ) {}

    public function adapterFor(MarketInstrument $instrument): MarketExecutionAdapter
    {
        return match ($instrument->asset_class) {
            MarketInstrument::ASSET_STOCK => $this->stocks,
            MarketInstrument::ASSET_FOREX => $this->forex,
            MarketInstrument::ASSET_CRYPTO => $this->crypto,
            default => throw new RuntimeException('No execution adapter is registered for asset class '.$instrument->asset_class.'.'),
        };
    }

    public function capabilities(MarketInstrument $instrument): array
    {
        return $this->adapterFor($instrument)->capabilities($instrument);
    }

    public function canExecute(MarketInstrument $instrument): bool
    {
        $capabilities = $this->capabilities($instrument);

        return (bool) ($capabilities['executable'] ?? false)
            && $this->adapterFor($instrument)->supports($instrument);
    }

    public function execute(
        User $user,
        MarketInstrument $instrument,
        string $side,
        float $quantity,
        array $context = []
    ): mixed {
        if (! $instrument->is_active) {
            throw new RuntimeException('MarketInstrument is inactive and cannot be executed.');
        }

        $adapter = $this->adapterFor($instrument);
        $capabilities = $adapter->capabilities($instrument);

        if (! ($capabilities['executable'] ?? false) || ! $adapter->supports($instrument)) {
            throw new RuntimeException((string) ($capabilities['reason'] ?? 'Execution is not available for this instrument.'));
        }

        return $adapter->execute($user, $instrument, $side, $quantity, $context);
    }
}
