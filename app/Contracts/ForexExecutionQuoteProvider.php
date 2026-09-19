<?php

namespace App\Contracts;

use App\Models\ForexPair;

interface ForexExecutionQuoteProvider
{
    public function key(): string;

    public function isAvailable(): bool;

    /**
     * Return an execution-grade quote payload for the pair.
     * Required keys: rate, bid, ask, captured_at, source.
     */
    public function quote(ForexPair $pair): array;
}
