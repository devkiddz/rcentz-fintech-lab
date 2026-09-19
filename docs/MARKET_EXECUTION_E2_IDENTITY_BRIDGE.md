# Market Execution E2 — Identity Bridge

E2 extends the parent-first MarketInstrument architecture into the persisted execution ledger without enabling Forex or Crypto capital movement.

## Authority

MarketInstrument is now recorded on existing Stock holdings, Stock transactions, and TradePosition rows. Existing stock_id fields remain compatibility rails. TradePosition.stock_id becomes nullable so later Forex and Crypto position adapters can use the same parent position authority without pretending to be Stocks.

## Compatibility

StockTradeExecutor remains the native equity ledger and continues to create StockHolding and StockTransaction rows. E2 simply writes the canonical market_instrument_id alongside stock_id. TradePositionService does the same when a Stock transaction opens a position.

## Safety boundary

E2 does not enable Forex or Crypto buy/sell, does not alter wallet settlement semantics, and does not create any automatic trades. The E1 Forex and Crypto execution adapters remain blocked.

After applying the migration, run:

    php artisan markets:inspect-execution-identity

A green inspection requires zero missing parent identities, zero Stock parent mismatches, and nullable trade_positions.stock_id.
