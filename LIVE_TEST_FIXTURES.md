# Tesla Drives Live-Test Fixtures

This package contains a synthetic QA dataset for validating the cleaned Laravel application on cPanel. None of the identities, document numbers, wallet activity, holdings, or transaction references represent real customers.

## Temporary login credentials

### Administrator
- URL: `/adminlogin/login`
- Email: `admin@tesladrives.test`
- Password: `TeslaAdmin@2026!`

If `BOOTSTRAP_ADMIN_EMAIL` and `BOOTSTRAP_ADMIN_PASSWORD` are already set in `.env`, the live-test seeder uses those administrator credentials instead.

### Test customers
All three customer accounts use the temporary password `TestUser@2026!`.

| User | Email | QA state |
| --- | --- | --- |
| Amara Okafor | `amara.okafor@tesladrives.test` | Email verified, KYC approved, funded wallet, stocks, funds, completed vehicle purchase, processing withdrawal |
| Daniel Brooks | `daniel.brooks@tesladrives.test` | Email verified, KYC pending, funded wallet, mixed portfolio, vehicle order processing |
| Sofia Martinez | `sofia.martinez@tesladrives.test` | Email verified, KYC rejected/resubmission scenario, watchlists, completed withdrawal, pending synthetic crypto deposit, cancelled vehicle order |

## Seeded catalogue/reference data

The normal database seeder now safely upserts:

- Site settings
- Traditional and cryptocurrency payment methods
- Tesla vehicle inventory
- Email templates
- Investment categories
- Practical investment plans covering mutual fund, ETF, retirement, Tesla-focused, ESG, and index-fund types
- Stock catalogue
- Currency-rate fixtures
- Live-test user activity and portfolios

The live-test fixtures additionally populate wallets, wallet transactions, KYC states, stock holdings/transactions/watchlists, stock quotes, five-day chart history, investment holdings/transactions/watchlists, recurring investment plans, a disabled automatic NAV rule, purchases, and dashboard notifications.

Stock prices, NAVs, quotes, chart points and currency rates are QA fixtures/snapshots. They are not live market data or financial advice. External stock API refresh is disabled during seeding unless `SEED_FETCH_LIVE_STOCK_DATA=true` is explicitly configured.

## Existing cPanel database

If your current database already contains the schema, do **not** run migrations merely to load this test data. From the Laravel project root run:

```bash
php artisan optimize:clear
php artisan db:seed --force
php artisan optimize:clear
```

The seeders are designed to be rerunnable and update their known fixture records rather than multiply them.

## Security after testing

Before exposing this installation as anything other than a controlled QA/reference site:

1. Change the temporary administrator password from the GUI or `.env` bootstrap configuration.
2. Delete or disable the three `.test` customer accounts if no longer needed.
3. Remove test wallet balances, KYC fixtures and transactions.
4. Configure real mail/payment/market-data credentials only in `.env`.
5. Never treat the seeded stock/NAV values as live financial data.
