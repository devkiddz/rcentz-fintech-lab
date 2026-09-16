# Rcentz V5.13 — Marketplace Consistency & Acceptance

V5.13 closes the presentation/valuation gaps left after the Dual Marketplace foundation.

- Customer holdings, positions and recent transactions are scoped to the active marketplace.
- Live quote refreshes revalue Live holdings only.
- Controlled ticks/reset prices revalue Controlled holdings only.
- Admin trade records resolve CMP and chart history from each contract's own marketplace, even if the admin currently has the other marketplace active.
- Customer position cards no longer expose internal context_type as a user-facing "source" label; they show LIVE or CONTROLLED Market.
- php artisan market:acceptance performs a read-only dual-authority and financial-identity check.

No schema migration is required.
