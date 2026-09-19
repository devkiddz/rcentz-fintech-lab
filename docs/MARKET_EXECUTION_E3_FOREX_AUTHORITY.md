# Market Execution E3 — Forex Capital Authority

E3 makes Forex a real execution participant without pretending a currency pair is a Stock.

MarketInstrument remains the parent identity. ForexExecutionAdapter delegates to ForexExecutionService. Forex exposure is stored in MarketHolding and every fill is recorded in market_execution_transactions.

Live execution is fail-closed. Analysis candles/current_rate are not accepted as execution-grade prices. Live fills require a fresh realtime quote from the configured Forex execution quote provider and an open 24/5 Forex session. Cross-currency settlement requires fresh currency conversion rates. Controlled Market execution remains available through Controlled Market price authority.

The first Forex execution model is a 1:1 cash-collateralized long FX exposure. It is deliberately non-leveraged and is not represented as ownership of the base currency. Quantity may be supplied as base-currency units or standard lots (100,000 units per lot, minimum 0.01 lot). Short selling, leverage/margin, broker routing and automatic Signal execution remain disabled.

The unified transaction ledger supports idempotency keys so retried requests cannot intentionally duplicate a wallet debit/credit. Wallet movement, MarketHolding mutation and execution receipt creation occur inside one database transaction.

MarketPositionService creates and manages non-Stock TradePosition rows using market_instrument_id and unified execution receipt links. The scheduled market-positions:process command handles Forex stop loss, take profit and time-expiry checks without using Stock session rules.
