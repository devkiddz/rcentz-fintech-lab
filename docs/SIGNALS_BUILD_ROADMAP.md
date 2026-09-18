# Signals Build Roadmap

## Status

**Feature:** Signals Engine
**Build strategy:** Straight-line phased implementation
**Canonical roadmap:** S1 → S6

The Signals feature will be implemented in six consolidated phases.

## Implementation checkpoint — 2026-09-18

Current accepted implementation status:

- **S1 — Foundation & Market Wiring:** complete.
- **S2 — Intelligence & Signal Construction:** complete.
- **S3 — Automation & Lifecycle:** complete and sealed at `24ddc4809526458394fcde3220b9b4a785bf1b36`.
- **S4 — Admin, Distribution & Notifications:** implemented and browser/runtime accepted; includes Signal Desk, publication, audited distribution, Recipients management and separate admin/customer notification authority.
- **S5 — Customer Experience & Performance:** implemented through customer notification repair, ownership-bound Current/History/detail surfaces, autonomous runtime, lifecycle-derived performance, customer lifecycle notifications/revision timeline and live timing intelligence.
- **Forex FX1 — Market Foundation:** implemented, migrated and seeded with 10 first-class Forex pairs plus a generic `MarketInstrument` registry. EUR/USD has 100 persisted real daily OHLC rows and reports `READY`.
- **Forex FX2 — Signal Engine Wiring:** next implementation step. FX2 must route `MarketInstrument` through Stock/Forex context providers without pretending Forex pairs are stocks. The first FX2 installer performed no writes because its accepted-state check for the admin Signal Room view was stale; the current accepted SHA256 is `8853EA9F8968B6EF9CB16677445B9849DB5E572D5898FB8121DFC13F4F52803B`.

Current authority rules remain:

```text
SignalDelivery = customer ownership/access authority
Notification = alert only
SignalEvent / SignalRevision = lifecycle/audit authority
MarketInstrument = generic market identity authority
```

Pending operational acceptance before the scheduler is restarted: run the idempotent S5 lifecycle-notification recovery for the existing AAPL adjustment and confirm the customer timeline/timing UI. Only one `artisan schedule:work` process should be active after maintenance.

Each phase must leave the application in a stable, testable state before proceeding to the next phase.

---

## Signals Architecture Roadmap

| Phase | Contains | Result |
| --- | --- | --- |
| **S1 — Foundation & Market Wiring** | old S0–S2 | Clean baseline + Signal models/tables + connection to existing Stocks, Live/Controlled markets, candles, prices and analysis infrastructure. |
| **S2 — Intelligence & Signal Construction** | old S3–S5 | Analysis → qualification/strength → complete trade setup with direction, entry, SL, targets, timeframe, expiry and R:R. |
| **S3 — Automation & Lifecycle** | old S6–S9 | Automatic scanning/generation + lifecycle monitoring + automatic re-analysis + immutable revisions/events. |
| **S4 — Admin, Distribution & Notifications** | old S10–S12 | Complete Signal Desk: manual creation, re-analyze, edit, publish, distribute, complimentary distribution and dashboard notifications. |
| **S5 — Customer Experience & Performance** | old S13–S15 | Customer Active/History/detail pages + autonomous scheduler runtime + real signal outcome/performance tracking. |
| **S6 — Trading + Membership Integration & Hardening** | old S16–S18 | “Trade this signal” bridge + Membership limits/instruments/access + final concurrency/idempotency/browser acceptance. |

---

# S1 — Foundation & Market Wiring

S1 establishes the Signals domain and connects it to the existing market infrastructure.

The Signals Engine must reuse existing market authorities instead of creating duplicate price or instrument systems.

Existing infrastructure to reuse includes:

- Stocks / tradable instruments
- Live Market
- Controlled Market
- MarketPriceRouter
- Stock candles
- Stock quotes
- Stock price history
- StockAnalysisService
- Existing notification infrastructure where appropriate

S1 introduces the core Signals persistence layer.

Expected domain concepts include:

- Signal
- Signal Target
- Signal Analysis Run
- Signal Revision
- Signal Event
- Signal Distribution
- Signal Delivery

At the end of S1, Signals should have a clean domain foundation and trustworthy access to market data.

No automated trading execution belongs inside the Signal domain.

---

# S2 — Intelligence & Signal Construction

S2 builds the intelligence responsible for deciding whether a valid market opportunity exists.

The analysis pipeline should consider multiple independent factors rather than a single indicator.

Possible analysis components include:

- market structure;
- trend;
- momentum;
- support and resistance;
- volatility;
- moving averages;
- volume where reliable;
- price position;
- multi-timeframe agreement;
- data freshness;
- market quality;
- risk/reward potential.

Conceptually:

```text
Market Context
      ↓
Structure Analysis
Trend Analysis
Momentum Analysis
Volatility Analysis
Level Analysis
      ↓
Confluence / Qualification
      ↓
Signal Construction
