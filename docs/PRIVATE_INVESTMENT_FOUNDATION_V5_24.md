# V5.24 — Private Investment Market Foundation

This milestone creates a new private-investment domain instead of forcing the legacy InvestmentPlan/NAV system to carry the new market architecture.

## New domain

- PrivateInvestmentInstrument
- PrivateInvestmentAsset
- PrivateInvestmentEvent
- PrivateInvestmentPrice
- PrivateInvestmentHolding
- PrivateInvestmentTransaction

The new tables are prefixed with `private_investment_` so the existing legacy investment feature remains readable while the new engine is developed safely.

## Universal shell principle

Each feature owns its own economic engine and source of truth, but shared presentation infrastructure can consume a universal OHLC series:

`time/open/high/low/close`

The investment price table therefore stores proper OHLC rows from the first milestone so the existing chart/candle renderer can be adapted without coupling Investments to ControlledMarketEngine.

## Demo data

PrivateInvestmentDemoSeeder creates four realistic demo instruments across:

- Real Estate
- Stock Market
- Cryptocurrency

Each instrument receives underlying assets, economic events, and 45 days of deterministic OHLC price history.

The seed is deterministic and rerunnable. It does not create fake customer holdings or wallet transactions; those will only be created after the subscription/redemption engine exists.

## UI direction note

The customer/admin shell should move toward a cleaner Tesla-like visual balance: more white/light surfaces, restrained dark panels, and red used as a deliberate accent rather than making the entire dashboard dark.

No large UI redesign is included in V5.24; the direction is recorded for the upcoming investment customer/admin surfaces.
