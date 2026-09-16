# V5.26.4 — Route Reconciliation + Private Watchlist

Corrected clean-baseline installer after V5.26.2 and V5.26.3 both failed during preflight and wrote no source changes.

## Canonical customer investment domain

- /investments
- /account/investments
- /account/investments/portfolio
- /account/investments/performance
- /account/investments/transactions
- /account/investments/watchlist

## Private watchlist

Users can track:
- authoritative investment price,
- personal target price,
- distance to target,
- priority,
- notes.

## Legacy containment

Old InvestmentPlan BUY/SELL endpoints no longer mutate financial state.
Old /portfolio and /watchlist URLs resolve safely into the new account domain.

## Lab maintenance

Clear Test Events removes only admin-created valuation-event rows and admin valuation price rows, restores the last non-admin baseline price and revalues active holdings.
