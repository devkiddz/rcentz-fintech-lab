# Rcentz Fintech Lab — Investments, VIP & Giveaways Roadmap

> Canonical implementation checklist for the next product phase after trading/admin hardening.
>
> **Status convention**
> - [ ] Not started
> - [~] In progress
> - [x] Complete
>
> Mark each item complete only after implementation, technical verification, and browser acceptance.

---

## 1. Private Investment Market

### Core philosophy

The investment system is an **admin-governed private investment market with system-authoritative price history**.

Admins may create, manage, pause, resume, adjust, add assets to, deduct from, and otherwise govern private investment instruments. However, customer returns must not be manually fabricated per user.

The source of truth is the investment instrument itself:

```text
Underlying Assets
      ↓
Investment Events
      ↓
Investment Valuation / Price Authority
      ↓
Investment Price History
      ↓
Chart
      ↓
Holdings / Current Value / P&L / Return
```

An investment may be private and unavailable on public exchanges while still behaving like a living asset with a rising/falling chart.

### Canonical terminology

- **InvestmentInstrument** — the investable product users subscribe to.
- **InvestmentAsset** — a real/private asset backing an instrument.
- **InvestmentEvent** — an event that changes economics or valuation.
- **InvestmentPrice** — the current authoritative instrument price.
- **InvestmentPriceHistory** — historical price points used by charts.
- **InvestmentHolding** — a user's stake/units in an instrument.
- **InvestmentTransaction** — subscription, redemption, distribution, deduction, etc.
- **InvestmentValuationEngine** — converts approved economic events/valuation inputs into authoritative value.
- **InvestmentGovernance** — authority that decides whether an event is approved.

### Critical rule

**Investment Event ≠ Investment Price.**

An event explains *why* something changed.

Example:

```text
Event: Major infrastructure repair
Effect: negative valuation adjustment
Price: $28.50 → $27.82
```

The price history becomes the source of truth, and all holdings react to that same price.

---

## 1.1 Customer Investment Navigation

From the supplied reference UI:

- [ ] **Investments** parent navigation
- [ ] **All Plans**
- [ ] **Stock Market**
- [ ] **Cryptocurrency**
- [ ] **Real Estate**
- [ ] **My Portfolio**
- [ ] **Performance History**

### Customer surfaces

- [ ] Investment discovery/listing page
- [ ] Category filtering
- [ ] Instrument details page
- [ ] Current price / valuation
- [ ] Historical chart
- [ ] Price change and percentage change
- [ ] Risk level
- [ ] Minimum investment
- [ ] Maximum investment where applicable
- [ ] Available units / supply where applicable
- [ ] Lock period / maturity rules where applicable
- [ ] Underlying asset summary
- [ ] Investment event/activity timeline
- [ ] Subscribe / invest flow
- [ ] Redeem / exit flow
- [ ] My holdings
- [ ] Cost basis
- [ ] Current value
- [ ] Unrealized P/L
- [ ] Realized P/L where applicable
- [ ] Return percentage
- [ ] Portfolio allocation
- [ ] Performance history chart

---

## 1.2 Investment Instrument Administration

- [ ] Create investment instrument
- [ ] Edit investment instrument
- [ ] Archive investment instrument
- [ ] Activate / pause / resume instrument
- [ ] Set opening price
- [ ] Configure unit supply
- [ ] Configure minimum investment
- [ ] Configure maximum investment
- [ ] Configure lock period
- [ ] Configure maturity rules
- [ ] Configure risk classification
- [ ] Configure fees
- [ ] Configure category
- [ ] Configure visibility
- [ ] Configure investment status
- [ ] Add/remove underlying assets
- [ ] Record asset acquisition
- [ ] Record asset disposal
- [ ] Record changes in asset composition

---

## 1.3 Underlying Assets

An instrument may contain one or more privately managed assets.

Example:

```text
Warri Residential Growth Fund
├── Property A
├── Property B
└── Commercial Annex
```

