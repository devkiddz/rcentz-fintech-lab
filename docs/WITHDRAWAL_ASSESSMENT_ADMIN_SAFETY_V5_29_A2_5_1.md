# V5.29.A2.5.1 — Withdrawal Assessment & Admin Safety

## Status

Implemented locally. Browser acceptance required.

## Why

A2.5 exposed Withdrawal Requests and the verification flow, but the transaction detail screen still behaved like a generic wallet transaction. It did not expose the linked security assessment and still displayed a destructive withdrawal-delete control.

## Changes

- Admin Transactions navigation now calls the security queue **Withdrawal Requests**.
- Withdrawal transaction details load the linked `WithdrawalTokenRequest`.
- Withdrawal detail page now shows an **Assessment & verification** section:
  - request id/status
  - requested amount/time
  - code issuance time
  - verification time
  - issuing administrator
  - code last four where retained
  - customer note
  - linked payout transaction
- Direct navigation from a withdrawal transaction to that customer's Withdrawal Requests.
- Legacy/unlinked withdrawals are explicitly labelled rather than falsely claiming a token gate was completed.
- Withdrawal transaction deletion is blocked in the controller.
- The withdrawal detail page no longer exposes a Danger Zone delete action.

## Financial rule

A withdrawal is a protected financial record. Pending withdrawals resolve through Approve or Reject so reserved funds and the audit trail remain coherent.
