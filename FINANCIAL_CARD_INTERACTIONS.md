RCENTZ FINANCIAL SUMMARY CARD INTERACTIONS

Changes
-------
Dashboard summary cards are now clickable:

- Available Balance -> Wallet
- Total Assets -> Transaction ledger
- Portfolio Value -> Investment Portfolio
- Total Return -> Portfolio Analytics

The transaction-history summary cards are also clickable for consistency:

- Available Balance -> Wallet
- Total Assets -> Dashboard
- Completed Credits -> ledger filtered to credits
- Completed Debits -> ledger filtered to debits

The Dashboard page header now includes an Account button linking to Profile / Account.

Why
---
The top metrics now behave like true dashboard navigation surfaces instead of
static reporting blocks. The Account button also gives the customer a direct,
intentional path into their own identity/settings area.

Apply
-----
Extract over the project root, then run:

    /c/xampp/php/php.exe artisan optimize:clear
    npm run build

No migration required.

Test
----
1. Dashboard:
   - click all four summary cards
   - confirm each destination opens
   - Account button opens the profile/account page

2. Transactions:
   - click Credits -> only credit ledger rows should show
   - click Debits -> only debit ledger rows should show
