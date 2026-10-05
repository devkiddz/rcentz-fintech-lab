<?php

return [
    // Activation is a separate release step, after ledger and automation acceptance.
    'enabled' => env('PAPER_TRADING_ENABLED', true),
    'lifecycle_enabled' => env('PAPER_TRADING_LIFECYCLE_ENABLED', true),
    'copy_enabled' => env('PAPER_TRADING_COPY_ENABLED', true),
    'bots_enabled' => env('PAPER_TRADING_BOTS_ENABLED', true),
    'fee_basis_points' => (float) env('PAPER_TRADING_FEE_BPS', 0),
    'stock_max_quote_age_seconds' => 300,
];
