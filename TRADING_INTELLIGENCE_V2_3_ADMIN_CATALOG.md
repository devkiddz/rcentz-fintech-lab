# Trading Intelligence V2.3 — Admin Catalog

This correction addresses three things directly:

1. Admin icons
   - The previous submenu used `data-lucide` markup but the admin shell did not load Lucide.
   - Lucide is now loaded and initialized in the admin layout.

2. Admin navbar/header
   - Rebuilt admin topbar with page title, site preview, settings, theme control, admin profile and logout.

3. Existing editable product catalog
   - Seeds 5 admin-owned AI trading bots.
   - Seeds 2 approved provider profiles with 4 copy strategies.
   - Adds admin editing for Copy Trading strategies.
   - Bot products are already editable from the admin catalog.
   - Adds a migration that seeds the catalog automatically on this existing installation.
   - Adds the catalog seeder to DatabaseSeeder for future clean installs.

## Apply

Extract over the Laravel project root.

Run exactly:

    /c/xampp/php/php.exe artisan migrate
    /c/xampp/php/php.exe artisan optimize:clear
    npm run build

Then refresh the browser with Ctrl + F5.

## Expected admin data

AI Trading Bots:
- AAPL Smart DCA
- MSFT Core Accumulator
- NVDA Dip Entry
- TSLA Volatility Scout
- GOOGL Breakout Watch

Copy Trading:
- Balanced Growth
- Capital Guard
- Momentum Select
- Core Tech Rotation

Admin can edit each bot from AI Trading Bots > Bot Catalog.
Admin can edit each strategy from Copy Trading > Strategies.
