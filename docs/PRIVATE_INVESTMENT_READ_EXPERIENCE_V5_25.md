# V5.25 — Private Investment Read Experience

V5.25 turns the V5.24 foundation into the canonical customer-facing investment marketplace.

## Important boundary

This milestone is read-only.

The old InvestmentPlan buy/sell engine is intentionally **not** wired to the new PrivateInvestmentInstrument domain. The new details page therefore does not expose subscription/redemption buttons yet. V5.26 will create the dedicated private-investment transaction engine.

## Universal chart shell

Investment history is transformed into the same analysis payload consumed by Rcentz Lightweight Charts:

- line
- candles
- area
- 1W / 1M / 3M / ALL

The chart renderer is shared presentation infrastructure; investment valuation remains a separate domain engine.

## UI direction

These surfaces start the Tesla-oriented cleanup:

- white/light primary surfaces,
- restrained charcoal,
- red accent for identity and action,
- fewer dark blocks,
- compact typography and spacing.
