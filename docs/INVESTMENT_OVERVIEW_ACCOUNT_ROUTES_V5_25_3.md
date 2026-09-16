# V5.25.3 — Investment Overview + Account Routes

## Route separation

Discovery/market:
- /investments
- /investments/stocks
- /investments/crypto
- /investments/real-estate
- /investments/bonds

Customer-owned state:
- /account/investments
- /account/investments/portfolio
- /account/investments/performance

Old /investments/portfolio and /investments/performance remain compatibility redirects.

## Rich overview

The investment overview now exposes:
- asset-class cards,
- platform inventory summary,
- featured instruments,
- market movement snapshot,
- richer product cards,
- direct link to the customer's investment account.

## Stock expansion

Four stock-backed private instruments are seeded:
- Tesla Equity Growth Plan
- Apple Quality Equity Plan
- NVIDIA AI Growth Plan
- Microsoft Cloud Growth Plan

These are NOT public stock quotes. Their unit prices and OHLC history live entirely in PrivateInvestmentInstrument / PrivateInvestmentPrice.

The public stock market can later be used only as a valuation input if we explicitly build that policy.