- [ ] Investment asset model
- [ ] Asset type
- [ ] Asset name/title
- [ ] Asset description
- [ ] Acquisition value
- [ ] Current valuation
- [ ] Ownership/stake percentage
- [ ] Status
- [ ] Effective date
- [ ] Supporting notes
- [ ] Asset history
- [ ] Add new asset upon approved agreement
- [ ] Remove/dispose asset with recorded reason
- [ ] Reflect asset composition changes in valuation history

---

## 1.4 Investment Events

Admins can govern the economic reality of private assets.

Supported event classes should include:

- [ ] Positive valuation adjustment
- [ ] Negative valuation adjustment
- [ ] Percentage adjustment
- [ ] Fixed-value adjustment
- [ ] Inflation adjustment
- [ ] Maintenance expense
- [ ] Infrastructure repair
- [ ] Amenities upgrade
- [ ] Capital improvement
- [ ] Insurance expense
- [ ] Property tax / levy
- [ ] Operating expense
- [ ] Rental / operating income
- [ ] Capital appreciation
- [ ] Occupancy improvement
- [ ] Independent valuation revision
- [ ] Asset addition
- [ ] Asset removal
- [ ] Supply/unit adjustment
- [ ] Distribution/dividend event
- [ ] Administrative price correction with reason
- [ ] Pause event
- [ ] Resume event

Every event should record:

- [ ] Instrument
- [ ] Event type
- [ ] Direction
- [ ] Adjustment type
- [ ] Adjustment value
- [ ] Previous value/price
- [ ] New value/price
- [ ] Reason
- [ ] Effective date/time
- [ ] Created by
- [ ] Approval/governance state
- [ ] Audit timestamps

---

## 1.5 Investment Price Authority & Chart

The chart is the visible source of truth for the instrument.

- [ ] Authoritative current instrument price
- [ ] Historical price/tick storage
- [ ] Chart data endpoint
- [ ] Intraday/daily/weekly/monthly aggregation where useful
- [ ] Price open/high/low/close representation where appropriate
- [ ] Percentage change
- [ ] Previous close/reference value
- [ ] Event annotations on chart
- [ ] Paused/frozen market state
- [ ] Resume continuity
- [ ] Manual authoritative adjustment
- [ ] Automatic valuation-derived adjustment
- [ ] Audit trail for all price changes

---

## 1.6 Holdings & Valuation

Example:

```text
Entry price:   $25.00
Units:         100
Cost basis:    $2,500

Current price: $29.16
Current value: $2,916
P/L:           +$416
Return:        +16.64%
```

- [ ] Unit-based holdings
- [ ] Entry price
- [ ] Cost basis
- [ ] Current value derived from authoritative price
- [ ] Unrealized P/L
- [ ] Realized P/L
- [ ] Return percentage
- [ ] Partial redemption
- [ ] Full redemption
- [ ] Holding status
- [ ] Position timestamps
- [ ] Marketplace/instrument linkage invariants
- [ ] Portfolio-level aggregation

---

## 1.7 Distributions, Deductions & Cash Effects

Not every event should change price.

Some events may instead affect holder cash/ledger balances.

- [ ] Cash distribution
- [ ] Rental income distribution
- [ ] Dividend-style distribution
- [ ] Expense deduction
- [ ] Management fee
- [ ] Performance fee where applicable
- [ ] Tax/levy deduction where applicable
- [ ] Per-unit distribution calculation
- [ ] Holder transaction ledger
- [ ] Wallet integration
- [ ] Distribution history

---

## 1.8 Investment Categories

### Stock Market

- [ ] Stock-market investment category
- [ ] Portfolio/basket-backed instruments
- [ ] Allocation percentages
- [ ] Internal/external market price provider integration
- [ ] Basket valuation
- [ ] Rebalancing events

### Cryptocurrency

- [ ] Cryptocurrency investment category
- [ ] Crypto-backed instrument support
- [ ] External or controlled price provider
- [ ] Basket valuation
- [ ] Allocation rules
- [ ] Crypto-specific risk metadata

