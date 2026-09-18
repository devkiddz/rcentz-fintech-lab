# Membership & Subscription Model — Architecture Milestone

## Status

**Milestone type:** Architecture / Product Direction
**Implementation status:** Planned — not yet sealed as production behavior
**Build dependency:** Signals should be designed and implemented before the commercial Membership rules are finalized.

## Core Decision

The platform will use **Membership Plans as the subscription model**.

Individual platform features should not normally be sold as isolated products. Instead, a customer subscribes to a Membership Plan and that plan determines:

- which services/features the customer can access;
- how often the customer can use them;
- how long an allowed action/session may remain active;
- how many simultaneous uses are allowed;
- which markets/instruments the customer may access;
- which support privileges are available;
- which future capabilities become available as new domains are added.

Membership is therefore the platform's **commercial access authority**, not a feature engine itself.

## Domain Boundary

Feature domains remain independent:

- Bot Trader
- Copy Trader
- Trade For Me
- Investment Packages
- Wallet Connect
- Signals
- Support

Membership does not own the business logic of these domains. Each feature engine remains responsible for its own lifecycle and rules. Membership only answers whether a customer is entitled to perform a protected action and, where applicable, what limits apply.

```text
Feature Engine
    |
    | asks
    v
Membership Access Authority
    |
    +-- allowed?
    +-- usage limit?
    +-- duration limit?
    +-- concurrency limit?
    +-- instrument/market access?
    +-- support level?
```

## Customer Experience

Customers should not see internal entitlement keys or technical slugs.

They should see a human-readable Membership Overview showing:

- current membership plan;
- membership status;
- start date;
- expiry/renewal date;
- included privileges;
- usage allowances where useful;
- renewal/upgrade actions when implemented.

Example customer-facing presentation:

```text
Membership
└── Overview
    ├── Current Plan
    ├── Status
    ├── Start / Expiry
    └── Included Privileges
        ├── Daily trading signals
        ├── Bot Trader allowance
        ├── Copy Trading allowance
        ├── Investment access
        ├── Wallet Connect allowance
        └── Support level
```

Internal entitlement identifiers remain backend implementation details.

## Access Dimensions

Membership privileges may control more than simple yes/no access.

### Availability

Examples:

```text
bot_trader.access
copy_trader.access
trade_for_me.access
signals.access
investments.access
wallet_connect.access
support.access
```

### Usage limits

Examples:

```text
signals.daily_limit
bot_trader.activations_per_week
copy_trader.max_relationships
trade_for_me.max_active_mandates
wallet_connect.max_wallets
```

### Duration limits

Examples:

```text
bot_trader.max_runtime_minutes
copy_trader.max_duration_minutes
```

### Market and instrument access

Membership may determine which instruments a customer can trade, copy, receive signals for, or invest in.

Possible control dimensions include:

- asset class;
- market;
- instrument allowlist;
- instrument group/tier;
- number of simultaneously accessible instruments;
- whether an instrument is available for automation;
- whether an instrument is available for signals;
- whether an investment product is available to the plan.

This restriction controls **access**, not market pricing, execution quality, or signal honesty.

The platform should not intentionally provide lower-quality analysis to lower membership tiers. Plans may differ by quantity, breadth, frequency, automation allowance, analytics depth, and market access.

## Example Membership Policy

The following is illustrative only and is not the final commercial plan matrix.

```text
Starter

Signals
- limited number of signals per day

Bot Trader
- limited activations per week
- limited runtime

Copy Trader
- limited active relationships
- limited session duration

Trade For Me
- unavailable or highly restricted

Investments
- selected investment packages only

Wallet Connect
- limited number of linked wallets

Markets / Instruments
- selected starter instruments

Support
- support tied to active services
- standard priority
```

Higher plans may progressively unlock more signals, broader instrument access, additional markets, more bot activations, longer automation runtime, more simultaneous copy relationships, Trade For Me access, broader investment package access, additional connected wallets, advanced analytics, and broader or higher-priority support.

Exact limits must be determined after the relevant feature engine exists and its real capabilities are known.

## Instrument Access Principle

Instrument restriction is a first-class Membership capability.

A plan may control access by category rather than hardcoding access into feature controllers.

```text
Customer
   |
Membership
   |
   +-- permitted markets
   +-- permitted asset classes
   +-- permitted instruments
   +-- automation permissions
   +-- signal permissions
   +-- investment-product permissions
```

Feature engines should query the Membership authority before exposing or executing protected instruments.

This allows the same instrument to be visible to one plan, unavailable to another, signal-enabled for one plan, bot-enabled for another, or investment-only for another.

The actual commercial matrix will be decided after Signals and the remaining capability inventory are complete.

## Signals Dependency

Signals should be implemented before Membership is finalized.

Reason: Membership rules should be based on real capabilities, not guessed limits.

The Signals domain should first establish its own model, including:

- signal identity;
- market/instrument;
- direction/bias;
- entry;
- stop loss;
- take-profit targets;
- publication time;
- expiry;
- status;
- performance/result;
- manual or automated origin;
- admin publishing flow;
- customer consumption/history;
- notifications.

After Signals is functional, Membership can accurately define signals per day, accessible instruments/markets, signal categories, history depth, notification privileges, analytics privileges, and any future signal-specific limits.

## Support Dependency

Support remains its own domain.

Membership may later control whether support is available, whether support requires an active service, ticket scope, support priority, response/service level, and number of open tickets where appropriate.

Membership should not contain ticket lifecycle logic.

## Payment Boundary

Membership activation must ultimately be connected to the platform's financial authority.

The customer clicking a Subscribe/Buy button must never itself create an active paid membership.

```text
Customer selects Membership Plan
        |
        v
Purchase / Payment Request
        |
        v
Financial Authority confirms successful payment
        |
        v
Membership activates
        |
        v
Entitlements and limits become effective
```

UI success must never be treated as payment success.

Manual/admin-issued memberships may coexist with paid memberships but must remain distinguishable for audit purposes.

## Planned Build Order

Current direction:

```text
1. Signals Engine
2. Support capability definition / foundation
3. Full platform capability inventory
4. Membership Plan policy and entitlement matrix
5. Membership purchase / renewal / upgrade authority
6. Feature-level Membership enforcement
7. Bonuses integration where its final domain boundary requires Membership
```

Bonuses should be planned alongside Membership and Signals before its final implementation order is sealed.

## Product / Brand Boundary

This PHP/Laravel project is currently a development/reference implementation and does not automatically define the technology stack or product branding of the future production platform.

A future production version may be implemented with Next.js and the broader JavaScript/TypeScript stack.

The architecture in this document is framework-independent: Membership policy, feature authority, entitlements, limits, instrument access, and payment confirmation can be implemented in Laravel/PHP, Next.js/TypeScript, or another backend without changing the product model.

## Architecture Principle

> **Membership defines access to the ecosystem; feature engines define how their services actually work.**

> **Reuse infrastructure and access contracts — do not merge independent business domains into Membership.**
