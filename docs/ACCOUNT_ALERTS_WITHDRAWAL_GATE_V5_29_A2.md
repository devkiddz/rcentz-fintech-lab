# V5.29.A2 — Direct Account Alerts + Withdrawal Token Gate

## Status

**IMPLEMENTED — browser acceptance required**

## Direct dashboard communication

A new Account Alert system is deliberately separate from support/chat and from generic automatic notifications.

- Admin → specific customer only
- Private dashboard alert window
- Normal / Important / Urgent priority
- Optional action button
- Optional expiry
- Read/dismiss state
- Admin history
- Withdrawal tokens use this same private alert channel

## Withdrawal lifecycle

1. Customer enters intended amount and optional note.
2. System creates a WithdrawalTokenRequest only.
3. No WalletTransaction exists yet.
4. No funds are reserved yet.
5. Admin generates a 6-digit token.
6. Token is pushed to the customer's Account Alert window.
7. Token expires in 30 minutes.
8. Admin may destroy the token/request before use.
9. Customer opens the second-stage form.
10. Customer enters token + payout method + destination.
11. Token validation succeeds.
12. Only then is the actual pending WalletTransaction created.
13. Funds are reserved at that moment.
14. Token is marked used.
15. Admin approves/rejects the real withdrawal normally.

## Safety

- Token hashes are stored instead of plaintext.
- Token is single-use.
- Regeneration invalidates the previous token and alert.
- Destroy Token revokes the request and hides its alert.
- Existing Admin withdrawal approval remains protected by the presence of a completed token gate.

## Admin email verification

Admin can manually mark customer email addresses verified from the Users table for test-mode registration workflows.

## Checkpoint note

This implementation is included in the V5.29.2.5 Git checkpoint.

Technical integration is present. Complete browser acceptance across every account-status and withdrawal branch remains pending and should be completed separately from the Private Investment lifecycle acceptance.
