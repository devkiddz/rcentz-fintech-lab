# Rcentz Trading Wiring — V5.10

V5.10 establishes traffic lanes after the V5.9 engine separation.

## Canonical real-trade lane

All NEW real trading surfaces route through MarketTradeContractEngine:

- Customer BUY / SELL
- Admin strategy trade
- Direct admin trade
- Trade for user
- Copy trading
- Trading bots

MarketTradeContractEngine composes StockTradeExecutor and TradePositionService in one outer database transaction. A BUY cannot remain without its required living position, and a user-facing SELL cannot remain if position reconciliation throws.

## Legacy lane

Historical StockTradePlan rows remain compatible through LegacyStockExecutionEngine only. No new normal trade is intentionally routed to that engine.

## Non-financial lanes

ManualPerformanceEngine remains presentation-only. OutcomeScenarioEngine remains simulation-only. Neither mutates wallets, holdings, stock transactions, or TradePosition contracts.

## Deferred design

The proposed contract switcher between API-fed/live persisted prices and a manual-price contract mode is intentionally NOT implemented in V5.10. It requires its own product and ledger rules before wiring.
