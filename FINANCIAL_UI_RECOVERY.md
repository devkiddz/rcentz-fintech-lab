RCENTZ FINANCIAL UI RECOVERY

Cause
-----
The financial data/business logic is fine.

The break came from reusing the existing `.ui-metric-card` primitive incorrectly.
That class is already defined as a horizontal flex container for:

    [icon] [content wrapper]

The first financial dashboard patch placed:
    icon + label + amount + description

as four direct flex children. On desktop this forced the text into narrow columns,
which is exactly the collision visible in the screenshot.

A second issue was using `.ui-surface` / `.ui-surface-muted`, helpers that are not
part of the currently guaranteed customer UI primitives. That made the Account
Movement / Asset Allocation / Recent Activity areas visually lose their panels.

Fix
---
- Restores the required inner content wrapper for all financial metric cards.
- Uses the existing `.ui-panel` primitive for dashboard/ledger surfaces.
- Replaces `ui-surface-muted` with explicit semantic Tailwind utilities.
- No controller/model/service/database changes.
- No migration.
- Financial calculations from the previous patch remain untouched.

Apply
-----
Extract this ZIP over the project root.

Then run:

    /c/xampp/php/php.exe artisan optimize:clear
    npm run build

TEST
----
1. Reload Dashboard at the same desktop width as the screenshot.
2. The four top financial cards must have:
   - icon on the left
   - label / amount / note vertically stacked
   - no text collision
3. Credits & Debits and Asset Allocation must appear inside clear bordered panels.
4. Recent Activity must appear inside one bordered panel.
5. Open /wallet/transactions and confirm its four summary cards are also stacked correctly.

No financial transaction needs to be performed for this test.
