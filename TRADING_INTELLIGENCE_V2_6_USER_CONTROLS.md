# Trading Intelligence V2.6 — User Controls & Detail Routes

This phase adds three upgrades.

## 1. Manual performance auto-calculation
Admin no longer needs to type both fields manually.

Bot calculation base:
1. Max user allocation
2. Minimum balance
3. Default trade amount

Copy strategy calculation base:
1. Recommended allocation
2. Minimum allocation

Typing Profit/Loss automatically calculates Return %.
Typing Return % automatically calculates Profit/Loss.
A hidden source field lets the server preserve whichever field the admin edited last.

## 2. Customer edit capabilities

AI Bots > My Bots:
- Configure button
- Trade amount
- Trigger price where allowed
- Interval
- Max daily trades
- Allocation cap

Copy Trading > My Copied Strategies:
- Edit Copy Settings button
- Allocation
- Max per trade
- Copy percentage
- Active / paused / stopped

Customers only edit their runtime/subscription settings.
They do not edit the admin-owned product or provider strategy.

## 3. Clickable history records

AI Bot Performance history now has View.
Copy Trading execution history now has View.

Each record opens a concise detail card with:
- action / symbol
- amount
- entry/current price where available
- current P/L
- current return
- status
- timestamp
- failure/reason where relevant

## Install

Extract over the Laravel project root, then run:

    /c/xampp/php/php.exe artisan optimize:clear
    npm run build

No migration is required for V2.6 itself.
V2.5 migration must already be installed.
