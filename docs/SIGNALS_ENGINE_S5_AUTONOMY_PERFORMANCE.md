# Signals Engine S5 — Autonomous Runtime & Performance Authority

## Purpose

S5.3 moves the accepted Signal lifecycle from manual command execution into the Laravel scheduler while keeping lifecycle truth, analysis truth and customer delivery truth separate.

## Scheduled authority

The scheduler now owns two independent loops:

1. `signals:process-lifecycle` every minute
   - checks published/active Signals against the canonical market router;
   - activates entry-zone hits;
   - records target hits;
   - records stop loss, expiry and closure outcomes.

2. `signals:run-autonomy --limit=25` every five minutes
   - re-analyzes only Signals whose timeframe-specific scheduled re-analysis interval is due;
   - performs a broad scheduled scan only when the 15-minute scan window is due;
   - uses application locks plus scheduler overlap guards;
   - keeps open-Signal duplicate protection and generation cooldowns authoritative.

The autonomous runner does not publish or distribute Signals. Generated Signals still enter `ready` state and remain under admin publication authority.

## Re-analysis cadence

| Timeframe | Scheduled re-analysis interval |
| --- | ---: |
| 5m | 5 minutes |
| 15m | 15 minutes |
| 1h | 30 minutes |
| 4h | 60 minutes |
| 1d | 240 minutes |
| 1w | 1440 minutes |
| fallback | 60 minutes |

Only runs with trigger `scheduled_reanalysis` advance this cadence. Scheduled scanner observations do not accidentally suppress contract re-analysis.

## Performance authority

`SignalPerformanceService` derives performance from persisted Signal lifecycle truth. It does not invent trading P&L because a Signal is an opportunity contract, not an executed customer position.

Tracked outcomes:

- open / active Signals;
- terminal Signals;
- closed Signals where all configured targets were reached;
- stopped Signals;
- expired Signals;
- invalidated Signals;
- cancelled Signals;
- targets reached versus configured targets;
- target reach rate;
- resolved success rate;
- average confluence.

Resolved success rate is deliberately defined as:

`closed / (closed + stopped)`

Expired, invalidated and cancelled Signals are excluded from that ratio and shown separately.

The service supports both platform-wide metrics and customer-scoped metrics based on `SignalDelivery` ownership.

## Boundaries retained

- No automatic publication.
- No automatic distribution.
- No wallet debit.
- No trade execution.
- No membership-policy expansion.
- No fake P&L or win-probability claim.
- No schema migration in S5.3.

## Runtime acceptance checkpoint — 2026-09-18

The real scheduler executed autonomous Signal work successfully. At 2026-09-18 15:10:44, AAPL Signal #1 was re-analyzed by `scheduled_reanalysis`, moved from VERY_STRONG / 78.87% to STRONG / 73.06%, created immutable Revision #1 and an `adjusted` event, and preserved the BUY direction, entry zone, stop and risk/reward contract. The same scheduled cycle scanned AAPL and correctly recorded `duplicate_open` instead of creating a second open Signal. Scheduler work is intentionally stopped during the current maintenance/Forex wiring window.
