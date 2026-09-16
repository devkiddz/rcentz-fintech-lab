# V5.22 — Market Eligibility & Integrity Hardening III

V5.22 closes the remaining gap between **source eligibility metadata** and **canonical execution authority**.

## Contract

An instrument may exist in the shared stock catalog without being eligible for External Feed.

- `stocks.external_feed_enabled = false` means External Feed cannot provide an execution price for that instrument.
- `LiveMarketPriceProvider` now rejects internal-only instruments before any buy/sell execution can use their seeded/raw stock price.
- Internal/private instruments remain available through their Internal Feed / controlled price authority.
- This protection is at the provider boundary, so customer trading, admin trading, copy trading and bot trading inherit the same rule.

## Integrity command

`php artisan market:integrity` checks current exposure invariants:

- internal-only symbols must not have LIVE holdings,
- internal-only symbols must not have LIVE open positions,
- CONTROLLED holdings/positions must have a controlled instrument,
- holdings, positions and stock transactions must use a known marketplace identity.

Historical records are not rewritten by this hardening pass.

No database migration is required by V5.22. V5.21's migration must already be applied.
