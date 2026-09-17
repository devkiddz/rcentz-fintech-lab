# V5.29.3.4 — Bot Trading Portfolio Integration

## Status

Implemented and browser accepted. Checkpoint complete.

## Goal

Complete the first universal Portfolio Intelligence shell by connecting Bot Trading as a fourth independent engine.

## Authority

Bot Trading portfolio data is read from:

- `trading_bots`
- `bot_subscriptions`
- `trading_bot_executions`
- `trade_positions`
- `trade_positions.context_type = trading_bot`

The existing `TradingBot::positions()` relationship already defines `context_type = trading_bot` as the canonical bot-position boundary.

## Metrics

Portfolio-level Bot Trading summary:

- bots
- active bots
- positions
- open / closed positions
- executions
- capital traded
- open capital
- realized P/L
- unrealized P/L
- net P/L
- return %
- wins / losses

Per bot:

- bot name
- stock
- strategy
- action
- status
- subscription status
- spend / max spend
- positions
- executions
- realized P/L
- unrealized P/L
- net P/L
- return %

## Customer-facing naming cleanup

The portfolio UI uses **Trading** rather than **Manual Trading**.

The backend context identifier remains `manual_trade` because it is an internal engine boundary, not customer-facing terminology.

## Universal portfolio shell

Connected:

1. Private Investments
2. Trading
3. Copy Trading
4. Bot Trading

Each engine keeps its own source-of-truth records and P/L rules.
