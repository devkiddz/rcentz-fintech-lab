# V5.29.2.2 — Demo Investor Holdings Seeder

## Status

**IMPLEMENTED — test data utility**

## Purpose

Populate existing non-admin customer accounts with realistic Private Investment holdings so the V5.29 lifecycle, audit, portfolio and investor-tracker surfaces can be exercised with meaningful data.

## Behavior

- Uses existing customer accounts that already have wallets.
- Uses active, visible Private Investment instruments only.
- Spreads customers across different instruments rather than cloning one identical portfolio.
- Uses the canonical `PrivateInvestmentOrderEngine` for every subscription.
- Creates explicit demo wallet funding before subscriptions so existing customer wallet balances are not consumed by the lab seed.
- Marks subscription metadata with source `demo_seed`.
- Existing positive holdings for a customer/instrument are skipped, making the command safe to rerun.
- Admin/system audit history remains truthful because the subscriptions pass through the real order engine.

## Command

`php artisan investment:seed-demo-holdings`

Optional customer cap:

`php artisan investment:seed-demo-holdings --customers=5`

## Verification

After seeding, run:

`php artisan investment:reconcile`

The Admin Investment registry should then show active investor counts on the seeded instruments.
