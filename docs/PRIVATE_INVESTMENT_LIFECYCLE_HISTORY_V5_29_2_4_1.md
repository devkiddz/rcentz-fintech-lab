# V5.29.2.4.1 — Lifecycle History Preview + Full History

## Status

**IMPLEMENTED — browser acceptance required**

## Correction

V5.29.2.4 failed safely during preflight because its controller matcher was too strict for the current compact relation-loader syntax.

V5.29.2.4.1 targets the verified local structure exactly.

## Behavior

### Investment Manage page

- Lifecycle history preview is limited to the latest **5** events.
- Preview remains directly below the lifecycle action form.
- A **View lifecycle history** button opens the complete history.

### Full lifecycle history

- Dedicated Admin page.
- Newest lifecycle events first.
- Paginated at **25 records per page**.
- Shows:
  - timestamp
  - event type
  - calculation method
  - configured value
  - customers affected
  - total amount
  - reason

## Scope boundary

This patch changes query/presentation behavior only.

It does not change:
- lifecycle financial calculations
- wallet credits/debits
- investment price authority
- holdings
- reconciliation rules
