# Trading Intelligence V2.5 — Manual Presentation Performance

Adds optional admin-controlled P/L and return overrides for AI Bot products and Copy Trading strategies.

## Purpose
This is presentation-only data for portfolio/demo scenarios.

It does NOT:
- credit or debit wallets
- create stock transactions
- create bot executions
- change holdings
- change ledger/history truth
- affect scheduler decisions

## Admin controls
AI Bots > Bot Catalog > Edit
Copy Trading > Strategies > Edit

Each has:
- Use manual presentation performance
- Manual Profit / Loss
- Manual Return %
- Internal presentation note

If the switch is OFF, cards use calculated execution performance.
If ON, only the displayed P/L/Return are overridden.

Customer cards label overridden values as:
- Preview P/L
- Preview Return

## Install
Extract over the Laravel project root, then run:

    /c/xampp/php/php.exe artisan migrate
    /c/xampp/php/php.exe artisan optimize:clear
    npm run build

Do not use migrate:fresh.
