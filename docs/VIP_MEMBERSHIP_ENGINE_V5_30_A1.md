# V5.30.A1 — VIP Membership Engine Foundation

## Purpose
Create an independent VIP membership authority without adding a boolean VIP flag to the user record.

## Domain authority
VIP state belongs to the membership engine:

- vip_plans defines sellable/configurable membership plans.
- vip_entitlements defines the capabilities granted by each plan.
- vip_memberships records the customer membership lifecycle.
- VipAccessService resolves active membership and entitlement access.

users.is_vip is deliberately not used. A customer is VIP only when the membership authority resolves a currently active membership on an active plan.

## Membership lifecycle
Initial supported status vocabulary:

- pending
- active
- paused
- cancelled
- expired

The database uses strings rather than native database enums so lifecycle rules can evolve without destructive enum migrations.

## Entitlements
Entitlements are plan-owned feature keys such as future examples:

- signals.premium
- trading.priority_support
- withdrawals.priority_review
- giveaways.vip_pool

This patch does not grant any concrete entitlement yet. Admin plan management will define them in the next layer.

## Boundaries
This foundation intentionally does not include:

- plan CRUD UI
- customer subscription UI
- wallet/payment charging
- automatic renewal
- middleware/gates
- VIP-only route enforcement
- seeded plans or prices
- notifications

Those features must consume this authority rather than creating their own VIP state.

## Next phase
V5.30.A2 should add admin plan/entitlement management and membership activation/lifecycle controls before customer checkout is introduced.
