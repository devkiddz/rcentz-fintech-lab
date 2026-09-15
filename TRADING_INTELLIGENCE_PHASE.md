# Rcentz Trading Intelligence Phase

This phase adds two capabilities inside the existing **Trading** sidebar group:

- Copy Trading
- Trading Bots

## Copy Trading

A verified customer can create a strategy-provider profile. Other verified customers can follow that provider with:

- allocation limit
- per-trade maximum
- copy ratio percentage
- active / paused / stopped state

When a provider completes a normal stock buy or sell, copy execution starts **after the provider transaction commits**. A follower failure cannot roll back the provider's trade.

Every follower execution still uses the existing wallet truth:

- available balance is checked
- wallet row is locked
- stock holdings are updated atomically
- wallet transaction is written
- financial history is written
- notification is created
- success/failure/skipped execution is retained

## Trading Bots

Supported rules:

- DCA / interval
- price below trigger
- price above trigger

Controls:

- buy or sell action
- amount or quantity per trade
- interval
- max daily trades
- max total automated buy spend
- paused / active state
- manual Run now
- full execution history

New bots always start **paused**.

The scheduled command is:

```bash
/c/xampp/php/php.exe artisan trading-bots:run
```

It is also registered in the Laravel scheduler every five minutes.

## Install

Extract over:

```text
C:\xampp\htdocs\tesla.com
```

Run:

```bash
/c/xampp/php/php.exe artisan migrate
/c/xampp/php/php.exe artisan optimize:clear
npm run build
```

Do not run `migrate:fresh`.

## TEST CHECKPOINT

### 1. Navigation

Trading should now contain:

- Live Markets
- Portfolio
- Transactions
- Watchlist
- Copy Trading
- Trading Bots

### 2. Copy trading

Use Daniel first:
1. Impersonate Daniel.
2. Open Trading -> Copy Trading.
3. Create a public provider profile named `Balanced Growth`.
4. Stop impersonating.
5. Impersonate Amara.
6. Open Copy Trading and start copying Daniel with:
   - Allocation: $1,000
   - Max / trade: $200
   - Ratio: 100%
7. Stop impersonating and return to Daniel.
8. Buy **1 AAPL** normally.
9. Return to Amara.
10. Check Copy Trading -> Execution History, stock portfolio, wallet ledger and account History.

Expected: Amara receives a mirrored AAPL buy capped at $200, with its own stock transaction, wallet debit, financial-history entry and notification.

### 3. Copy sell

With Daniel, sell the AAPL position. Return to Amara and confirm the copied sell is recorded and cannot exceed Amara's copied holding.

### 4. Trading bot

As Amara:
1. Open Trading Bots.
2. Create `AAPL Accumulator`:
   - AAPL
   - DCA
   - Buy
   - $50 / trade
   - interval 60
   - max daily trades 3
   - max total spend $250
3. Confirm it starts Paused.
4. Click **Run now** once while paused.
5. Check Bot Execution History, stock portfolio, wallet ledger and Account History.
6. Activate the bot.

Expected: Run now executes one $50-ish fractional AAPL purchase if balance is sufficient. Scheduled runs remain governed by the daily/spend limits.

### 5. Failure truth

Try a bot amount above available balance or a copied trade that exceeds remaining allocation.

Expected: the original/provider trade remains successful; the follower/bot execution is marked Failed or Skipped with a reason. No phantom wallet movement is created.

Stop at the first failed checkpoint and send the exact screen/error before continuing.
