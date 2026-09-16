# V5.21 — Market Source Eligibility Hardening II

This hardening pass separates **instrument existence** from **external-feed eligibility**.

## Why

The private/internal market can contain synthetic or privately-defined symbols. Those symbols must not be sent to Finnhub/Yahoo merely because they are active `stocks` rows.

## Contract

- `stocks.external_feed_enabled = true` means the instrument may be refreshed by the external quote job.
- New symbols created by the Internal Feed desk default to `false`.
- Existing public stocks retain their current eligibility.
- Migration backfill marks controlled instruments with no historical external quote as internal-only.
- External market-status gainers/losers/most-active exclude internal-only symbols.
- Internal price authority remains independent and continues to value its own holdings.

This prevents accidental external API requests from becoming a hidden source of truth for privately-created instruments.
