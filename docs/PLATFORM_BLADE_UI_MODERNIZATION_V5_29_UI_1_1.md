# V5.29.UI.1.1 — Trading & History Blade Cleanup

## Scope
This follow-up directly modernizes four customer-facing pages identified during browser review of V5.29.UI.1:

- Trading transactions
- Trading watchlist
- Trading positions
- Dashboard purchase history

## UI changes
- Replaces the remaining legacy Tesla-gradient page headers with the current Rcentz workspace header pattern.
- Normalizes filters, tables, cards, buttons, empty states and modal surfaces to the existing ui-* primitives.
- Simplifies customer copy and removes developer-facing EMP/CMP/contract language from the Positions screen while preserving the same price and P/L data bindings.
- Removes the duplicated open-position action strip; the existing position actions remain available in the card action area and management dialog.
- Keeps all existing routes, controller contracts, forms, variables, pagination and mutation endpoints intact.

## Boundaries
- No controller changes.
- No route changes.
- No database migrations.
- No dependency changes.
- No trading accounting or settlement logic changes.
- No V5.29.UI.1 files outside these four Blade screens are rewritten.
