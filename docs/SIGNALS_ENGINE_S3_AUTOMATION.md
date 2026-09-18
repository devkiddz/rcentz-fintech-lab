# Signals Engine — S3 Automation & Lifecycle

## Status

**Phase:** S3 — Automation & Lifecycle
**Roadmap:** Signals S1 → S6
**Purpose:** Convert the deterministic S2 intelligence layer into persistent automatic Signal generation, lifecycle monitoring, re-analysis and immutable revision/event authority.

## Boundary

S3 mutates the Signal domain only.

It does not:

- distribute Signals to customers;
- create customer notifications;
- enforce Membership quotas;
- execute trades;
- debit wallets;
- schedule background automation yet.

Scheduler wiring remains part of S5. S3 exposes safe services and commands that S5 can schedule later.

## Generation Flow

```text
Stock / Market Authority
        ↓
S2 Intelligence
        ↓
Qualification
        ↓
Auto-generation eligible?
        ↓
Duplicate + cooldown checks
        ↓
Signal(status = ready)
        ↓
Targets + Analysis Run + Generated Event
```

Automatic generation is deterministic. A scan does not force a Signal into existence.

If S2 rejects the setup, the analysis run is recorded but no Signal is created.

## Duplicate Protection

Generation uses two protections:

1. a per-instrument/per-marketplace cache lock;
2. a database check preventing more than one open Signal for the same instrument and marketplace.

`--force` bypasses cooldown only. It does not bypass the open-Signal rule.

## Cooldown

After a Signal leaves the open lifecycle, a timeframe-based cooldown prevents immediate repeated generation from the same market condition.

Typical cooldowns:

```text
5m  → 15 minutes
15m → 45 minutes
1h  → 3 hours
4h  → 12 hours
1d  → 1 day
1w  → 7 days
```

## Signal States

S3 generates Signals into `ready` state.

```text
READY
  ↓ publication occurs in S4
PUBLISHED
  ↓ entry reached
ACTIVE
  ├── targets
  ├── stop loss
  ├── expiry
  └── re-analysis
```

A ready Signal may expire before publication.

S3 lifecycle processing does not auto-publish or auto-distribute. Those authorities belong to S4/S5.

## Lifecycle Authority

`SignalLifecycleService` reads the same market authority established in S1.

It can:

- expire ready/published/active Signals;
- activate a published Signal when price enters the entry zone;
- mark targets as hit;
- stop a Signal when stop loss is reached;
- close a Signal when all targets are reached;
- write immutable Signal events for each lifecycle transition.

## Automatic Re-analysis

Open Signals can be re-analyzed using the same S2 intelligence contract.

Possible outcomes include:

```text
NO_CHANGE
STRENGTHENED
WEAKENED
ADJUSTMENT_RECOMMENDED
ADJUSTED
INVALIDATED
```

If fresh analysis no longer supports the original direction, the Signal is invalidated.

## Safe Adjustment Rules

Before activation, automatic re-analysis may materially update:

- entry zone;
- stop loss;
- pending targets;
- strength;
- confluence metadata.

After activation:

- the entry zone is locked;
- stop loss may only tighten risk, never widen it;
- hit targets are immutable;
- pending targets may only move to valid prices still ahead of the current market;
- every material mutation must create a `SignalRevision` and an `adjusted` event.

Published history is never silently overwritten.

## Analysis History

Every automatic generation or re-analysis attempt creates a `SignalAnalysisRun`.

Rejected scans therefore remain auditable without creating fake Signals.

To avoid database bloat, S3 stores compact market snapshots rather than copying complete candle histories into every analysis row.

## Commands

Scan one instrument:

```powershell
C:\xampp\php\php.exe artisan signals:scan AAPL --marketplace=live --force
```

Scan the active catalogue:

```powershell
C:\xampp\php\php.exe artisan signals:scan --limit=25
```

Re-analyze the latest open Signal:

```powershell
C:\xampp\php\php.exe artisan signals:reanalyze
```

Re-analyze without applying adjustments:

```powershell
C:\xampp\php\php.exe artisan signals:reanalyze --no-adjust
```

Process Signal lifecycle:

```powershell
C:\xampp\php\php.exe artisan signals:process-lifecycle
```

Inspect S3 authority:

```powershell
C:\xampp\php\php.exe artisan signals:inspect-automation
```

Exercise the real revision service inside a rolled-back transaction:

```powershell
C:\xampp\php\php.exe artisan signals:inspect-automation --exercise-revision
```

The revision probe must end with:

```text
REVISION_ROLLBACK_PROBE=PASS
```

and must leave no persistent probe mutation.

## Next Phase

**S4 — Admin, Distribution & Notifications**

S4 will expose the Signal Desk, manual creation/editing, re-analyze controls, publish/distribute authority, complimentary distribution and dashboard notification integration.
