# V5.25.2 — Investment Taxonomy + Admin Audit Access

## Investment information architecture

Canonical customer routes:

- /investments
- /investments/stocks
- /investments/crypto
- /investments/real-estate
- /investments/bonds
- /investments/portfolio
- /investments/performance

Stocks under Trading remain actual trading-market exposure. Stocks under Investments are privately priced investment instruments/baskets.

## Pricing authority

All Investment listings, detail screens and performance surfaces read from the Private Investment domain:

PrivateInvestmentInstrument -> PrivateInvestmentPrice history.

No Investment listing reads Finnhub, Yahoo, MarketPriceRouter or Stock::current_price.

Public market data may later inform an underlying asset valuation event, but the customer-facing Investment unit price remains system-authoritative.

## Admin customer-surface access

The old `block.admin` policy blocked admins from all customer pages. It is replaced by `customer.access`:

- Admin GET/HEAD/OPTIONS requests are allowed for audit/preview.
- Customer POST/PATCH/PUT/DELETE financial actions remain blocked for admins.
- Admin edits and financial operations must go through explicit /admin control-plane routes.

This keeps admins able to see what customers see without creating two mutation paths for the same financial state.
