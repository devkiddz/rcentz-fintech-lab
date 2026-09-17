# V5.29.1 — Investment Audit + Reconciliation Foundation

## Status

**IN PROGRESS — V5.29 foundation**

This milestone begins V5.29 Investment Realism & Audit Hardening.

## Added

- [x] Append-only private investment audit log table
- [x] Audit model with actor, target customer, instrument, action, reference, reason and metadata
- [x] Subscription/redemption transactions automatically emit audit records
- [x] Valuation events automatically emit audit records
- [x] `investment:reconcile` integrity command
- [x] Holding current-value reconciliation
- [x] Holding unrealized P/L reconciliation
- [x] Unit supply / available-unit reconciliation
- [x] Transaction gross / fee / net reconciliation
- [x] Investment transaction ↔ wallet transaction reference reconciliation
- [x] Subscription and redemption direction checks
- [x] Units × authoritative price checks

## Boundary

Audit history is append-only operational history. Business metadata remains editable through Admin. Corrections to historical financial actions should be represented by explicit corrective/reversal operations rather than silently rewriting history.

## Next

V5.29.2 will build realistic lifecycle activity and distribution/deduction behavior on top of this audit/reconciliation foundation.
