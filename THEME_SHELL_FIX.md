RCENTZ THEME + AUTH SHELL FIX

What this patch fixes

1. Customer theme toggle now works.
   The customer layout currently calls:
       window.AxausTheme.toggle()
   but app.js only exposed:
       window.toggleTheme()
   This patch exposes both APIs.

2. Admin and customer themes are separated.
   Old behavior:
       localStorage["theme"]
   New behavior:
       localStorage["rcentz_theme:admin"]
       localStorage["rcentz_theme:customer"]
       localStorage["rcentz_theme:public"]

3. Admin impersonation no longer forces the customer workspace to inherit the
   admin workspace preference. The active route determines the theme scope.

4. Existing users keep their current preference.
   The old "theme" value is copied into the first active scoped key automatically.

5. System theme fallback remains supported when no explicit preference exists.

Apply
-----
Extract this archive into the Laravel project root, replacing files.

Then run:

    /c/xampp/php/php.exe artisan optimize:clear
    npm run build

Visual test
-----------
A. Log in as admin.
B. Set admin to DARK.
C. Impersonate a customer.
D. Toggle customer to LIGHT.
E. Stop impersonating.
F. Admin should still be DARK.
G. Impersonate the same customer again.
H. Customer workspace should still be LIGHT.

No migration is required.

Important architectural note
----------------------------
This fixes the shared theme behavior across the existing Admin and Customer shells.
The next UI cleanup can now safely consolidate repeated topbar/header markup without
mixing theme state.
