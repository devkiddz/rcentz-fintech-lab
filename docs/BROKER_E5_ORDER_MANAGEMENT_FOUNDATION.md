# Broker E5 — Order Management Foundation

E5 introduces a broker-style order authority between customer intent and capital execution.

## Authority

`BrokerOrder` is the durable customer/order intent record. A customer request is not allowed to jump directly from presentation code into an asset executor in the future unified broker surface.

Flow:

`Customer intent -> BrokerOrder -> BrokerTradeContractEngine -> asset adapter -> unified MarketExecutionTransaction -> TradePosition / Holding / Wallet`

## Supported liquid assets

- Stocks: existing StockTradeExecutor and Stock position engine remain the equity-native implementation.
- Forex: E3 execution-grade quote, 1:1 cash-collateralized long exposure, 24/5 session authority.
- Crypto: E4 execution-grade quote, spot ownership, 24/7 execution authority.

All three normalize into `MarketExecutionTransaction` receipts.

## Production protections introduced

- per-user idempotency keys for customer order submission
- durable lifecycle states (`accepted`, `executing`, `filled`, `failed`, etc.)
- immutable-style order event trail
- canonical `MarketInstrument` identity
- explicit quantity modes per asset class
- completed orders require a unified execution receipt
- failure is recorded rather than hidden behind UI state

## Deliberate boundaries

E5 does not expose a customer trade screen yet and the installer never executes a trade. The customer workstation is layered on this authority in the next stage.

E5 is not a regulatory license, exchange membership, custody arrangement, best-execution policy, KYC/AML program, market-data license or external clearing relationship. Those are separate operational/regulatory requirements for a real-money brokerage business.
