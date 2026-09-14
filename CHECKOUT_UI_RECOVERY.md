# Checkout UI Recovery

Replaces the legacy vehicle checkout Blade with a balanced, responsive application-style checkout while preserving the existing form action and backend field names.

## Install
Extract into the Laravel project root and overwrite matching files, then run:

```bash
/c/xampp/php/php.exe artisan optimize:clear
npm run build
```

## Test checkpoint
Open a vehicle, continue to checkout, complete the form, select a payment method, accept the terms and submit the order. Verify the order reaches the expected confirmation/history state.
