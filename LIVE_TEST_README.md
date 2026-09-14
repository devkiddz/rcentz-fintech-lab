# Tesla Laravel — cPanel Live Test

This release is a cleaned Laravel 12 application prepared for a subdomain live test.

## Hosting baseline

- PHP: 8.2 or newer (8.2 is the safest first test target)
- Web server: Apache/cPanel with mod_rewrite
- Database: MySQL/MariaDB
- Required PHP extensions: ctype, curl, dom/xml, fileinfo, filter, hash, mbstring, openssl, pcre, PDO, pdo_mysql, session, tokenizer
- Recommended: bcmath, intl, zip, OPcache

## Preferred subdomain setup

1. Extract the application outside the public web root if your cPanel allows it.
2. Point the subdomain document root to this application's `public/` directory.
3. If you cannot change the document root, you may extract the application directly into the subdomain document root. The included root `.htaccess` forwards requests into `public/`, but a true `public/` document root is preferred.
4. In cPanel MultiPHP Manager, assign PHP 8.2+ to this subdomain only.
5. Enable the required PHP extensions in Select PHP Version / PHP Extensions.

## Environment

Copy `.env.example` to `.env`, then edit at least:

```env
APP_NAME="Tesla Investment Platform"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-subdomain.example.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

BOOTSTRAP_ADMIN_NAME="Platform Administrator"
BOOTSTRAP_ADMIN_EMAIL=your-admin@example.com
BOOTSTRAP_ADMIN_PASSWORD=use-a-strong-unique-password

CRON_TOKEN=use-a-long-random-token
```

Do not upload the borrowed/original `.env` file.

## First-run commands

From cPanel Terminal/SSH, run from the application root:

```bash
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link
php artisan optimize:clear
```

If `public/storage` already exists as a copied directory, log in as admin after the database is ready and use the storage repair action in Admin Settings; the cleaned controller preserves existing files and converts the path to Laravel's canonical storage link.

## Login routes

Customer login:

```text
/login
```

Administrator login:

```text
/adminlogin/login
```

Admin dashboard:

```text
/admin
```

Guests requesting `/admin` or any `/admin/*` route are redirected to `/adminlogin/login`.

## Smoke test order

1. Open `/up` — expect HTTP 200.
2. Open `/login` — expect customer login page.
3. Open `/adminlogin/login` — expect dedicated admin login page.
4. Open `/admin` while signed out — expect redirect to `/adminlogin/login`.
5. Sign in with the administrator created by the seeder.
6. Verify dashboard, users, settings, KYC, wallets, investments, stocks, purchases and email-management pages.
7. Create a disposable test user and verify registration/login/logout.
8. Test image upload after storage link is working.
9. Configure SMTP and market APIs only after the core application is stable.

## Database note

The original SQL dump is intentionally NOT bundled in this cleaned release because it contains legacy user/account data. Use the included migrations and seeders for a clean test database.

## Useful host diagnostic

```bash
php scripts/hosting-check.php
```

It reports missing PHP extensions, writable directories and the Vite production build status.

## QA fixture dataset

The current release includes a rerunnable live-test dataset. See `LIVE_TEST_FIXTURES.md` for the temporary administrator/customer credentials and seeded scenarios.

For an already-created schema, use `php artisan db:seed --force`; you do not need to rerun migrations just to load the fixtures.
