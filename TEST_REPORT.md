# Cleaned Release Test Report

## Passed

- Laravel route registry boots: **212 routes**.
- Duplicate method + URI routes: **0**.
- Missing routed controller classes/methods: **0**.
- Static named route references checked: **497**; missing: **0**.
- Static controller/route view targets checked: **106**; missing: **0**.
- PHP lint: **208 PHP files**, **0 syntax failures**.
- Production frontend build: **PASS** with Vite 6.3.5.
- `/up`: **HTTP 200**.
- `/login`: **HTTP 200**.
- `/adminlogin/login`: **HTTP 200**.
- `/register`: **HTTP 200**.
- `/forgot-password`: **HTTP 200**.
- Signed-out `/admin`: **HTTP 302 -> /adminlogin/login**.
- Signed-out `/admin/users`: **HTTP 302 -> /adminlogin/login**.
- Signed-out `/dashboard`: **HTTP 302 -> /login**.

## Host/runtime limitation of this test environment

This inspection runtime has PHP 8.4 but is missing PHP extensions including `pdo_mysql`, `dom/xml`, `mbstring` and `curl`. Therefore full MySQL-backed feature tests, PHPUnit/Pest execution, database migrations and authenticated CRUD workflows cannot be executed here.

Those are deployment prerequisites, not known application failures. The package includes `scripts/hosting-check.php` so the actual cPanel environment can be verified before the live test.

## Deployment resilience added

The public/auth layouts now fall back to application defaults when the database/settings table is temporarily unavailable during first setup. That lets the login surfaces render while database configuration is being completed instead of failing immediately through the settings helper.

## Live-test data fixture verification — 2026-09-14

Additional QA pass after adding the synthetic live-test dataset:

- PHP lint: 197 application/config/database/route PHP files, 0 syntax failures.
- Laravel route registry: 212 routes discovered successfully.
- Frontend production build: PASS with Vite 6.3.5; `public/build/manifest.json` regenerated.
- Uploaded MySQL schema contract: 35 tables parsed; fixture writes checked across 17 tables; 0 missing referenced columns.
- Fixture catalogue dependencies: all referenced stock symbols, investment plans, Tesla vehicles and payment methods exist in their seeders.
- No `.env` file is included in the release.
- `node_modules` is excluded from the cPanel release; compiled frontend assets are included.

A true database execution of `db:seed` cannot be performed in this container because its PHP CLI exposes PDO without a database driver. The fixture SQL/table contract was therefore checked statically against the supplied MySQL schema. The cPanel environment remains the authoritative integration test for MySQL writes.
