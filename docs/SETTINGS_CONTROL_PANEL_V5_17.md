# Rcentz V5.17 — Collapsible Settings Control Panel

V5.17 makes the Settings Control Plane responsive independently of the main admin sidebar.

## Desktop

- The settings navigation can collapse from its full 250px panel into a compact icon rail.
- The preference is persisted in localStorage across every Settings route.
- All current and reserved domains remain discoverable through icon titles.
- The content column expands automatically when the panel is collapsed.

## Mobile

- The settings navigation becomes an off-canvas sheet instead of occupying page width.
- A compact Settings Navigation trigger opens the sheet.
- The sheet closes from the X button, backdrop, Escape key, or after navigating.
- Body scrolling is locked while the sheet is open.

## Architecture

This uses the existing Blade + Tailwind shell rather than adding a second component runtime. It follows the same interaction pattern as a shadcn Sheet while remaining native to the current Laravel UI stack.

No database migration is required.