### Real Estate

- [ ] Real-estate investment category
- [ ] Property/stake-backed instruments
- [ ] Private valuation events
- [ ] Repair/maintenance events
- [ ] Infrastructure/amenity upgrade events
- [ ] Rental income events
- [ ] Inflation adjustments
- [ ] Appraisal/revaluation events
- [ ] Asset acquisition/disposal
- [ ] Property-level audit history

---

## 1.9 Governance Model

### PHP reference implementation

For the current Laravel/PHP app:

```text
Admin
  ↓
Investment Event
  ↓
Investment Valuation / Price Authority
  ↓
Price History
  ↓
Holdings / P&L / Chart
```

- [ ] Admin approval authority
- [ ] Admin action audit trail
- [ ] Reason required for sensitive adjustments
- [ ] Confirmation for destructive/high-impact actions

### Future Rcentz production model

For the future Next.js / React Native real-estate staking platform:

```text
Proposal
   ↓
Admins
   ↓
Judges / Independent Review
   ↓
Top-tier Government Authority
   ↓
Quorum / Approval Policy
   ↓
Approved Investment Event
   ↓
Investment Valuation Engine
   ↓
Price History
   ↓
Stakeholder Holdings
```

- [ ] Proposal system
- [ ] Multi-role approvals
- [ ] Judges/review authority
- [ ] Top-tier government authority
- [ ] Quorum rules
- [ ] Decision status
- [ ] Rejection flow
- [ ] Revision flow
- [ ] Execution authorization
- [ ] Immutable governance audit trail

The future governance layer must sit **above** the investment engine. The downstream economic engine should consume only approved events.

---

# 2. VIP Cards / Membership

From the supplied reference UI:

- [ ] **VIP Cards** parent navigation
- [ ] **Browse Cards**
- [ ] **My Memberships**

### Core philosophy

VIP should be a **Membership + Entitlement Engine**, not just a decorative card.

```text
VIP Card / Tier
      ↓
Membership
      ↓
Validity / State
      ↓
Entitlements
      ↓
Feature Access / Limits / Privileges
```

### VIP administration

- [ ] Create VIP tier/card
- [ ] Edit VIP tier/card
- [ ] Activate/deactivate tier
- [ ] Set price
- [ ] Set duration
- [ ] Set renewal rules
- [ ] Set visual card identity
- [ ] Set tier rank
- [ ] Configure benefits
- [ ] Configure feature entitlements
- [ ] Configure trading/investment privileges where applicable
- [ ] Configure limits
- [ ] Configure priority support
- [ ] Configure special access
- [ ] Membership audit history

### Customer VIP experience

- [ ] Browse VIP cards
- [ ] VIP card details
- [ ] Compare tiers
- [ ] Purchase/subscribe
- [ ] My memberships
- [ ] Membership status
- [ ] Start date
- [ ] Expiry date
- [ ] Renewal
- [ ] Cancellation rules
- [ ] Active entitlements
- [ ] Expired membership history
- [ ] VIP badge/card presentation

---

# 3. Giveaways

From the supplied reference UI:

- [ ] **Giveaways** parent navigation
- [ ] **Browse Giveaways**
- [ ] **My Entries**

### Core philosophy

Giveaways should be a **Campaign + Entry Ledger + Draw Engine**.

```text
Giveaway Campaign
      ↓
Eligibility Rules
      ↓
Entries
      ↓
Entry Ledger
      ↓
Draw
      ↓
Winner Record
```

### Giveaway administration

- [ ] Create campaign
- [ ] Edit campaign
- [ ] Pause/resume campaign
- [ ] Open/close entries
- [ ] Set start date
- [ ] Set end date
- [ ] Set prize
- [ ] Set eligibility rules
- [ ] Set entry cost where applicable
- [ ] Set maximum entries
- [ ] Configure VIP-only eligibility where applicable
- [ ] Configure investment/trading-linked eligibility where applicable
- [ ] Draw winner
- [ ] Record draw authority
- [ ] Record winner
- [ ] Record prize fulfillment
- [ ] Campaign audit history

