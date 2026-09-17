# V5.29.3.3 — Copy Trading Portfolio Integration

## Status

Implemented and browser accepted. Checkpoint complete.

## Goal

Connect Copy Trading to the universal Portfolio Intelligence shell while keeping its strategy/allocation model separate from Manual Trading.

## Runtime boundary confirmed

Existing copied positions are identified by:

`trade_positions.context_type = copy_strategy`

and the existing `context_id` points to the related Copy Strategy.

Manual positions remain:

`trade_positions.context_type = manual_trade`

This milestone respects that observed runtime boundary.

## Data authority

Copy Trading metrics are built from:

- `trade_positions` filtered to `copy_strategy`
- `copy_strategies`
- latest follower `copy_relationships` per strategy
- existing TradePosition P/L accessors

## Metrics

Summary:

- strategies represented
- positions
- open / closed positions
- capital traded
- open capital
- allocation limit
- used allocation
- remaining allocation
- realized P/L
- unrealized P/L
- net P/L
- return %
- wins / losses

Per copied position:

- strategy
- provider
- stock
- direction
- marketplace
- status
- initial/open quantity
- entry price
- realized P/L
- unrealized P/L
- net P/L
- return %
- relationship status where available

## Engine boundary

The portfolio shell now connects:

1. Private Investments
2. Manual Trading
3. Copy Trading

Bot Trading remains the next engine integration.
