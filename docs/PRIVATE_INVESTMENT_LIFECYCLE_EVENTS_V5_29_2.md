# V5.29.2 — Investment Lifecycle Distributions + Deductions

## Status

**IMPLEMENTED — browser acceptance required**

## Purpose

V5.29.2 adds instrument-level cash lifecycle events managed by Admin.

A lifecycle event applies one approved rule to every active holding in the selected instrument. The system calculates each customer's amount from that customer's actual holding, records wallet movement, writes an InvestmentTransaction, and preserves the operation in the V5.29 audit trail.

## Implemented

- [x] Private investment lifecycle event table/model
- [x] Admin-governed distribution events
- [x] Admin-governed deduction events
- [x] Fixed amount per unit calculation mode
- [x] Percentage of current holding value calculation mode
- [x] Atomic all-or-nothing processing
- [x] Wallet credit for distributions
- [x] Wallet debit for deductions
- [x] Insufficient-wallet protection for deductions
- [x] Per-customer InvestmentTransaction records
- [x] Per-customer WalletTransaction records
- [x] Shared references between investment and wallet transactions
- [x] Aggregate lifecycle audit record
- [x] Per-customer transaction audit records
- [x] Admin form and recent lifecycle event history
- [x] Lifecycle wallet-direction reconciliation checks
- [x] Admin registry investor tracker showing active investor/holding presence
- [x] MySQL-safe explicit lifecycle index names (V5.29.2.1 hotfix)

## Economic boundary

Lifecycle cash events do not silently alter the authoritative unit price.

Valuation changes remain the responsibility of the Investment Valuation Engine. A maintenance cost may therefore be represented as either:

- a cash deduction when the business rule explicitly charges customer accounts, or
- a valuation event when the economic effect belongs in the instrument's value/price.

These are deliberately separate actions.

## Acceptance required

Browser-test Admin Investment Control by applying a small distribution to an instrument with active holdings, then verify customer wallet/transactions and run:

`php artisan investment:reconcile`

Do not mark this milestone fully complete until that browser acceptance passes.

## Runtime acceptance — completed

V5.29.2 has now passed runtime/browser acceptance for the implemented lifecycle path.

Verified behavior:
- seeded customers hold Private Investment instruments
- Admin can execute a percentage-based distribution
- lifecycle event persists to the database
- wallet/investment reconciliation remains valid
- lifecycle history is scoped to the correct investment instrument
- Manage preview is limited to the latest 5 events
- full lifecycle history is available on a dedicated paginated page
