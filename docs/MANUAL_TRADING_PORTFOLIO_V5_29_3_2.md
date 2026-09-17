# V5.29.3.2 — Manual Trading Portfolio Integration

## Status

Implemented and browser accepted. Checkpoint complete.

## Goal

Connect the Manual Trading engine to the universal Portfolio Intelligence shell without mixing trading economics into Private Investments.

## Authority

Manual Trading portfolio data is read only from canonical trading records:

- `trade_positions`
- `trade_position_events`
- `context_type = manual_trade`
- `TradePosition::current_profit_loss`
- `TradePosition::current_return_percent`

Copy Trading positions are explicitly excluded because their context is `copy_strategy`.

## Metrics

Manual Trading summary:

- total positions
- open positions
- closed positions
- capital traded
- open capital
- realized P/L
- unrealized P/L
- net P/L
- return percentage
- wins
- losses

Per position:

- stock
- direction
- marketplace
- status
- initial quantity
- open quantity
- entry price
- initial capital
- open capital
- realized P/L
- unrealized P/L
- net P/L
- return percentage

## Engine boundary

Private Investments and Manual Trading now contribute separate summaries to the same portfolio surface.

The shell does not merge their internal accounting rules.

Next engine integrations:

1. Copy Trading
2. Bot Trading
