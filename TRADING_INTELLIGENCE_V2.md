# Rcentz Trading Intelligence V2

This upgrade turns Copy Trading and AI Trading Bots into separate product domains with governance and commercial controls.

## Customer navigation

Trading
- Live Markets
- Portfolio
- Transactions
- Watchlist

Copy Trading
- Strategy Marketplace
- My Copied Strategies
- Become a Provider
- Provider Dashboard (approved providers only)

AI Trading Bots
- Bot Marketplace
- My Bots
- Subscriptions
- Performance

## Copy Trading governance

Users may apply to become strategy providers.
Applications are Pending until admin approval.
Only approved providers receive a provider profile and may publish strategies.
Admin can review applications, providers and strategies and may pause a strategy.
Customers may copy approved active strategies with their own allocation, max-trade and copy-ratio limits.

## AI bot governance

Customers can no longer create arbitrary bots.
Admin creates the Bot Catalog.
Each bot product defines:
- stock
- strategy/action
- risk
- price
- billing period
- minimum balance
- max allocation
- default interval / daily trade limit / trade amount / trigger
- which settings a customer is allowed to change

A customer buys/subscribes to a bot product.
The system creates a customer runtime bot in Paused mode.
The customer may configure only permitted fields, then activate/pause/run the bot.
Paid bot access is debited from Available Balance and written into the wallet ledger + financial history.

## Admin navigation

Copy Trading
- Provider Applications
- Providers
- Strategies

AI Trading Bots
- Bot Catalog
- Create Bot
- Subscriptions
- Executions

## Install

Extract over the project root, then run:

    /c/xampp/php/php.exe artisan migrate
    /c/xampp/php/php.exe artisan optimize:clear
    npm run build

Do NOT use migrate:fresh.

This package includes both the original Trading Intelligence migration and the V2 upgrade migration. If V1 was already migrated, Laravel will only run the V2 migration.

## Test checkpoint

1. Admin:
   - Open Copy Trading > Provider Applications.
   - Open AI Trading Bots > Create Bot.
   - Create an AAPL DCA bot, $25 access price, one-time billing, medium risk, $500 max allocation.

2. Amara:
   - Copy Trading > Become a Provider.
   - Submit an application.
   - Provider Dashboard must NOT be available yet.

3. Admin:
   - Approve Amara.
   - Amara must now see Provider Dashboard.
   - Publish one strategy.

4. Daniel:
   - Copy Trading > Strategy Marketplace.
   - Copy Amara's strategy.
   - Confirm it appears under My Copied Strategies.

5. Daniel:
   - AI Trading Bots > Bot Marketplace.
   - Buy/subscribe to the admin-created bot.
   - Wallet Available Balance should fall by the bot price.
   - Ledger/history should show the bot subscription debit.
   - My Bots should show the bot in Paused mode.
   - Configure and activate it.
   - Run now once.
   - Performance should show the execution.

Stop at the first failed checkpoint and send the exact screen/error.
