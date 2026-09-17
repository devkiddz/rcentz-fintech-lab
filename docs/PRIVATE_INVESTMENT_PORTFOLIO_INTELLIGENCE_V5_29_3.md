# V5.29.3 — Private Investment Portfolio Intelligence

## Status

Implemented and browser accepted. Checkpoint complete.

## Goal

Turn the Private Investment portfolio from a holdings list into a financial explanation surface.

The customer should be able to answer:

> Why did my portfolio move?

without mixing Private Investment economics with Trading, Copy Trading or Bot Trading.

## Data authority

This milestone uses existing canonical Private Investment records only:

- `private_investment_holdings`
- `private_investment_transactions`
- authoritative instrument pricing already reflected in holding valuation
- lifecycle `distribution` and `deduction` transactions
- subscription/redemption transaction history

No fake portfolio values and no new financial mutation path are introduced.

## Metrics

Portfolio summary:

- active holdings
- capital at work
- current value
- unrealized P/L
- realized P/L
- distributions
- deductions
- net performance
- total subscribed capital
- performance percentage
- available wallet balance

Per investment:

- units
- average entry price
- remaining cost basis
- total subscribed capital
- current value
- unrealized P/L
- realized P/L
- distributions
- deductions
- fees paid
- net performance
- recent movements

## Net performance rule

`net performance = unrealized P/L + realized P/L + distributions - deductions`

Transaction fees are displayed for transparency but are **not subtracted again** because:

- subscription fees are already reflected by reduced deployed value / holding valuation
- redemption fees are already reflected in realized P/L

Double-subtracting fees would understate performance.

## Engine boundary

V5.29.3 begins with Private Investments.

The universal portfolio shell will later connect:

1. Private Investments
2. Manual Trading
3. Copy Trading
4. Bot Trading

Each engine remains financially independent and exposes only its summary/activity contract to the shell.
