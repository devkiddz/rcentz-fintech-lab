# Signals + Forex Current Checkpoint — 2026-09-18

## Repository checkpoint

The previously sealed base is Signals S3 at:

`24ddc4809526458394fcde3220b9b4a785bf1b36`

The current working milestone contains accepted S4/S5 implementation plus Forex FX1 foundation and is intended to be committed as the next repository checkpoint before FX2.

## Signals acceptance

- S4 admin Signal Desk, publication, Recipients audit and complimentary delivery are implemented.
- Signal #1 AAPL is active and has two real customer deliveries.
- Customer notification infrastructure is repaired and browser accepted.
- Customer Current / History / ownership-bound Signal detail pages are implemented.
- Autonomous lifecycle and re-analysis are scheduler-wired.
- Real autonomous AAPL re-analysis produced immutable Revision #1: VERY_STRONG / 78.87% → STRONG / 73.06%.
- Lifecycle-derived performance does not invent Signal P&L or unresolved win rates.
- Customer lifecycle notification fan-out and revision timeline are installed.
- Timing intelligence exposes entry timing bands, timeframe and session context without mutating Signal terms.

## Pending S5 operational action

The historical AAPL adjustment-notification recovery has been dry-run accepted but not yet executed:

```powershell
C:\xampp\php\php.exe artisan signals:sync-customer-notifications 1 --event=adjusted
```

After the real recovery, the idempotency dry-run should report zero new alerts and two existing alerts.

## Forex FX1 acceptance

The Forex foundation is first-class rather than modeled as Stock rows.

Initial pair universe:

- EUR/USD
- GBP/USD
- USD/JPY
- USD/CHF
- AUD/USD
- USD/CAD
- NZD/USD
- EUR/GBP
- EUR/JPY
- GBP/JPY

Generic market registry count at acceptance:

```text
stock 10
forex 10
```

EUR/USD has 100 real persisted daily OHLC rows, latest date 2026-09-17 and latest close 1.14740. It reports `READY`; the remaining nine pairs remain `NEEDS_HISTORY` until intentionally refreshed.

## FX2 next step

FX2 will wire `MarketInstrument` into the Signal pipeline:

```text
MarketInstrument
      ↓
Stock / Forex context provider
      ↓
SignalAnalysisEngine
      ↓
Qualification
      ↓
Entry / SL / Targets
      ↓
READY Signal
```

No FX2 writes are currently accepted. The first FX2 installer stopped during precheck because it expected an older admin Signal Room view. Current accepted SHA256:

`8853EA9F8968B6EF9CB16677445B9849DB5E572D5898FB8121DFC13F4F52803B`

The accepted Signal Room UI must be preserved when FX2 is rebuilt.

## Scheduler maintenance state

During this checkpoint, `artisan schedule:work` is intentionally stopped. Do not run multiple scheduler workers. After maintenance/browser acceptance, restart exactly one scheduler authority.

## Authority rules

```text
SignalDelivery = customer ownership/access authority
Notification = alert only
SignalEvent / SignalRevision = lifecycle/audit authority
MarketInstrument = generic market identity authority
```
