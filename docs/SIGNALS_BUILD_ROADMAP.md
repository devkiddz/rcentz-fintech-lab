# Signals Build Roadmap

## Status

**Feature:** Signals Engine
**Build strategy:** Straight-line phased implementation
**Canonical roadmap:** S1 → S6

The Signals feature will be implemented in six consolidated phases.

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
