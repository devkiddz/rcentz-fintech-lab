# Rcentz Trading Engines

V5.9 separates the project's trading responsibilities without changing the canonical market contract behaviour.

## 1. Market Contract Engine — canonical

**Classes:** `StockTradeExecutor` + `TradePositionService`

This is the current source of truth for new user, administrator, bot and copy-trading contracts. It creates financial executions, updates wallets/holdings, opens living positions, applies stop-loss/take-profit/time rules and calculates realized P/L from EMP and exit price.

Its execution quote is the persisted `stocks.current_price`. That value is refreshed by the market-data pipeline (Finnhub through `UpdateStockQuotesJob`). The execution engine does not call the external quote API directly during a BUY/SELL request.

## 2. Legacy Trade Plan Engine — isolated compatibility

**Class:** `App\Services\Legacy\LegacyStockExecutionEngine`

This preserves the old `StockTradePlan` automatic execution path. It is intentionally separated from the modern position engine because it predates living `TradePosition` contracts and historically executes against the stored `stocks.current_price` value.

`App\Services\StockExecutionService` remains as a compatibility wrapper so unknown legacy references do not break, but new features must not depend on it.

## 3. Manual Performance Engine — presentation only

**Class:** `App\Services\Simulation\ManualPerformanceEngine`

This owns the existing manual P/L / return override used by bot-product and copy-strategy presentation. It never creates executions and never changes a wallet, holding or position. Actual calculated values are retained separately in `actual_profit_loss` and `actual_return_percent`.

## 4. Scenario Outcome Engine — isolated simulator

**Class:** `App\Services\Simulation\OutcomeScenarioEngine`

This is a pure calculator for intentional **WIN / LOSS / FLAT** scenarios. It supports long and short direction mathematically, but does not persist anything and cannot touch financial records. It is the safe foundation for a future demo/testing workstation.

Examples:

```bash
php artisan trading:scenario win --entry=100 --qty=10 --move=5
php artisan trading:scenario loss --entry=100 --qty=10 --move=3
php artisan trading:scenario flat --entry=100 --qty=10
```

## Engine inspection

```bash
php artisan trading:engines
```

## Modernization rule

New market trading must flow through the Market Contract Engine. Legacy `StockTradePlan` processing remains available only for historical compatibility. Presentation overrides and scenario outcomes remain financially isolated.
