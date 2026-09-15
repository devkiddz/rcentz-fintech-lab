# Trading Intelligence V2.2 Recovery

Fixes:
- AI bot create/edit Blade error: removes the fragile shared `$item` variable pattern.
- Adds a single preview-seeding command that prints row counts after seeding.
- Adds icons to the new Copy Trading and AI Trading Bots admin submenu items.
- Rebuilds the admin dashboard into a current operations workspace using the existing dashboard data contract.
- Keeps the V2.1 legacy-route recovery.

Apply over the project root.

Run exactly:

    /c/xampp/php/php.exe artisan optimize:clear
    /c/xampp/php/php.exe artisan trading-intelligence:seed-preview
    /c/xampp/php/php.exe artisan optimize:clear
    npm run build

The preview command MUST print non-zero rows for Published strategies and Bot products.
If either is zero, stop and send the terminal output.

No migration is required for this recovery patch.
