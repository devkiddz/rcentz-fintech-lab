# Forex FX1 — Market Foundation

## Purpose

FX1 adds Forex as a first-class market domain without pretending currency pairs are stocks and without changing the existing Signal authority yet.

## Data model

- `ForexPair` — currency-pair identity and current market metadata.
- `ForexCandle` — real persisted Forex OHLC history.
- `MarketInstrument` — generic registry that can represent both existing Stocks and Forex pairs.

Existing Stocks are backfilled into `market_instruments`. Forex pairs receive their own `forex_pairs` records and generic instrument records.

## Initial pair universe

Featured majors:

- EUR/USD
- GBP/USD
- USD/JPY
- USD/CHF
- AUD/USD
- USD/CAD
- NZD/USD

Additional crosses:

- EUR/GBP
- EUR/JPY
- GBP/JPY

## Market data rules

Current rates may be derived from the existing persisted `currency_rates` table. This is useful for pair discovery and display only.

Signal-grade historical analysis must use real OHLC history. `forex:refresh-history` retrieves and persists daily FX OHLC data through the configured Alpha Vantage key. The system does not synthesize historical Forex candles.

## Sessions

Forex market status is modeled independently from the US stock session. Session awareness includes Sydney, Tokyo, London, New York and London/New York overlap detection with timezone/DST conversion.

## Commands

```text
php artisan db:seed --class=ForexPairSeeder
php artisan forex:inspect
php artisan forex:refresh-history EURUSD
php artisan forex:refresh-history --all
```

## Boundary

FX1 does **not** alter `signals.stock_id` or generate Forex Signals yet. It establishes the correct market identity/data boundary first. FX2 will generalize Signal market context from Stock-only authority to `MarketInstrument` and route Forex instruments through the Forex context provider.

## Acceptance checkpoint — 2026-09-18

FX1 migration and seeding completed successfully. The generic registry currently contains 10 stock instruments and 10 Forex instruments. The initial Forex universe is EUR/USD, GBP/USD, USD/JPY, USD/CHF, AUD/USD, USD/CAD, NZD/USD, EUR/GBP, EUR/JPY and GBP/JPY.

EUR/USD real-history ingestion was exercised with `forex:refresh-history EURUSD --output=compact` and persisted 100 daily OHLC rows through 2026-09-17. Its latest persisted close became 1.14740 and `forex:inspect` reports the pair as `READY`. The remaining nine pairs are seeded and intentionally remain `NEEDS_HISTORY` until refreshed.

FX2 must generalize Signal market context around `MarketInstrument` while preserving existing Stock behavior. No FX2 writes have been accepted yet.
