<?php

namespace App\Contracts;

use App\Models\CryptoPair;

interface CryptoExecutionQuoteProvider
{
    public function key(): string;

    public function isAvailable(): bool;

    /**
     * Return an execution-grade quote payload for the pair.
     * Required keys: rate, bid, ask, captured_at, source.
     */
    public function quote(CryptoPair $pair): array;
}
