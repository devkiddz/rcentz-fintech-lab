# V5.29.A2.5 — Money & Withdrawal Foundation

## Status

Implemented locally. Browser acceptance required.

## Scope

This milestone repairs and professionalizes the withdrawal foundation before VIP work resumes.

### Customer
- Adds canonical `/money/*` routes while retaining `/wallet/*` compatibility endpoints.
- Renames the customer navigation domain from Wallet & Money to Money.
- Rebuilds the withdrawal request and verification screens.
- Adds withdrawal request history and clear lifecycle status.
- Automatically expires stale verification codes so they no longer block new requests.
- Makes the customer-facing withdrawal process copy editable through Admin → Settings → Security.

### Admin
- Renames Token Requests to Withdrawal Requests.
- Adds customer/status filtering.
- Shows the linked payout transaction when one exists.
- Adds direct Withdrawal Requests access from customer cards.
- Modernizes the Transactions workspace.
- Removes the withdrawal Delete action from the transaction UI; withdrawal outcomes must use approve/reject.

### Next
V5.29.A2.6 modernizes the remaining Money pages (overview, add money, send money, activity, connections and crypto deposit confirmation) into the same workspace language.
