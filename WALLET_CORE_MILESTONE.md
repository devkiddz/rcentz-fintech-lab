# Wallet Core Milestone

This milestone upgrades the PHP reference application with a working synthetic internal-transfer engine and a more app-like wallet experience.

## Added
- `/wallet/transfer` form and transfer history
- Atomic sender debit + recipient credit using locked wallet rows
- Persistent `internal_transfers` records
- Mirrored wallet transaction records for both users
- Recipient/sender notifications
- Internal Transfer payment method seed
- Expanded wallet transaction status enum to support existing admin rejection flow
- Modern wallet overview and shared application UI primitives

## Important
All balances and transfers are synthetic. No live money, banking rail, blockchain transaction, or payment provider is used.
