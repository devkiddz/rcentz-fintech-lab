# Signals Engine S5 — Customer Experience

## Authority

A customer sees a Signal only when a persisted `SignalDelivery` exists for that customer and Signal. Notification records are alerts, not ownership or access authority.

## Customer surfaces

- `/signals` — current delivered Signals in `published` or `active` lifecycle states.
- `/signals/history` — delivered terminal Signals.
- `/signals/{signal}` — ownership-bound Signal room with market chart, entry, stop, targets, strength, confluence and delivery record.
- Dashboard — compact cards for the most relevant current delivered Signals.
- Notification bell — Signal alerts link to the ownership-bound Signal room.

## Read semantics

Opening a customer Signal room stamps `SignalDelivery.read_at`. Notification read state remains separate and is managed through the shared Notification system.

## Boundaries

- Signal display never opens a trade or debits a wallet.
- Membership controls distribution eligibility; a persisted delivery is the customer-facing authority for an already delivered Signal.
- Trade execution integration remains S6.

## Accepted implementation checkpoint — 2026-09-18

Customer Signal ownership-bound routing is browser accepted. Delivered customers can open Current, History and Signal detail surfaces; users without a matching `SignalDelivery` cannot view the Signal. The shared notification bell now hydrates from the existing notification API, supports unread state/read actions, and deep-links Signal alerts to the customer Signal room. Dashboard presentation is intentionally compact; richer Signal identity/summary polish remains optional UI work rather than an authority change.
