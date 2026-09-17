# V5.29.2.3 — Internal Wallet Transaction Payment Boundary

## Status

**IMPLEMENTED — runtime acceptance required**

## Problem found

The demo Private Investment seeder failed before creating holdings because `wallet_transactions.payment_method_id` was database-required even for internal ledger movements.

Observed failure:

`SQLSTATE[HY000]: General error: 1364 Field 'payment_method_id' doesn't have a default value`

## Architectural correction

`payment_method_id` identifies an external deposit / withdrawal rail. It is not a valid requirement for every wallet ledger movement.

Internal operations such as:

- Private Investment subscriptions
- Private Investment redemptions
- Investment distributions / deductions
- Demo funding and controlled internal ledger adjustments

may legitimately have no external payment method.

V5.29.2.3 therefore makes `wallet_transactions.payment_method_id` nullable while retaining the existing relationship for transactions that actually use a payment method.

## Why this is preferable to fake payment methods

Assigning an arbitrary payment method to an internal investment transaction would make the financial ledger misleading. The schema now represents the domain truth instead:

- external rail used → `payment_method_id` populated
- internal platform movement → `payment_method_id = NULL`

## Acceptance

After migration:

1. Run `investment:seed-demo-holdings --customers=5`.
2. Run `investment:reconcile`.
3. Confirm Admin → Investments reports active investors.
4. Continue V5.29.2 lifecycle distribution acceptance.
