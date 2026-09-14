# Wallet UI Balance Pass

This patch is a presentation-only refinement of the wallet Blade files introduced in the recent milestones.

Included:
- Wallet overview
- Internal transfer
- Connected wallets
- Shared user layout
- Shared app CSS

No new database migration is required.

After extraction run:

```bash
/c/xampp/php/php.exe artisan optimize:clear
npm run build
```
