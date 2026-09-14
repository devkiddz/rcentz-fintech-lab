# Rcentz Fintech — Customer UI System Cleanup

This pass starts the repository-wide cleanup from the shared shell instead of redesigning isolated pages.

## Included
- customer shell/sidebar/topbar proportions and theme behavior
- semantic dark/light normalization for legacy Blade surfaces
- shared transaction/form/table/choice-card styling
- complete Investment Buy screen rebuild into the same workspace language
- `.gitignore` repair for ZIPs and Laravel runtime files

## Why this helps older pages too
The customer layout now carries a `customer-workspace` scope. CSS under that scope maps older light-only gradients, inputs, tables and status surfaces back to the shared semantic theme variables, so untouched customer pages stop fighting dark mode while they are progressively migrated.

## Apply
Extract at the project root, then run:

```bash
/c/xampp/php/php.exe artisan optimize:clear
npm run build
```

No migration is required.
