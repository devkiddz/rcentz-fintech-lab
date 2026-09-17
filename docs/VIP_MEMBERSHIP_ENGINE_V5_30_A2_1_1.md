# V5.30.A2.1.1 — Membership Route Architecture Correction

This correction completes the A2.1 surface integration by making Memberships the parent domain and VIP a nested membership type.

## Customer canonical routes

- /memberships → memberships.index
- /memberships/vip → memberships.vip.index

## Admin canonical routes

- /admin/memberships → admin.memberships.index
- /admin/memberships/vip → admin.memberships.vip.index
- /admin/memberships/vip/memberships → admin.memberships.vip.memberships
- VIP plan, entitlement and lifecycle mutations remain nested under admin.memberships.vip.*

## Navigation

Customer sidebar:
Memberships → Overview / VIP Membership

Admin sidebar:
Memberships → Overview → VIP Membership → Plans & Entitlements / Memberships

## Authority

VIP remains membership-authoritative through VipMembership and VipAccessService. No users.is_vip shortcut is introduced. Admin remains the mutation authority. Customer membership surfaces are read-only until the purchase/payment phase.
