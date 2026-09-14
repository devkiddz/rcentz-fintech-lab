# cPanel Deployment Guide — Cleaned Laravel Reference

This application is a Laravel 12 project. Its `composer.json` requires **PHP 8.2 or newer**. For the first hosting test, use **PHP 8.2** because that is the version the original package was developed around. PHP 8.3 can be tested after the 8.2 baseline is stable.

## 1. Recommended subdomain arrangement

Use a dedicated subdomain for the test deployment, for example:

```text
reference.example.com
```

The preferred filesystem arrangement is:

```text
/home/CPANEL_USER/apps/tesla-reference/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/              <-- subdomain document root
├── resources/
├── routes/
├── storage/
├── vendor/
├── artisan
├── composer.json
└── .env
```

In cPanel, point the subdomain's **Document Root directly to the application's `public/` folder**. This is safer than exposing the Laravel project root.

A fallback root `.htaccess` remains in the package for hosts that force the project root to be web-accessible, but it should not be the first choice.

## 2. PHP version

Open **cPanel → MultiPHP Manager**, select only the test subdomain, and choose:

```text
PHP 8.2
```

The cleaned project no longer hardcodes `ea-php82` in `.htaccess`; cPanel owns the PHP-version selection. This also means you can later switch the subdomain independently without changing application source code.

Do **not** select PHP 8.1, 8.0, 7.4, or older. The application declares `php: ^8.2`.

## 3. Required PHP extensions

Before testing, enable or ask the host to enable at least:

```text
ctype
curl
dom
fileinfo
filter
hash
mbstring
openssl
pcre
PDO
pdo_mysql
session
tokenizer
xml
```

Recommended as well:

```text
bcmath
intl
zip
```

The included diagnostic can be run from the application root:

```bash
php scripts/hosting-check.php
```

A missing `dom/xml` extension can make Laravel console commands fail with `Class "DOMDocument" not found`. That is a PHP-extension/server issue, not an application-route issue.

## 4. Upload the deployment package

Use the cPanel-ready ZIP supplied with this cleanup. It includes `vendor/` and the compiled `public/build/` assets so the host does not need Node.js just to serve the application.

Do not upload the original borrowed `.env` file. The cleaned package deliberately excludes it.

After extraction, copy:

```text
.env.example -> .env
```

Then edit `.env` for the hosting account.

## 5. Essential `.env` values

At minimum configure:

```dotenv
APP_NAME="Tesla Investment Platform"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://reference.example.com
APP_KEY=

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cpanel_database
DB_USERNAME=cpanel_database_user
DB_PASSWORD=strong_database_password

BOOTSTRAP_ADMIN_NAME="Platform Administrator"
BOOTSTRAP_ADMIN_EMAIL=your-admin@example.com
BOOTSTRAP_ADMIN_PASSWORD=use-a-strong-unique-password

CRON_TOKEN=generate-a-long-random-secret
```

Never commit `.env` to GitHub or place it in a public download.

## 6. Create MySQL database

In **cPanel → MySQL Databases**:

1. Create a database.
2. Create a database user.
3. Add the user to the database.
4. Grant the required privileges.
5. Put the resulting cPanel-prefixed database/database-user names into `.env`.

## 7. First-run Laravel commands

If cPanel Terminal/SSH is available, from the application root run:

```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

The cleaned seeder does **not** ship a public default admin password. It uses `BOOTSTRAP_ADMIN_EMAIL` and `BOOTSTRAP_ADMIN_PASSWORD` from `.env`.

The admin login is now intentionally:

```text
/adminlogin/login
```

After successful administrator authentication, the application redirects to:

```text
/admin
```

A non-admin account is rejected from the administrator login surface.

## 8. Writable directories

Laravel must be able to write to:

```text
storage/
bootstrap/cache/
```

Typical shared-hosting permissions are `775`, although the exact correct value depends on the host's PHP handler and ownership model. Avoid `777` unless the hosting provider explicitly requires it.

## 9. Production assets

A production Vite build is already included in the cPanel package. To rebuild after changing Tailwind/JavaScript locally:

```bash
npm install
npm run build
```

Then upload the refreshed `public/build/` directory.

Node.js does not need to run continuously in production for this Laravel application.

## 10. External integrations

The application contains integrations that will not work until their own credentials are supplied:

- Google OAuth
- SMTP/mail
- Finnhub / market-data services
- optional Yahoo market data configuration
- Pusher/broadcasting when enabled

Keep `BROADCAST_CONNECTION=log` until Pusher is configured correctly.

Invalid market-data keys should not be mistaken for PHP or routing failures.

## 11. Cron jobs

The `/cron/*` endpoints now fail closed unless `CRON_TOKEN` exists. The old unprotected legacy cron endpoint was removed.

The cron documentation page is administrator-only:

```text
/cron-setup
```

A typical cPanel cron command is:

```bash
wget -q -O /dev/null "https://reference.example.com/cron/run-all?token=YOUR_CRON_TOKEN"
```

Prefer a header-based token when the scheduler/tool supports custom HTTP headers.

If `QUEUE_CONNECTION=database`, scheduled endpoints dispatch jobs into the queue. A queue worker or an additional scheduled queue-processing strategy must therefore exist for asynchronous jobs to execute.

## 12. Quick smoke-test order

After deployment, test in this order:

```text
1. Homepage loads
2. /login loads
3. /adminlogin/login loads
4. Admin credentials authenticate
5. /admin loads
6. Customer registration/login works
7. Dashboard loads after login
8. Wallet pages
9. Investment pages
10. Stock pages
11. Portfolio/watchlists
12. KYC
13. Vehicle browsing/purchase flow
14. Admin CRUD screens
15. Mail/OAuth/market APIs
16. Cron/queue operations
```

This order separates core Laravel/DB problems from optional third-party integration problems.

## 13. If a 500 error appears

Temporarily inspect:

```text
storage/logs/laravel.log
```

On production, keep:

```dotenv
APP_DEBUG=false
```

Do not expose stack traces publicly. If necessary, reproduce the error briefly on the private test subdomain and inspect the log instead.

## 14. Why the old PHP-version conflict was confusing

The original source contained a cPanel-generated `.htaccess` handler that forced `ea-php82`. That tied application source code to one host's PHP package name. The cleaned project removes that pin.

The new rule is simpler:

```text
Application requirement: PHP >= 8.2
Hosting selection:      cPanel MultiPHP Manager
Per-subdomain version:  controlled by cPanel, not Git/source code
```

That is the correct separation of concerns for this deployment.
