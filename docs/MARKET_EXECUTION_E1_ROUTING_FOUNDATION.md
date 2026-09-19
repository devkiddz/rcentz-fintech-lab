# Market Execution E1 — Routing Foundation

E1 introduces a MarketInstrument-first execution boundary without changing wallet balances, holdings, positions, or current Stock execution behavior.

## Authority

MarketInstrument is the canonical execution identity. MarketExecutionRouter chooses an asset-class adapter.

- StockExecutionAdapter delegates to the existing mature StockTradeExecutor.
- ForexExecutionAdapter is present but intentionally non-executable until the Forex execution stage.
- CryptoExecutionAdapter is present but intentionally non-executable until the Crypto execution stage.

This prevents Forex or Crypto from being disguised as Stocks while giving the trading system one parent execution entry point.

## E1 safety boundary

E1 performs no schema migration and no automatic trade. It does not alter StockHolding, StockTransaction, TradePosition, wallet settlement, trading bots, copy trading, or Signal behavior.

Use `php artisan markets:inspect-execution-authority` to verify routing without moving capital.

## Next stages

E2 generalizes position/exposure identity to MarketInstrument while preserving Stock compatibility. E3 implements Forex execution semantics. E4 implements Crypto execution semantics. Portfolio and membership hardening follow only after execution contracts are proven.
