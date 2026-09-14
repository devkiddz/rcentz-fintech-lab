RCENTZ FINANCIAL OVERVIEW + UNIFIED LEDGER

This pass adds the missing account-level financial truth layer.

What is now defined
-------------------
Available Balance
= current spendable wallet balance.

Portfolio Value
= current stock holding value + current investment holding value.

Invested Capital
= cost basis still deployed in current stock/investment holdings.

Total Assets
= Available Balance + Portfolio Value.

Total Return
= Portfolio Value - Invested Capital.

This intentionally does NOT include wallet cash in investment return calculations,
which fixes the old dashboard's inflated gain/loss formula.

Credits / Debits
----------------
Wallet transactions now have queryable credit/debit scopes with support for old
rows where direction is null.

Explicit direction values are preferred.
Legacy fallback:
- deposits/refunds/dividends => credits
- investment descriptions beginning with "Sale of" => credits
- other withdrawals/investment purchases => debits

Unified ledger
--------------
Existing route:
    /wallet/transactions

Now supports filters:
- direction
- type
- status
- date_from
- date_to

No new database table and no migration are required because stock purchases,
stock sales, investment purchases, investment sales, deposits, withdrawals and
internal transfers already create wallet transaction rows.

Files
-----
app/Services/FinancialOverviewService.php
app/Models/WalletTransaction.php
app/Http/Controllers/UserDashboardController.php
app/Http/Controllers/WalletController.php
resources/views/user/dashboard.blade.php
resources/views/wallet/transactions.blade.php

Apply
-----
Extract into:
C:\xampp\htdocs\tesla.com

Then:

/c/xampp/php/php.exe artisan optimize:clear
npm run build

TEST CHECKPOINT
---------------
Use Amara.

1. Open Dashboard before any new transaction.
   Record:
   - Available Balance
   - Total Assets
   - Portfolio Value
   - Invested Capital / Return

2. Buy 1 AAPL.
   Expected:
   - Available Balance falls by the purchase cost.
   - Stock value rises by the current value of that share.
   - Total Assets should remain broadly consistent at execution price because cash
     becomes an asset holding rather than disappearing.
   - Completed Debits increases.
   - Latest activity shows the stock purchase as a negative/debit item.

3. Open /wallet/transactions.
   - Filter Direction = Debits.
   - Confirm the AAPL purchase appears.
   - Filter Direction = Credits.
   - Confirm deposits/sales/incoming transfers appear.

4. Sell that AAPL share.
   Expected:
   - wallet cash rises.
   - stock position/value falls.
   - Completed Credits increases.
   - ledger shows the sale as a positive/credit item.

Important
---------
This pass displays the current state correctly. Pending-withdrawal reservation is
still a separate workflow hardening task; pending withdrawals are not yet reserved
from the wallet balance until approval.
