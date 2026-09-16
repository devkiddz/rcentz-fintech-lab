# V5.23.1 — Explicit Market Context Hardening IV

Corrected V5.23 baseline installer. The original V5.23 installer failed during preflight and wrote no source changes.

## Changes

- Live analysis fallback now reads `Stock::live_current_price` instead of active-market `Stock::current_price`.
- Admin holding detail resolves price through the holding's own marketplace.
- Admin holding records visibly identify **External Feed** vs **Internal Feed**.
- Legacy holding presentation uses `company_name` and `average_buy_price`.

No database migration is required.
