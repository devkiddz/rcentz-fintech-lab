# Rcentz V5.14.3 — Automatic Market Runtime + Solemn UI

V5.14.3 closes the runtime and trade-result gaps while keeping price-source terminology out of normal trading views.

## Transaction result panel

The tenth cell on Admin Stock Transaction Details now reports the linked trade contract result instead of rendering blank space:

- open contract → Current Trade P/L + current return
- closed contract → Realized P/L + realized return
- legacy/unlinked execution → explicit "No linked contract result yet"

The stock context is also bound to the transaction's own marketplace rather than whichever global desk happens to be active.

## Automatic Controlled Market clock

The internal price engine exposes tickIfDue(). Both the Laravel scheduler and browser heartbeat use it. The method:

1. reads market_environments.controlled_tick_seconds,
2. checks whether Controlled Market is active,
3. acquires one cache lock,
4. rechecks latest instrument last_moved_at,
5. advances all active Controlled instruments only when actually due.

This prevents multiple browser tabs and the scheduler from multiplying price movement.

## Runtime browser updates

Authenticated pages can call GET /market-runtime. The heartbeat updates tagged:

- market prices,
- previous price and change,
- linked position CMP / difference / P&L / return,
- full and mini analysis charts.

The browser heartbeat is also a local-XAMPP fallback when schedule:work is not resident. Production schedule:work remains a valid background driver.

No database migration is required because controlled_tick_seconds already existed in V5.11.
