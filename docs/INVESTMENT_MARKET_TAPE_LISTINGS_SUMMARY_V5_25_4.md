# V5.25.4 — Investment Market Tape + Listings Summary

This milestone enriches the customer investment overview without changing any financial engine.

## Investment listings tape

A classic scrolling tape now sits directly below the investment hero.

It displays:
- instrument symbol,
- asset class,
- authoritative private investment price,
- current movement percentage.

The tape duplicates its visual sequence only for seamless animation; it does not create duplicate database records.

## Market movement rail

The existing Largest Current Moves card now also includes:
- total active listings,
- Stocks count,
- Crypto count,
- Real Estate count,
- Bonds count,
- aggregate underlying-asset valuation.

## Price source

Every value rendered here still comes from PrivateInvestmentInstrument / PrivateInvestmentPrice.

No direct live stock or crypto quote is introduced by this UI milestone.
