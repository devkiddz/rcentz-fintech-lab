# Signals Engine — S1 Foundation & Market Wiring

## Status

**Phase:** S1 — Foundation & Market Wiring
**Roadmap:** Signals S1 → S6
**Purpose:** Establish the Signal domain and connect it to the existing market authority without introducing Signal intelligence, automation, distribution UI or Membership enforcement yet.

## Boundary

Signals are market-analysis records. They do not execute trades, debit wallets or mutate Membership state.

```text
Existing Market Authority
Stock / Quotes / Candles / Controlled Market
              ↓
      MarketPriceRouter
              ↓
      StockAnalysisService
              ↓
   SignalMarketContextService
              ↓
          Signal Domain
```

S1 reuses the current `Stock` catalogue as the canonical tradable instrument identity. No parallel Signal instrument table is introduced.

## Persistence

S1 introduces:

- `signals`
- `signal_targets`
- `signal_analysis_runs`
- `signal_revisions`
- `signal_events`
- `signal_distributions`
- `signal_deliveries`

These tables intentionally separate current Signal state from historical analysis, revisions, lifecycle events and customer distribution audit.

## Market Wiring

`SignalMarketContextService` reads through existing market services:

- `MarketPriceRouter`
- `StockAnalysisService`
- `MarketSessionService`
- Live Stock quotes/history/candles
- Controlled Market instruments/ticks

The service is read-only. It does not create Signals by itself.

## S1 Inspection Command

After migration:

```powershell
C:\xampp\php\php.exe artisan signals:inspect-foundation
```

Optional marketplace selection:

```powershell
C:\xampp\php\php.exe artisan signals:inspect-foundation --marketplace=live
C:\xampp\php\php.exe artisan signals:inspect-foundation --marketplace=controlled
```

A successful inspection ends with:

```text
SIGNALS_S1_FOUNDATION_OK
```

## Membership Boundary Correction

The customer Membership workspace is corrected at this baseline:

```text
Membership
└── Overview
```

The customer sees the current membership plan, status, dates and human-readable privileges only. Membership types and technical entitlement keys are not customer navigation domains.

Admin Membership management remains independent and data-driven.

## Next Phase

**S2 — Intelligence & Signal Construction**

S2 will convert market context into deterministic analysis, qualification strength and complete Signal setups containing direction, entry, stop, targets, timeframe, expiry and risk/reward.
