# Next.js Rebuild Handoff — Domain Checklist

Before rebuilding each domain, extract:

1. Models / persisted state
2. Invariants
3. Validation rules
4. State transitions
5. Financial side effects
6. Audit/activity records
7. Notifications
8. Customer DTO
9. Admin DTO
10. Scheduled/background behavior

Recommended rebuild sequence:

1. Identity/Auth/KYC
2. Wallet + Financial Activity
3. Market Data + Stock Catalog
4. Stock Trading + Portfolio
5. Investments
6. Copy Trading
7. AI Trading Bots
8. Notifications + Support
9. Admin Governance
10. Mobile/API layer

Do not carry forward:
- Laravel-specific route/controller coupling
- Blade calculations
- duplicate legacy routes
- presentation-only business logic
- ambiguous "win rate" semantics for open positions

Carry forward:
- immutable money movement references
- balance reservation rules
- lock/transaction boundaries
- provider approval governance
- subscription expiry governance
- market-session truth
- persisted OHLC
- realized/open P&L separation
