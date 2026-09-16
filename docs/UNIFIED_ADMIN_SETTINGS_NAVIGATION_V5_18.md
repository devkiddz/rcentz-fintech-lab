# Rcentz V5.18 — Unified Admin Settings Navigation

V5.18 removes the second Settings Control Plane navigation and makes the primary admin sidebar the single navigation authority.

## Navigation

The primary admin sidebar now owns a nested **System Settings** group:

- Overview
- General
- Appearance
- Market
- Trading
- Security
- Mail & Notifications
- Integrations
- System

Desktop and mobile use the same admin navigation system. There is no second settings drawer, rail, or control panel.

## Route model

All settings pages are selected through one read route:

`GET /admin/settings?section=<section>`

The section parameter selects the focused settings surface. Mutation routes remain scoped by responsibility, for example:

- `PATCH /admin/settings/general`
- `PATCH /admin/settings/appearance`
- `PATCH /admin/settings/security`
- `PATCH /admin/settings/mail`
- `POST /admin/settings/market/source`
- `POST /admin/settings/market/movement`
- system maintenance action routes

This keeps navigation routes small without collapsing unrelated write contracts into one oversized endpoint.

## Future settings

Future bot policy, copy-trading policy, verification/approval rules, approved trading accounts and audit/compliance settings can be added as new sections under the same **System Settings** sidebar group and selected through the same read route.

No database migration is required.
