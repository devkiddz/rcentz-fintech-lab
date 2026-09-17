# V5.28.3 — Investment Metadata CRUD Forms + Labels

## Status

**COMPLETE**

Verified in browser and checkpointed to Git.

**Git checkpoint:** `f246a4b`
**Commit:** `feat(investments): complete metadata CRUD forms and labels`

## Purpose

V5.28.3 synchronized the Investment admin forms with the richer Private Investment product model introduced during V5.28.

The Investment Engine already supported duration, return cycles, projected return ranges, and fee metadata. This milestone made those values fully manageable through the admin CRUD surfaces rather than leaving them as seed-only or hidden database values.

## Completed

### Create Investment
- [x] Instrument identity and classification
- [x] Opening/listing price and unit supply
- [x] Minimum and maximum investment
- [x] Investment duration
- [x] Return cycle
- [x] Projected minimum and maximum return per cycle
- [x] Lock period
- [x] Subscription, management and redemption fees
- [x] Description
- [x] Featured and visibility state

### Edit Investment
- [x] Existing instruments expose the same business metadata for editing
- [x] Duration and return-cycle values persist through update
- [x] Projected return ranges persist through update
- [x] Fee metadata persists through update
- [x] Supply and customer investment limits remain editable

### Form clarity
- [x] Explicit labels replace placeholder-only form meaning
- [x] Helper text explains duration, return-cycle, lock and pricing concepts
- [x] Pricing Authority form is labelled
- [x] Admin customer subscription/redemption forms are labelled
- [x] Underlying Asset creation form is labelled
- [x] Customer Investment Action shows subscription and redemption fee context

### Validation
- [x] Maximum investment must be greater than or equal to minimum investment when supplied
- [x] Return interval cannot exceed total investment duration
- [x] Projected maximum return cannot be below projected minimum return
- [x] Percentage fields remain bounded

### Admin registry
- [x] Price
- [x] Duration
- [x] Return range
- [x] Return interval
- [x] Asset count
- [x] Status

## Product rule preserved

Any meaningful investment business metadata introduced by the system must be controllable from the admin control plane where appropriate.

Seeded data is demo/reference data, not an alternative hidden source of business truth.

## Next milestone

**V5.29 — Investment Realism & Audit Hardening**

Planned scope:
1. realistic customer activity/history,
2. valuation and lifecycle events,
3. admin audit trail,
4. richer portfolio history,
5. wallet/units/fees/holding reconciliation,
6. presentation and final acceptance.

Business data introduced by V5.29 must remain admin-manageable.
