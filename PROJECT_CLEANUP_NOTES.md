# Laravel Reference Cleanup Report

## Objective

Stabilize the borrowed Laravel/PHP application as a trustworthy reference implementation before using its product behavior to design a separate Next.js/Rcentz version.

This cleanup intentionally preserves the application's business scope while removing avoidable routing drift, theme duplication, unsafe defaults, stale vendor branding, and hosting-specific PHP configuration.

## Major fixes completed

### Administrator authentication

- Added a dedicated administrator login surface at `/adminlogin/login`.
- Added separate GET and POST route names: `adminlogin.login` and `adminlogin.authenticate`.
- Added `AdminAuthenticatedSessionController`.
- Non-admin users are rejected from the administrator login flow even if their customer credentials are valid.
- Unauthenticated access to `/admin` and `/admin/*` now redirects to `/adminlogin/login` instead of the customer login page.
- Authenticated admins are redirected to `/admin`; authenticated customers are redirected to `/dashboard`.

### Broken route/view contracts

The original application contained controller actions that referenced Blade files which did not exist. The cleanup repaired these contracts by either creating the missing canonical UI or redirecting obsolete aliases to an existing canonical screen.

Fixed areas include:

- investment search
- featured investments
- investment type/category filters
- investment categories
- investment watchlist
- KYC status
- stock sector filters
- stock industry filters
- dashboard transaction links

Static audit result after cleanup:

```text
Named route references: no unresolved application route names
Controller view references: 0 missing views
Routed controller actions: 0 missing classes/methods
Duplicate method + URI routes: 0
```

`$request->route('token')` on the password-reset screen is a request-route parameter lookup, not a named-route reference.

### Theme system

The original theme had multiple competing sources of truth:

- Tailwind's `blue-*` palette was secretly redefined as Tesla red.
- the customer layout repeated another large set of inline color overrides.
- several dark-mode cards used brand-red surfaces instead of neutral dark surfaces.
- authentication pages hardcoded white backgrounds and inline colors.
- admin pages were largely hardcoded light-only.

The cleanup now uses:

- an explicit `tesla-*` brand palette
- normal Tailwind color meaning (`blue` means blue again)
- semantic CSS variables for background/card/border/text/muted/ring
- shared `ui-panel`, `ui-input`, `ui-label`, and button primitives
- one theme initialization partial
- one JavaScript theme toggle implementation
- consistent light/dark behavior across guest, customer, and administrator shells
- theme-safe native form controls
- neutral dark cards instead of accidental red backgrounds

The mobile dashboard navigation was also normalized to one brand treatment instead of unrelated green/purple/orange/red active states.

### Authentication form cleanup

Customer sign-in, registration, password reset, confirmation, email verification, and administrator sign-in were normalized around the same semantic form system.

The forms no longer depend on inline JavaScript color mutations or hardcoded white card backgrounds.

### Administrator UI cleanup

- admin shell now respects light/dark theme tokens
- admin navigation uses the same brand active state
- admin page content uses semantic background/card/text colors
- theme toggle added to admin header
- old `ThemeVortex`, `Hyipcoders`, `codesremedy.net`, and “Remedy script” presentation remnants removed from the About surface
- About page now describes the actual platform instead of advertising an unrelated developer/vendor

### Deployment/security cleanup

- removed cPanel-specific `ea-php82` handler from source `.htaccess`
- cPanel MultiPHP Manager now owns PHP version selection
- application requirement remains PHP 8.2+
- removed the unprotected `/cron/run-all-legacy` endpoint
- `/cron-setup` is now administrator-only
- cron endpoints fail closed when `CRON_TOKEN` is absent
- cron token comparison uses `hash_equals`
- removed direct `env()` reads from application views/runtime code; runtime reads configuration instead
- removed public default seeded admin/test passwords
- bootstrap admin credentials are now environment-driven
- demo user seeding is opt-in
- Settings seeding is included in the main seeder and made repeatable with `updateOrInsert`
- `.env.example` rewritten for MySQL/cPanel and the integrations actually present in the project
- added `scripts/hosting-check.php`

### Frontend build

The production Vite/Tailwind build succeeds after cleanup.

The original ZIP had non-executable permissions on bundled Node binaries; this was a packaging/file-permission artifact, not an application-code problem.

## Verification completed

```text
PHP project files linted:       207
PHP syntax failures:            0
Blade templates compiled/linted:158
Blade failures:                 0
Duplicate route method+URI:     0
Missing routed methods/classes: 0
Missing controller views:       0
Production Vite build:          PASS
Laravel route discovery:        PASS
```

## Verification not possible in this workspace

Full database/browser integration tests were not run because the inspection runtime is missing several PHP extensions/drivers, including DOM/XML and PDO MySQL. The included hosting check detects these prerequisites before cPanel testing.

This is an environment limitation, not a silent claim that every third-party integration has been live-tested.

## External configuration still required

The following cannot be “fixed” generically because they require real service credentials or business decisions:

- MySQL production database credentials
- SMTP credentials
- Google OAuth credentials
- Finnhub/market-data API key and quota
- Pusher/broadcasting credentials if enabled
- production CRON token
- production site identity/content/settings
- real payment/crypto destination configuration

## Important security note

The borrowed archive contains an `.env` file. Treat it as private. Do not publish the original ZIP or original `.env` to a public GitHub repository.

The cleaned deliverables exclude `.env`.

## Reference-vs-rebuild rule

This Laravel application should now be used to answer:

> What product behavior exists, what data does it need, and what workflows must the Next.js product reproduce or improve?

It should **not** be copied line-for-line into Next.js. The Next.js implementation should rebuild the domain model, permissions, financial transaction rules, forms, async jobs, and UI system intentionally.

## Live-test fixture pass

- Added one temporary admin and three synthetic customer scenarios.
- Added wallets, KYC states, transactions, holdings, watchlists, purchases and notifications for meaningful UI testing.
- Expanded investment plans so all supported plan types have practical metadata, NAV deltas, assets, fees, yield and NAV-history fixtures.
- Made investment category, investment plan and email-template seeders rerunnable.
- Added currency-rate seeding to the main database seeder.
- Removed the interactive stock-seeder prompt; live API refresh is now explicit opt-in.
- Fixed Stock mass assignment for open/high/low fields.
- Aligned the fresh Laravel investment-plan migration with the SQL/model contract by adding `nav_history` and `last_nav_update`.
