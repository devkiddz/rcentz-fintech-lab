# V5.30.A2.1 — VIP Surface Integration

This phase closes the visibility gap between the V5.30.A1 membership authority and V5.30.A2 admin control layer.

## Customer integration
- Adds a customer VIP Membership route and read-only membership workspace.
- Adds VIP Membership to the customer sidebar.
- Adds a VIP status card to the customer dashboard.
- Adds a membership summary and Manage VIP action to Profile Settings.
- Shows current or most-recent membership state, lifecycle dates and active entitlements.
- Shows active VIP plans and their enabled entitlements without introducing payment mutations.

## Admin customer integration
- Customer Directory cards show the customer current or latest VIP record.
- Customer Directory cards include a direct Manage VIP action into the existing membership registry search.
- Customer detail view includes a VIP summary, lifecycle dates, enabled entitlements and management shortcut.

## Read authority
VipAccessService remains the single read authority. It understands eager-loaded vipMemberships so admin customer surfaces avoid N+1 membership queries while preserving the active-membership rules.

## Explicitly not included
- No wallet debit or checkout flow.
- No customer self-activation.
- No renewal, upgrade or downgrade mutation.
- No entitlement route gating yet.
- No new migration or dependency.

V5.30.A3 can add purchase/payment, activation, renewal and entitlement enforcement on top of the now-visible membership experience.
