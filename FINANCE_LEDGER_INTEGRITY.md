# Finance Ledger Integrity milestone

This milestone adds an explicit credit/debit direction to wallet ledger entries so sale proceeds are shown as incoming funds instead of investment debits. It also updates wallet, stock, investment, admin balance-adjustment and automatic-investment transaction writers to record direction consistently.

## Apply

```bash
/c/xampp/php/php.exe artisan migrate
/c/xampp/php/php.exe artisan optimize:clear
npm run build
```

## Test checkpoint

After applying, perform one stock buy and one stock sell, then one investment buy and sell. Verify the wallet balance, holdings, and Recent Activity signs after each action.
