# Rcentz V5.14.4 — Admin Settings Hub

The Admin Settings page is the visible configuration control plane.

## Market settings

/admin/settings now exposes the core market configuration:

- External Feed / Internal Feed price source selection
- external feed health summary
- market direction
- movement strength
- automatic movement interval
- exposure isolation summary

Instrument registration, price reset, pause/activate and manual tick remain available through the dedicated market operations page because those are operational market actions rather than generic key/value settings.

## Settings model contract fix

The settings Blade view already supported a password field type but the Setting model did not implement isPassword(). V5.14.4 completes that model/view contract.

Password fields also preserve their current stored value when the form is submitted blank. This matches the existing UI promise: “leave blank to keep current”.