### Customer giveaway experience

- [ ] Browse giveaways
- [ ] Giveaway details
- [ ] Eligibility state
- [ ] Enter giveaway
- [ ] My entries
- [ ] Entry count
- [ ] Entry timestamps
- [ ] Draw status
- [ ] Winner result
- [ ] Past giveaway history

---

# 4. Existing Reference Navigation Visible in Screenshots

These are not all new modules, but they are part of the supplied navigation reference and should remain aligned with the final information architecture.

### Dashboard & Account

- [ ] Dashboard
- [ ] Account Statement

### Car Inventory

- [ ] Car Inventory parent navigation
- [ ] Browse Cars
- [ ] My Orders

### Trading

- [x] Trading parent navigation
- [x] Market / Live Markets customer surface
- [x] Copy Trading foundation
- [x] AI Trading Bots foundation

> Trading is already implemented substantially in the current PHP reference app. Future work here is hardening rather than rebuilding the navigation concept.

### Wallet & Finance

- [ ] Keep Wallet & Finance grouped in customer navigation
- [ ] Confirm final wallet sub-routes during customer-navigation hardening

---

# 5. Implementation Order

## Phase A — Current Platform Hardening

- [x] Controlled/internal market continuity when active source changes
- [x] Marketplace-safe portfolio totals in emails
- [x] Prevent synthetic/internal instruments from inappropriate external quote jobs
- [x] Marketplace-aware admin SELL position selection
- [x] Audit global/current price usage
- [x] Marketplace consistency across admin/customer transaction history
- [x] Remove obsolete compatibility routes after caller audit
- [x] Final acceptance test
- [x] Git checkpoint

## Phase B — Private Investment Market

- [ ] Domain models/schema
- [ ] Investment Instrument admin CRUD
- [ ] Underlying Asset management
- [ ] Investment Event ledger
- [ ] Investment Valuation Engine
- [ ] Investment Price Authority
- [ ] Investment Price History
- [ ] Investment chart
- [ ] Holdings
- [ ] Transactions
- [ ] Portfolio
- [ ] Performance History
- [ ] Stock Market category
- [ ] Cryptocurrency category
- [ ] Real Estate category
- [ ] Distributions/deductions
- [ ] Admin pause/resume
- [ ] Browser acceptance
- [ ] Git checkpoint

## Phase C — VIP Membership

- [ ] VIP tier/card admin
- [ ] Membership lifecycle
- [ ] Entitlement engine
- [ ] Browse Cards
- [ ] My Memberships
- [ ] Feature gating
- [ ] Browser acceptance
- [ ] Git checkpoint

## Phase D — Giveaways

- [ ] Campaign admin
- [ ] Eligibility rules
- [ ] Entry ledger
- [ ] Browse Giveaways
- [ ] My Entries
- [ ] Draw engine
- [ ] Winner records
- [ ] Prize fulfillment state
- [ ] Browser acceptance
- [ ] Git checkpoint

---

# 6. Reference-App Purpose

This Laravel/PHP application is the **behavioral and architectural reference implementation**.

The goal is not merely to finish the PHP product. It is to prove:

- the domain rules,
- state transitions,
- valuation mechanics,
- administrative controls,
- audit history,
- customer UX,
- and edge cases.

The later Rcentz Next.js / React Native implementation should reuse the proven domain philosophy while introducing stronger architecture, richer UX, and multi-party governance.

```text
PHP Reference
      ↓
Proven Behavior / Domain Contract
      ↓
Rcentz Next.js Backend + Web
      ↓
React Native Client
      ↓
Multi-party Governance
```

---

## Definition of Complete

A checkbox should only become `[x]` when:

1. the data/model contract exists,
2. the operational logic is implemented,
3. admin controls work,
4. customer/read surfaces work,
5. validation/invariants are enforced,
6. technical verification passes,
7. browser acceptance passes,
8. the milestone is checkpointed to Git.
