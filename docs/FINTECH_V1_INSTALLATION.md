# Fintech V1 Release — 2026-10-05

## Fresh installation

Use PHP 8.2+ and MySQL/MariaDB with an EMPTY database. Extract the full package into the application directory and point the web server document root at `public/`. Bundled Composer dependencies and the captured Vite build are included; no frontend rebuild is needed for this release.

Give PHP write access to `storage/` and `bootstrap/cache/`. Create the usual storage framework cache/data, sessions, views and logs directories if absent. Visit `/install` over HTTPS and fill in database, administrator, brand and region fields. Never use `/install` on an existing customer database. The bundled sanitized baseline is imported and subsequent migrations are applied by the existing installer.

The installer now accepts optional Alpha Vantage, Twelve Data and CoinMarketCap keys. Register at the linked provider websites, create your own credentials and paste them locally. Keys cannot be automatically issued by this app. Blank keys allow configured markets; external feeds require credentials and their refresh worker. Provider quotas remain shared wherever the same key is reused. Saving a Twelve Data key does not start its separate trial feed worker.

After installation, inspect Admin Market settings and select configured Market/Neutral for the demonstration engine. Inspect registered instruments and feeds, then configure ONE scheduler as described in UPDATE-INSTRUCTIONS.md. Do not silently change the global marketplace or reuse local customer data. USD observations need three fresh currency series spanning at least 30 minutes before strength is available. Existing baseline instruments use their stored configured prices until eligible reference observations exist.

After baseline import and migrations, the installer automatically provisions UKPROP24 under Real Estate and GOLD24 under Commodities, with their backing bases and basket bindings. Each has 10,000 shares initially priced at USD 10, a 24-hour redemption lock, 1% subscription fee and 0.5% redemption fee. These are demonstration reserve records, not real asset purchases. Setup uses positive stored basket prices; fresh scheduler movement is required before investing. No customer subscriptions or wallet funding are created by this listing setup. No temporary browser setup script is included or required for fresh installations.

Online crypto trade and investment subscription/redemption tests remain pending. The full fresh MySQL install has not been executed in the assembly environment.
