# FOREX FX2 — Signal Engine Market-Instrument Wiring

FX2 generalizes the existing Signal Engine from a stock-only foreign key to the shared `MarketInstrument` authority established in FX1.

## Guarantees

- Existing stock Signals are backfilled to their stock `MarketInstrument` records.
- `stock_id` remains for compatibility, but becomes nullable for non-stock instruments.
- `market_instrument_id` becomes the Signal Engine's cross-asset identity.
- Stock analysis continues through `StockAnalysisService`.
- Forex analysis routes through `ForexMarketDataService` using verified stored FX OHLC history.
- The same deterministic analysis, qualification, setup, revision and lifecycle services are reused.
- Price precision comes from the instrument, so EUR/USD is not rounded like a stock.
- Automated scheduled discovery remains stock-only in FX2. Forex discovery is explicit with `signals:scan --asset=forex` until each pair has verified history.
- Forex Signals are kept in READY review state in FX2. Publication is blocked until a live spot-price lifecycle authority is installed; daily-history close is analysis data, not sufficient execution authority.

## Commands

Read-only intelligence inspection:

`php artisan signals:inspect-market-universe EURUSD`

Explicit Forex scan:

`php artisan signals:scan EURUSD --asset=forex --force`

The first command never creates a Signal. The second may create a READY Signal only when deterministic qualification permits automatic generation.

## Authority chain

MarketInstrument -> market-specific context provider -> SignalAnalysisEngine -> SignalQualificationService -> SignalSetupBuilder -> Signal

Stocks and Forex share the Signal engine, but they do not share feed/session semantics.
