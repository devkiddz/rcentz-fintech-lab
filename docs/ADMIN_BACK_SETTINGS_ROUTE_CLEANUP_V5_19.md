# Rcentz V5.19 — Admin Back Helper + Settings Route Cleanup

## Back helper

The admin top bar now includes a universal Back control. It returns through browser history only when the referrer is a same-origin admin page; otherwise it falls back to the Admin Dashboard. On mobile the label is hidden and the arrow remains visible.

## Settings route cleanup

V5.18 consolidated Settings read navigation to:

GET /admin/settings?section=<section>

V5.19 scans admin Blade views for obsolete named GET route helpers such as admin.settings.market, admin.settings.mail, admin.settings.security, etc. and rewrites them to the unified settings index route with the appropriate section query.

This fixes the Market Operations page error caused by the removed admin.settings.market route.

No database migration is required.
