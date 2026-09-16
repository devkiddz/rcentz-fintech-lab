# Rcentz V5.16 — Admin Navigation + Email Configuration

## Admin navigation

The admin shell now has two responsive navigation states:

- Desktop: full navigation or a persisted compact icon rail. The top-bar panel button toggles the state and the preference is stored locally per admin workspace.
- Mobile/tablet: the sidebar is an off-canvas drawer. Menu opens it; the close button, backdrop, Escape key, or selecting a navigation link closes it. Background scrolling is locked while the drawer is open.

If a grouped menu is clicked while the desktop rail is collapsed, the rail expands before the group opens so nested routes never become inaccessible.

## Email configuration

`/admin/settings/mail` is now a writable mail configuration surface with:

- transport: SMTP, log, array
- SMTP host and port
- automatic TLS/STARTTLS or implicit SMTPS
- SMTP username
- SMTP password
- global From address and From name
- delivery test

Operational mail overrides are stored in the existing `settings` table. Passwords are encrypted with Laravel's application encryption key and are never rendered back to the browser. Leaving the password field blank preserves the current credential.

The runtime override is applied by `MailConfigurationService` during application boot. Environment/config mail values remain the fallback when no database override exists. The admin UI does not edit `.env`.

No database migration is required.
