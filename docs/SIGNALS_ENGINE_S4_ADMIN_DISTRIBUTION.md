# Signals Engine — S4 Admin, Distribution & Notifications

## Status

**Phase:** S4 — Admin, Distribution & Notifications
**Roadmap:** Signals S1 → S6
**Purpose:** Expose a complete administrator control plane for Signal supervision, publication, audited distribution and dashboard notification delivery.

## Boundary

S4 controls Signal publication and customer delivery. It does not execute trades or debit wallets.

S4 also does not activate the customer Signal workspace yet. Customer Active/History/detail pages and autonomous scheduler runtime remain S5.

```text
S3 Signal Authority
      ↓
READY Signal
      ↓
Admin Signal Desk
      ├── Re-analyze
      ├── Edit with immutable revision
      ├── Publish
      ├── Cancel / Close
      └── Distribute
             ↓
      SignalDistribution
             ↓
        SignalDelivery
             ↓
         Notification
```

## Admin Signal Desk

S4 activates the existing admin Signal Engine navigation and introduces five surfaces:

- Overview
- Generated Candidates
- Live Signals
- History
- Engine Activity

Each Signal has a dedicated detail/control page showing:

- instrument and marketplace;
- direction and timeframe;
- status, strength and confluence;
- entry zone, stop loss and targets;
- generated, published, activated and expiry times;
- analysis runs;
- immutable revisions;
- lifecycle events;
- distribution batches.

## Manual Signals

Administrators can create manual Signals using the same persistence model as automatic Signals.

Manual creation validates the trade contract:

- BUY stop loss must be below the entry zone;
- SELL stop loss must be above the entry zone;
- BUY targets must rise above entry;
- SELL targets must fall below entry;
- targets must remain directionally ordered;
- expiry must be in the future.

Manual Signals enter `ready` state. Creation never publishes or distributes by itself.

## Immutable Editing

Ready and published Signals may be edited by an administrator.

Material edits go through `SignalRevisionService`, producing a numbered immutable revision and an `adjusted` event.

Published direction and timeframe are locked. Active and terminal Signals cannot be manually edited from the S4 contract form.

## Publication

Publication is explicit:

```text
READY
  ↓ admin publish
PUBLISHED
  ↓ S3 lifecycle sees entry reached
ACTIVE
```

Publishing does not distribute automatically.

This preserves the authority boundary between Signal validation, publication and customer delivery.

## Distribution

S4 supports two audited distribution modes.

### Membership distribution

Normal distribution uses the existing Membership engine and requires an enabled `signals.access` entitlement on at least one active membership.

S4 intentionally applies only this coarse access gate.

Full membership policy remains S6, including:

- usage quotas;
- allowed markets;
- allowed instruments;
- plan-specific Signal breadth;
- final idempotency and concurrency hardening.

### Complimentary distribution

Administrators may send a Signal to one customer or all active customers as a complimentary override.

Complimentary delivery:

- bypasses `signals.access`;
- is written as `reason = complimentary`;
- does not consume normal membership allowance;
- remains fully auditable through `SignalDistribution` and `SignalDelivery`.

The unique `(signal_id, user_id)` delivery authority prevents duplicate delivery of the same Signal to one customer.

## Notifications

Each successful Signal delivery creates a dashboard notification using the existing Notification infrastructure.

Notification type:

```text
signal
```

The notification carries Signal identity, instrument, direction, timeframe, entry zone, stop loss, targets and delivery reason.

S5 will connect these delivered Signals to the customer Signal workspace.

## Admin Commands

S4 provides a non-mutating inspection command:

```powershell
C:\xampp\php\php.exe artisan signals:inspect-desk
```

The distribution integration can be exercised safely inside a rolled-back transaction:

```powershell
C:\xampp\php\php.exe artisan signals:inspect-desk --exercise-distribution
```

A successful probe must include:

```text
DISTRIBUTION_ROLLBACK_PROBE=PASS
SIGNALS_S4_DESK_OK
```

The probe creates a real complimentary delivery and dashboard notification inside an outer database transaction, verifies both rows, then rolls the complete probe back.

## Next Phase

**S5 — Customer Experience & Performance**

S5 will add customer Active/History/detail Signal pages, autonomous scheduler runtime and real outcome/performance tracking.


## Delivery Management & Administrator Receipts

S4 delivery administration distinguishes two audience scopes:

- `individual`: a complimentary distribution sent to one explicitly selected customer;
- `general`: membership distribution or a complimentary distribution sent to the broader active-customer audience.

Every new distribution records its `audience_scope` in the distribution snapshot and each delivery metadata payload. Historical S4 deliveries created before this field existed remain readable through the distribution mode and requested-recipient count.

The Admin Signal Recipients table is an audit/control surface. It does not delete historical delivery truth. Administrators can filter by Individual / General scope, distribution mode, Signal lifecycle state, customer, asset and Signal ID, then drill into the Signal Room or customer record.

Every completed distribution also creates a separate `signal_admin` notification for the administrator who initiated it. Customer Signal notifications remain type `signal`, so customer delivery counts and administrator operational receipts stay independently measurable.

## Accepted implementation checkpoint — 2026-09-18

S4 has been exercised with real persisted data. Signal #1 progressed through generated → ready → published → active. Two complimentary customer deliveries were created successfully, Recipients management is browser accepted, customer Signal notifications remain type `signal`, and the initiating administrator receives a separate `signal_admin` operational receipt. Current accepted persisted counts at the S4/S5 checkpoint are two distributions, two deliveries and one admin Signal receipt.
