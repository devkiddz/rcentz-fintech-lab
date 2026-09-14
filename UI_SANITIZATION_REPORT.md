# Rcentz UI Sanitization Pass

## Scope inspected

- Total Blade templates in reconstructed project: **166**
- Authenticated/admin Blade templates inspected: **100**
- Authenticated/admin Blade templates included in this patch: **100**
- Public marketing/email/vendor/PDF templates were deliberately excluded from destructive normalization.

## Static legacy-token audit

| Signal | Before | After source normalization |
| --- | ---: | ---: |
| Tesla palette tokens | 656 | 620 |
| Manual `dark:*dark-*` tokens | 85 | 8 |
| Gradient utilities | 106 | 101 |
| Manual gray palette tokens | 1302 | 1156 |
| Narrow max-width shells | 15 | 13 |

The remaining legacy color/gradient tokens are covered by the new **workspace-scoped compatibility layer** in `resources/css/app.css`. This is intentional: instead of rewriting every historical Blade expression and risking functional regressions, old Tesla/gray surfaces are centrally forced onto semantic `background/card/muted/border/foreground` tokens.

## Structural fixes

1. Added a reusable Money Movement visual language (`money-*` classes).
2. Rebuilt Deposit and Withdrawal screens from scratch.
3. Updated Transfer to use **Available Balance** truth and linked History.
4. Sanitized crypto deposit confirmation.
5. Added **Account Overview** to the Account sidebar group.
6. Neutralized legacy Tesla/red full-surface styling across authenticated customer/admin workspaces.
7. Normalized old custom dark-mode class names.
8. Neutralized old hard-coded form backgrounds/borders centrally.
9. Reduced giant legacy shadows inside application content.
10. Cleaned vehicle purchase history away from full-red table surfaces.

## Important design rule going forward

Do not add new arbitrary `tesla-*`, `gray-*`, `dark:*dark-*`, or gradient page shells to authenticated Blade pages.

Use:
- `ui-page`
- `ui-panel`
- `ui-btn*`
- `ui-input`
- `ui-metric-*`
- `money-*`
- semantic tokens: `bg-background`, `bg-card`, `bg-muted`, `text-foreground`, `text-muted-foreground`, `border-border`.

Status colors (green/amber/red) remain allowed because they communicate state rather than theme.

## Installation

Extract over the project root, then run:

```bash
/c/xampp/php/php.exe artisan optimize:clear
npm run build
```

No migration is required for this UI-only patch.

## Visual test checklist

1. Dashboard — light and dark.
2. Wallet overview.
3. Deposit — no red/gradient hero; wide two-column transaction layout on desktop.
4. Withdraw — same layout as Deposit; available/wallet/reserved truth visible.
5. Transfer — uses Available Balance.
6. Connected wallets.
7. Transactions + History.
8. Stock Buy/Sell.
9. Investment Buy/Sell.
10. Portfolio/Stocks screens.
11. Profile + KYC.
12. Admin dashboard and one index/show/form route.
13. Mobile sidebar + bottom nav.
14. Impersonation with independent admin/customer themes.

If a legacy page still looks visibly inconsistent after this patch, send its screenshot; the compatibility layer should make the remaining fixes local rather than requiring another system-wide redesign.
