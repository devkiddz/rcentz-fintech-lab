# Rcentz V5.11 — Dual Marketplace Foundation

V5.11 separates **price authority** from the canonical trading engine.

- `Live Market` keeps external/API-fed prices in `stocks.current_price` and existing quote/history tables.
- `Controlled Market` owns independent prices in `controlled_market_instruments` and history in `controlled_market_ticks`.
- `MarketPriceRouter` selects which price authority the application reads.
- `StockTradeExecutor` records the marketplace on each new transaction.
- `TradePosition` records the marketplace on each new contract.
- Controlled Market does not require the regular U.S. session to advance or execute.
- Live Market retains the existing regular-session protection.

For integrity, global marketplace switching is blocked while any open position or stock holding exists. This keeps one financial exposure from changing price authorities mid-contract. The schema records marketplace origin now so a future version can safely support simultaneous live/controlled exposures if product requirements call for it.

Controlled instruments are initialized from existing active stocks during migration. New controlled instruments need only a symbol, label and starting price. Precision, minimum tick, minimum price, volatility and neutral bias are derived automatically. Asset class defaults to equity because this module is the stock marketplace.
