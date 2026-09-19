# Broker E6 — Unified Customer Trading Surface

E6 turns the completed Stock / Forex / Crypto execution authorities into one coherent customer brokerage experience without collapsing the asset-specific execution rules.

## Product boundary

The Instruments workspace remains market discovery and analysis. It presents the three liquid asset classes, market marks, movement and execution readiness. The Broker workstation owns customer order entry.

## Order flow

Customer -> BrokerOrder -> BrokerTradeContractEngine -> asset-specific execution adapter -> MarketExecutionTransaction -> Holding / TradePosition / Wallet.

Position closes also create a BrokerOrder first, including partial closes, so the presentation layer cannot bypass order authority merely because exposure already exists.

## Unified customer surfaces

- Workstation: one market-order ticket for Stocks, Forex and Crypto with asset-specific quantity modes.
- Portfolio: StockHolding plus MarketHolding normalized into one liquid-market portfolio.
- Positions: one position desk for all three asset classes with risk management and broker-order closes.
- Orders: durable customer intent and lifecycle.
- Execution Activity: unified MarketExecutionTransaction receipts, including pre-E5 Stock history mirrored by E2.1.

## Pricing truth

The UI distinguishes market marks from executable quotes. Portfolio and discovery pages may use market marks for presentation. Capital execution still fails closed until the underlying adapter obtains and validates the asset's execution quote/session requirements.

## Deliberate boundaries

- long/cash exposure only at this stage
- no leverage, shorts or derivatives
- no automatic Signal-to-order execution
- no silent fallback from execution-grade quotes to daily analysis candles
- Private Investments remain a separate product domain
