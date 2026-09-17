# V5.29.A1.1 — Account Identity, Access & Withdrawal Verification

## Status

**IMPLEMENTED — browser acceptance required**

## Corrections from failed A1 preflight

- Previous A1 installer failed safely before source writes because an Admin User controller anchor appeared twice.
- A1.1 uses narrower deterministic anchors.
- Optional profile fields remain optional.
- Age eligibility uses required 18+ self-attestation; optional DOB is validated as 18+ when provided.
- Withdrawal token is NOT sent by platform notification.
- Admin sees the generated token once and personally sends it to the customer.
- Withdrawal token lifetime is 24 hours.

## Scope

- Multi-step registration redesign.
- Optional date of birth, country, employment class and education level.
- Required 18+ confirmation.
- Customer display-currency selector.
- Admin create/edit support for optional identity fields.
- Account states: active, blocked, suspended, banned.
- Restriction reason and suspension expiry.
- Customer access enforcement.
- Mandatory withdrawal purpose.
- Admin purpose review.
- Manually delivered admin-generated 6-digit token.
- Hashed token storage; only last four retained for reference.
- Customer token verification.
- Final withdrawal approval locked until verification succeeds.
