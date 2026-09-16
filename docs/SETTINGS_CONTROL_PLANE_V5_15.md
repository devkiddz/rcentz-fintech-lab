# Rcentz V5.15 — Settings Control Plane

`/admin/settings` is now the indexed administrative configuration home.

## Available settings routes

- `/admin/settings` — overview
- `/admin/settings/general`
- `/admin/settings/appearance`
- `/admin/settings/market`
- `/admin/settings/trading`
- `/admin/settings/security`
- `/admin/settings/mail`
- `/admin/settings/integrations`
- `/admin/settings/system`

Generic settings save only their own database group. Market settings continue to write to the dedicated market-environment domain rather than the generic `settings` key/value table.

## Operational separation

`/admin/trading/marketplace` is now a Market Operations surface: instrument registration, price reset, pause/activate and manual tick. Price-source selection and automatic movement configuration live under `/admin/settings/market`.

## Reserved domains

The central registry already reserves navigation slots for:

- Bot Automation
- Copy Trading
- Verification & Approvals
- Approved Trading Accounts
- Audit & Compliance

They are intentionally not fake routes or fake forms. When their business contracts are ready, each can be enabled by adding its controller/route and flipping its registry entry to available.

## Route policy

- `GET` reads a settings surface.
- `PATCH` updates persistent configuration in one domain.
- `POST` performs an action or changes a specialized domain state.
- Operational trading actions remain outside the generic settings table.
