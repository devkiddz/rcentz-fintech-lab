RCENTZ AUTHENTICATED SHELL CONSOLIDATION

Purpose
-------
This pass removes duplicated topbar/theme/flash-message markup from the two
authenticated workspaces.

What changed
------------
Customer shell:
- one customer topbar partial
- one shared theme toggle partial
- notifications remain in the customer shell
- impersonation exit stays visible and responsive
- semantic theme tokens replace mixed hardcoded light/dark topbar classes
- body is explicitly scoped as data-theme-scope="customer"

Admin shell:
- one admin topbar partial
- uses the exact same shared theme toggle
- admin workspace remains its own shell/navigation
- body is explicitly scoped as data-theme-scope="admin"

Shared:
- shared flash-message partial
- shared shell CSS primitives
- the previously tested scoped-theme JS is included so this patch cannot
  accidentally regress independent admin/customer theme preferences.

New files
---------
resources/views/partials/shell/theme-toggle.blade.php
resources/views/partials/shell/customer-topbar.blade.php
resources/views/partials/shell/admin-topbar.blade.php
resources/views/partials/shell/flash-messages.blade.php

Replaced files
--------------
resources/views/layouts/user-layout.blade.php
resources/views/layouts/admin-layout.blade.php
resources/views/partials/theme-init.blade.php
resources/js/app.js
resources/css/app.css

Apply
-----
Extract into the project root, then run:

/c/xampp/php/php.exe artisan optimize:clear
npm run build

No migration.

TEST CHECKPOINT
---------------
1. Customer login:
   - topbar renders
   - theme toggle works
   - notification dropdown opens
   - sidebar opens on mobile width

2. Admin login:
   - admin topbar renders
   - theme toggle works
   - admin sidebar still works

3. Impersonation:
   - start from dark admin
   - impersonate light customer
   - Stop impersonating is visible
   - return to admin and confirm admin remains dark

4. Flash messages:
   - perform any action that creates success/error feedback
   - alert should render correctly in both light and dark mode.

Architecture result
-------------------
There are now only two intentional authenticated shell headers:
CustomerTopbar and AdminTopbar.
The theme control and alert language are shared rather than duplicated.
