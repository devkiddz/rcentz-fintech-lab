# V5.30.A2 — VIP Admin Control Layer

## Purpose
Give administrators a complete operating surface for the independent VIP membership authority introduced in V5.30.A1.

## Admin control surfaces
- VIP overview and plan registry.
- Create, update, activate/deactivate and safely delete unused plans.
- Add, update, enable/disable and remove plan entitlements.
- Membership registry with search, plan filter and status filter.
- Assign memberships to non-admin customers.
- Create memberships as pending or activate immediately.
- Activate, cancel or expire membership lifecycle records.

## Lifecycle authority
VipMembershipService centralizes lifecycle mutation. Active periods for the same customer cannot overlap. Plan duration can supply the membership end date when Admin leaves it blank.

The service never writes users.is_vip. VipAccessService remains the read authority for resolving current VIP access.

## Plan deletion safety
Plans with membership history cannot be deleted. They can be deactivated so historical membership records retain their plan relationship.

## Entitlements
Entitlement keys are plan-owned capability identifiers. Optional values are stored as JSON so future consumers can use limits or configuration without adding one-off columns.

## Boundaries
- No customer purchase/checkout flow.
- No wallet charging or renewal.
- No VIP middleware or route gates yet.
- No seeded plans, prices or entitlements.
- No new migration in A2; it consumes the V5.30.A1 tables.

## Next phase
V5.30.A3 should add the customer VIP marketplace/account experience and purchase/activation workflow using the A1 authority and A2 lifecycle service.
