# Market Execution E4 — Crypto Spot Authority

E4 makes active Crypto MarketInstrument rows real execution participants without treating digital assets as Stocks or Forex contracts.

MarketInstrument remains the parent execution identity. CryptoExecutionAdapter delegates to CryptoExecutionService. Crypto ownership/exposure is stored in the shared MarketHolding table and every fill is written to the unified market_execution_transactions ledger.

Live Crypto execution is fail-closed. Daily Crypto candles/current_rate remain analysis data and are not accepted as Live execution prices. Live fills require a fresh realtime CURRENCY_EXCHANGE_RATE quote from the configured Alpha Vantage provider. The quote path supports cryptocurrency-to-physical-currency pairs and validates timestamp freshness before capital can move. Controlled Market execution continues to use Controlled Market price authority.

The E4 execution model is spot ownership only. A BUY acquires asset units and debits the user's settlement wallet; a SELL requires sufficient owned MarketHolding quantity and credits the settlement wallet. Short selling, leverage/margin, derivatives, lending, staking and automatic Signal execution remain disabled.

Crypto quantity may be expressed as asset units or as a desired settlement-currency amount. Unified execution idempotency is enforced so retries cannot duplicate a wallet debit or credit. Wallet movement, MarketHolding mutation and execution receipt creation occur inside one database transaction.

MarketPositionService now processes both Forex and Crypto non-Stock positions. Forex retains 24/5 session handling; Crypto uses continuous 24/7 lifecycle checks. Stop loss, take profit and time expiry remain position-contract controls and do not create automatic Signal-to-trade behavior.

E4 requires no schema migration because E3 already installed the shared MarketHolding and unified execution ledger fields needed by non-Stock assets.
