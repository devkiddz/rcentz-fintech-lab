# V5.29.A2.2 — Admin Customer Directory UI Cleanup

## Status

**IMPLEMENTED — visual acceptance required**

## Scope

- Rebuilt Admin → Users into a cleaner customer control surface.
- Removed always-open access forms from every card.
- Access controls now live inside a compact expandable panel.
- Added customer/email search.
- Added quick filters for Active, Restricted, Email Unverified and Investors.
- Consolidated profile, status, optional identity metadata and balances.
- Kept View, Edit, Alert, Verify Email, Login As and Delete actions.
- Preserved Block / Suspend / Ban / Activate authority.
- No controller, route, database or financial behavior changed.

This patch is UI-only and intentionally does not alter the V5.29 withdrawal or investment engines.
