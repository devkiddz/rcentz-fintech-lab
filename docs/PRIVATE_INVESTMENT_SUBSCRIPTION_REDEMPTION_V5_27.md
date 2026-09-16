# V5.27 — Private Investment Subscription & Redemption

The Private Investment market now has a real economic execution engine.

## Customer subscription

Amount → authoritative investment price → units → wallet debit → holding → transaction → available supply.

## Customer redemption

Units → authoritative investment price → realized P/L → wallet credit → holding reduction/closure → transaction → returned supply.

## Lock period

A holding now has locked_until. A new top-up extends the aggregate holding lock to the later applicable date. This is deliberate for the current lab model; lot-level locks can be introduced later if the production product needs tranche-specific redemption.

## Admin authority

Admin can perform explicit customer-targeted subscription and redemption from the instrument control page. Admin does not use customer-owned account routes for financial mutations.

## Source-of-truth boundary

No Trading price provider, Stock current_price or external quote is used. Investment executions always use PrivateInvestmentInstrument.current_price.
