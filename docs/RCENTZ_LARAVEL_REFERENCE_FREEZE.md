# Rcentz Fintech Lab — Laravel Reference Freeze

## Purpose

This Laravel implementation is the behavior/reference system for the deliberate Rcentz Next.js rebuild.

The reference establishes:
- wallet and money-movement truth
- KYC-gated financial actions
- stock trading and holdings
- investment holdings
- Copy Trading provider / strategy / relationship / execution flows
- AI Trading Bot catalog / subscription / runtime / execution flows
- live Finnhub market quotes
- persisted 15-minute OHLC candle truth
- market news and market context
- customer/admin auditability
- TradingView Lightweight Charts presentation

## Rebuild rule

Do not port pages one-for-one.

Port domain contracts:

    stored state
        -> domain rule
        -> transaction / execution
        -> audit record
        -> derived financial truth
        -> presentation

## Next.js target

Suggested domains:
- server/finance
- server/wallet
- server/trading
- server/market
- server/copy-trading
- server/ai-bots
- server/security
- server/audit

Prisma should own persisted contracts.
Server/domain services should own financial rules.
React components should consume DTOs and never calculate ledger truth themselves.

## Frozen cleanup milestones

- V3.0 Market Session + persisted OHLC
- V3.1 Bot Subscription Lifecycle + performance semantics
- V3.2 Copy Trading V2 + structural cleanup

After V3.2 regression is green, avoid broad Laravel architecture expansion unless a new feature is needed as a reference before the Next.js rebuild.
