<?php

namespace App\Contracts;

use App\Models\MarketInstrument;
use App\Models\User;

interface MarketExecutionAdapter
{
    public function assetClass(): string;

    public function supports(MarketInstrument $instrument): bool;

    public function capabilities(MarketInstrument $instrument): array;

    /**
     * Execute against the adapter-native ledger.
     *
     * E1 establishes the parent execution contract. Only the Stock adapter is
     * executable in E1; Forex and Crypto remain explicitly non-executable until
     * their dedicated execution stages are implemented.
     */
    public function execute(
        User $user,
        MarketInstrument $instrument,
        string $side,
        float $quantity,
        array $context = []
    ): mixed;
}
