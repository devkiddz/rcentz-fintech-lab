# Rcentz V5.20 — Trading Source-of-Truth Hardening I

This hardening pass closes several marketplace-boundary gaps before the Private Investment Market work begins.

## Changes

- Internal/controlled instruments continue to tick on their own schedule even when External Feed is the active browsing/execution source.
- Internal tick broadcasts are emitted only while Internal Feed is the visible source, preventing cross-source UI price contamination.
- Stock buy/sell emails now revalue and total only the portfolio associated with the completed transaction's marketplace.
- Customer transaction history is scoped to the active marketplace, matching Portfolio and Position Desk behavior.
- Admin strategy, direct-admin, and trade-for-user SELL actions select positions from the active marketplace before closing them.

## Contract

Live and controlled exposures remain financially isolated. Switching the visible/active price source does not freeze the inactive market's own price history and does not allow a SELL in one market to accidentally close exposure from the other.

No database migration is required.
