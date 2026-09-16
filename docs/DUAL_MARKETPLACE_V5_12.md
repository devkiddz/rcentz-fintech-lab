# Rcentz V5.12 — Marketplace Financial Isolation

V5.12 completes the missing financial boundary between Live and Controlled marketplaces.

## Holding identity

Stock holdings are now keyed by:

`user_id + stock_id + marketplace`

This allows the same user to hold the same instrument independently in Live and Controlled markets without combining quantities or cost basis.

Existing holdings migrate as `live`.

## Marketplace switching

The global marketplace switch now controls the active browsing/new-execution environment and is no longer blocked by existing exposure.

Existing positions and holdings retain their own marketplace identity. Closing a known position uses that position's marketplace authority.

## Live diagnostics

`php artisan market:live-refresh` now reports how many active instruments were refreshed during that run rather than showing only the latest quote row.
