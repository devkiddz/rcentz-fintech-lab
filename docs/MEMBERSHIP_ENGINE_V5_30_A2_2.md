# V5.30.A2.2 — Membership Engine Generalization

This phase corrects the original VIP-specific core and makes Membership the actual authority.

## Core domain

- MembershipType
- MembershipPlan
- MembershipEntitlement
- Membership
- MembershipAccessService
- MembershipService

VIP is now a row in `membership_types` with slug `vip`; it is not a separate membership engine.

## Database migration

The migration preserves the existing VIP data and IDs by renaming the existing tables and keys:

- `vip_plans` → `membership_plans`
- `vip_entitlements` → `membership_entitlements`
- `vip_memberships` → `memberships`
- `vip_plan_id` → `membership_plan_id`

A new `membership_types` table is created and all migrated plans are attached to the VIP type.

## Lifecycle authority

A customer may have active memberships in different membership types at the same time. Overlap protection is scoped to one membership type, so VIP and Signals can coexist while two overlapping VIP memberships are rejected.

## Routing

Customer:
- `/memberships`
- `/memberships/{type}`

Admin:
- `/admin/memberships`
- `/admin/memberships/{type}`
- `/admin/memberships/{type}/memberships`

The URLs for VIP remain `/memberships/vip` and `/admin/memberships/vip`, but the route implementation is now data-driven rather than VIP-specific.

## Admin authority

Admins can create and manage membership types, plans, entitlements and customer memberships from the shared Membership control plane. Adding a new membership type no longer requires a new model/service stack.

## Next phase

V5.30.A3 — Membership Purchase & Payment Authority can now connect the Money engine to this generic Membership authority once, then serve VIP and future membership types.
