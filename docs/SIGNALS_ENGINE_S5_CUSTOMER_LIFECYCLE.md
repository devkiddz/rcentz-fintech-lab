# Signals S5 — Customer Lifecycle Communication

## Purpose

This phase closes the gap between Signal lifecycle authority and the customer experience.

The persistence chain remains:

```text
Signal / SignalTarget
        ↓
SignalEvent / SignalRevision
        ↓
SignalDelivery
        ↓
Customer workspace
```

`SignalDelivery` remains the ownership/access authority. A notification never grants access to a Signal.

## Event-driven customer notifications

`SignalEventObserver` listens for newly persisted material Signal events and asks `SignalCustomerNotificationService` to alert only users who already hold a matching `SignalDelivery`.

Customer-facing material event types are:

- `activated`
- `adjusted`
- `target_hit`
- `stopped`
- `expired`
- `invalidated`
- `cancelled`
- `closed`

The observer is deliberately fail-safe. A notification failure is logged and must not roll back or become authority over the Signal lifecycle. Missing alerts can be recovered from the immutable event ledger.

## Idempotent recovery

`signals:sync-customer-notifications` backfills missing alerts from `SignalEvent` records.

The default event filter is `adjusted`, which is useful after introducing this layer to an already-running Signal. Use `--event=all` only when a full material-event recovery is intentionally required.

Dry-run example:

```text
php artisan signals:sync-customer-notifications 1 --event=adjusted --dry-run
```

Actual recovery:

```text
php artisan signals:sync-customer-notifications 1 --event=adjusted
```

Every generated lifecycle alert stores `signal_event_id`, so recovery can skip existing notifications instead of duplicating them.

## Customer Signal timeline

The customer Signal detail page now renders a readable timeline derived from the Signal's immutable events and revisions. Internal analysis snapshots and actor ids are not exposed.

For revisions, the timeline shows customer-relevant contract changes such as:

- strength
- confluence
- entry boundaries
- stop loss
- risk/reward
- expiry
- target prices

The event/revision tables remain authoritative; the timeline is only a presentation layer.

## Boundary

This phase does not:

- publish a Signal
- distribute a Signal
- grant membership access
- debit or credit a wallet
- execute a trade
- calculate fictional P&L
- create new lifecycle events

It communicates lifecycle truth that already exists.

## Acceptance checkpoint — 2026-09-18

The lifecycle communication layer is installed and its recovery path is dry-run accepted against the existing AAPL adjustment. The dry-run found one `adjusted` event, two matching deliveries, two missing customer alerts and zero failures. The real backfill command remains intentionally pending until final browser acceptance; once run, a second dry-run must report zero would-create and two existing alerts.
