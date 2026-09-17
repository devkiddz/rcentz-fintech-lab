# V5.29.2.5 — Lifecycle Acceptance + Platform Checkpoint Cleanup

## Status

**READY FOR GIT CHECKPOINT**

## Investment lifecycle acceptance

Browser/runtime acceptance completed for V5.29.2:

- Demo investor holdings exist.
- Admin investment registry reports investor counts.
- Lifecycle distribution executed successfully.
- Verified stored lifecycle event:
  - type: distribution
  - calculation mode: percent of current value
  - value: 0.5%
- Per-instrument lifecycle history is correctly scoped.
- Manage page shows the latest 5 lifecycle events.
- Dedicated lifecycle history page provides paginated full history.
- Private Investment reconciliation passes after lifecycle execution.

## Internal wallet boundary

`wallet_transactions.payment_method_id` is nullable so internal platform ledger movements do not require a fake external payment method.

## Withdrawal architecture cleanup

The obsolete A1-era Admin wallet-transaction withdrawal-token endpoint is absent from the canonical route set.

The canonical A2 withdrawal flow remains:

Customer amount + optional note
→ WithdrawalTokenRequest
→ Admin generates token
→ private Account Alert
→ customer verifies token + destination
→ pending WalletTransaction is created and funds are reserved
→ Admin approves/rejects the actual withdrawal transaction.

Without a verified token there is no withdrawal transaction to approve.

## Account/security acceptance state

A1.1 / A2 / A2.2 remain implemented in this checkpoint.

Technical integration is present. Complete browser acceptance of every account/security branch is still pending and should not be represented as fully accepted yet.

## Next milestone

V5.29.3 — Portfolio Intelligence

The portfolio layer should aggregate, but not blur, separate economic engines:

- Private Investments
- Manual Trading
- Bot Trading
- Copy Trading

Each engine should expose understandable accumulation:

- capital / cost basis
- current value / equity
- realized profit or loss
- unrealized profit or loss
- distributions / credits
- deductions / fees
- net performance
- timestamped activity explaining each `+` and `-`
