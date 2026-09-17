# V5.29.UI.1 — Platform Blade UI Modernization Sweep

## Purpose
Bring the active logged-in Blade experience into the compact Rcentz workspace language without changing financial, trading, investment, KYC, support, or admin business rules.

## Audit scope
Reviewed the application view families under `resources/views`, including customer account/dashboard, Money, profile/KYC, support, notifications, stocks/trading, private investments, legacy investments, copy trading, AI bots, portfolio, and the nested admin workspaces.

Public/guest marketing and authentication pages keep their own presentation language. Email, PDF, installer, framework vendor and error templates are not forced into the dashboard workspace style.

## Direct upgrades in this patch
- Money shell wording and canonical `/money/*` route usage on active customer surfaces.
- Money overview/add/send/activity/connections language consistency.
- Crypto deposit rebuilt into the current workspace composition.
- Support rebuilt into the current workspace composition.
- Profile and KYC outer workspace/card conventions tightened.
- Customer mobile navigation now says Money and uses the canonical Money route.
- Admin mobile navigation now says Transactions.
- WalletController success redirects use canonical Money routes while legacy `/wallet/*` endpoints remain compatible.
- Customer dashboard Money links use canonical routes.

## Compatibility modernization layer
A scoped CSS layer normalizes older logged-in Blade surfaces that still use legacy Tesla/gray cards, gradients, oversized shadows, light typography and legacy borders. It applies only inside `.customer-workspace` and `.admin-workspace`.

This gives old CRUD/detail screens the current neutral card/token language while they retain their existing forms, variables, actions and controller contracts.

## Boundaries
- No schema changes.
- No financial mutation changes.
- No withdrawal-security workflow changes.
- No route removals.
- No dependency changes.
- No `npm install` required.
