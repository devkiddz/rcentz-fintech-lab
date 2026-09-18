# Signals Engine — S2 Intelligence & Signal Construction

## Status

**Phase:** S2 — Intelligence & Signal Construction

S2 converts the read-only market context established in S1 into deterministic directional analysis, qualification strength and a complete proposed Signal setup.

## Pipeline

```text
SignalMarketContextService
        ↓
SignalAnalysisEngine
        ↓
SignalQualificationService
        ↓
SignalSetupBuilder
        ↓
SignalIntelligenceService
```

S2 does not create or distribute Signals. Persistence and automatic generation begin in S3.

## Analysis Components

The analysis engine combines independent observations:

- trend;
- momentum;
- moving-average alignment;
- market structure;
- support/resistance position;
- multi-timeframe alignment;
- analyst context when available;
- volume confirmation when available.

Each observation produces a signed bias. Weighted biases create a deterministic directional score from -100 to +100.

Positive scores support BUY conditions. Negative scores support SELL conditions. Balanced evidence remains neutral.

## Qualification

Qualification considers:

- directional evidence;
- confluence score;
- market-data quality.

Possible strengths are:

```text
rejected
watch
moderate
strong
very_strong
```

Only qualified setups are eligible for normal Signal generation. Strong and very-strong setups may later become eligible for automatic generation in S3.

## Signal Construction

When a justified BUY or SELL direction exists, S2 constructs:

- direction;
- timeframe;
- entry zone;
- stop loss;
- TP1;
- TP2;
- TP3;
- expiry;
- primary risk/reward;
- rationale;
- qualification flags.

Risk placement is derived from recent market range/ATR-like volatility and nearby support/resistance where that level remains structurally relevant.

## Determinism

S2 contains no random Signal logic.

Given the same market context, the same analysis engine produces the same directional score, qualification and price construction.

## Domain Boundary

S2 remains analysis-only.

It does not:

- execute trades;
- debit wallets;
- mutate Membership state;
- distribute Signals;
- create Signal records;
- alter live market prices.

## Verification

Run:

```powershell
C:\xampp\php\php.exe artisan signals:inspect-intelligence
```

Optional explicit instrument and market:

```powershell
C:\xampp\php\php.exe artisan signals:inspect-intelligence AAPL --marketplace=live
C:\xampp\php\php.exe artisan signals:inspect-intelligence AAPL --marketplace=controlled
```

Successful inspection ends with:

```text
SIGNALS_S2_INTELLIGENCE_OK
```

## Next Phase

**S3 — Automation & Lifecycle**

S3 will add scheduled scanning, automatic candidate generation, lifecycle monitoring, re-analysis and immutable revision/event authority.
