# Market Engine Upgrade Roadmap

Date: 5 October 2026
Status: agreed design direction; implementation pending
Current platform: PHP / Laravel
Purpose: product demonstration with simulated account settlement

## Product direction

Keep the existing trading experience: charts, prices, Buy/Sell, positions, receipts and account updates. Rename configured instrument presentations to baskets and indices; preserve real instrument names in live mode. Do not add repetitive price-generation explanations to instrument cards.

Basket prices use saved live reference prices and market strength as influences. They are independent prices between provider observations; they must never overwrite actual live quotes internally. “CMP” means current market price, not market capitalization.

Names such as Euro Basket and Metals Basket are provisional. Final names, symbols and mapping need to be settled before installation. A label change must not mutate historical trade identity or imply holdings the engine does not maintain.

## Implementation sequence

### 1. Inspect and separate price identities

Audit instrument IDs, marketplace routing, configured feeds, native feeds, charts and execution consumers. Decide whether marketplace-specific display metadata is sufficient or separate instrument identities are necessary. Preserve existing positions, receipt snapshots and settlement records. Do not globally rename a shared live instrument row.

### 2. Persist market references

Save provider symbol, latest verified price, provider observation time, receipt time, source, health and recent observations. Keep these durable in the database; process variables are only temporary working values. Reject wrong symbols, invalid prices and older observations. Separate reference values from basket CMPs.

### 3. Verify provider access and calculate strength

Inspect candidate fiat endpoints, plan access, timestamps and costs before integration. Dollar strength is relative movement against multiple currencies, not a cryptocurrency price or market capitalization. Choose and document pairs, weights, orientation and comparison window. Normalize movements and require sufficient valid observations. Combine broad USD strength with instrument-specific trend and volatility. No validated endpoint or strength formula is implemented by this roadmap.

### 4. Define basket movement rules

Configure each basket's reference instrument, USD sensitivity, own momentum influence, volatility scale, step limits and reference-age limits. Relationships are influences, not universal inverse correlations. Gold, equities and crypto retain their own drivers. Publish internal parameters in versioned configuration so tests are reproducible.

### 5. Upgrade Neutral movement

Fresh reference data influences direction and step size. Decay that influence with age toward existing Neutral behaviour. Bound step size, drift and price range. Preserve explicit Drive Up, Drive Down and Consolidate controls in configured mode. Live selection disables manual controls as previously agreed; it must not accidentally change configured background movement.

### 6. Reconcile new references

On a fresh provider observation, revise the reference and reconcile the basket price gradually with bounded steps. Do not silently reset execution prices or rewrite history. Ordinary reconciled price steps may legitimately trigger SL/TP; smoothing does not guarantee exits are avoided. Actual live quotes retain their observed jumps and are never smoothed into invented live quotes.

### 7. Use one authoritative basket CMP

Charts, calculators, execution, P/L, closes and automatic exits read the same persisted basket price and version. Persist each movement into basket-owned chart history. Reference feeds and basket history remain separate. Ensure consistent units, precision and marketplace routing. Do not fabricate provider OHLC or retrospectively execute exits from unobserved candle extremes.

### 8. Harden scheduled processing and AJAX

Use one scheduled engine with concurrency protection and transactional updates. Prevent duplicate fills and payments. Log task outcomes and failures. Track local request budgets, provider limitations and feed freshness; page views must not call providers independently. AJAX reads persisted state and updates positions, orders and balances. Never retry uncertain trade submissions automatically.

### 9. Verify and release incrementally

Use isolated fixtures for entries, long/short partial and full closes, SL/TP, expiry, fees, balances, receipts, AJAX updates, restart recovery, duplicate processing and exhausted feeds. Test new reference reconciliation and stale-data decay. Deliver backed-up PowerShell installers with rollback and explicit scope. Local acceptance is distinct from production verification. Final fresh-install and updater packages follow verified local behaviour.

## First delivery

A persistent reference store and read-only provider inspection. Verify access and data before enabling strength-driven Neutral movement. A roadmap file is not activation or an engine patch.

## Current evidence and outstanding work

- Manual long/short and partial/full closes have been exercised locally.
- Automatic SL has been confirmed by the user after stale scheduler-lock recovery.
- A short position was recorded with take_profit, but the user reports manually closing it. Automatic TP still needs an unambiguous isolated test.
- Shared live feed currently covers EUR/USD and Gold, not every instrument.
- Feed hardening reduced ordinary updates from four requests to two; history refresh is about every sixteen minutes. The local daily budget remains enforced and does not represent provider-wide usage.
- Lifecycle overlap expiry was reduced to fifteen minutes. This bounds abandoned-lock duration; it does not solve every hung-process or concurrency case.
- Investment movement, scheduled processing, expiry and subscription/redemption still need complete verification.
- Full installation and live updater releases remain pending.

## Future Next.js product

A Next.js interface can provide a polished product experience while Laravel continues to own validated trading behaviour initially. Replace Blade pages incrementally through authenticated backend APIs; keep ledger, settlement, pricing and workers authoritative on the server.

A full backend rewrite is a separate project. First document API contracts, authorization, price versions, receipts, idempotency and transaction guarantees. Port behaviour only with equivalent acceptance tests. Next.js itself does not provide an always-running price worker; worker hosting, database concurrency and job execution require deliberate design.

Prioritize engine correctness and a consistent UI before framework migration. Preserve this roadmap in the repository and update its evidence and status with each delivery.
