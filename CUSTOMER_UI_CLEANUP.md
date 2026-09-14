# Customer UI cleanup pass

This pass establishes one shared application UI language for the authenticated customer workspace and replaces the old investment-plan screen with the new system.

## Included
- Roomier shared customer layout
- Consistent 1440px workspace canvas
- Shared shadcn-inspired UI primitives in `resources/css/app.css`
- Complete Investment Plans redesign
- Responsive filter bar
- Compact metrics
- Balanced featured-plan cards
- Dense, readable all-plans list
- Unified button, panel, field, badge, empty-state and pagination primitives

## Apply
Extract into the Laravel project root, overwrite matching files, then run:

```bash
/c/xampp/php/php.exe artisan optimize:clear
npm run build
```

No migration is required.
