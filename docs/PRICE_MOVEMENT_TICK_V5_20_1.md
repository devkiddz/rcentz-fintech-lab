# V5.20.1 — Price Movement Tick

Adds a lightweight tick-to-tick movement indicator to runtime-managed market prices.

## Behaviour

- Compares the previous rendered authoritative price with the newest server runtime price.
- Shows a small rising/falling arrow and percentage for the latest movement.
- First snapshot only seeds state and does not fabricate movement.
- The badge fades after 2.2 seconds and reappears on the next actual price change.
- It does not replace the existing session/day change percentage.
- No financial value is calculated client-side beyond the temporary visual comparison.
- External/Internal price authority remains server-owned.

This is presentation-only and introduces no database migration.
