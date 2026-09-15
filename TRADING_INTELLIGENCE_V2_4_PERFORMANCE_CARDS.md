# Trading Intelligence V2.4 — Performance Cards

Adds labelled performance metrics to AI Trading Bots and Copy Trading.

Metrics:
- Profit / Loss
- Return %
- Executed trades
- Win rate
- Minimum amount / minimum balance
- Allocation / used amount
- Copy percentage
- Per-trade amount
- Allocation cap
- Access price

P/L is calculated from completed executions against the current stock price.
No fake performance numbers are stored. With no executions, metrics correctly show zero.

Install:
    /c/xampp/php/php.exe artisan optimize:clear
    npm run build

No migration required.
