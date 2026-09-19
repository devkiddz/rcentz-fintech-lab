# Market Execution E2.1 — Unified Execution Ledger Bridge

E2.1 removes the remaining Stock-only transaction identity from the parent execution architecture before Forex capital execution is enabled.

## Authority

`market_execution_transactions` becomes the cross-asset execution receipt authority keyed by `market_instrument_id`. Existing `stock_transactions` remain the mature equity-native ledger and are mirrored into the parent ledger with a unique native reference.

`TradePosition` keeps its legacy Stock transaction links for compatibility while gaining parent execution transaction links. `TradePositionEvent` gains the same parent transaction link.

## Why this bridge exists

A multi-asset position cannot use a foreign key that points only to `stock_transactions`. E2.1 creates the transaction authority that Forex and Crypto adapters can use without pretending to be Stocks and without breaking the existing Stock trading engine.

## Safety boundary

E2.1 moves no capital by itself and does not enable Forex or Crypto execution. The Forex and Crypto adapters remain blocked until their dedicated stages are installed.

After applying the migration, run:

    php artisan markets:inspect-execution-ledger

A green inspection requires a unified mirror for every Stock transaction and complete generic transaction linkage for existing position/event records.
