# Signals S5.5 — Timing Intelligence

## Purpose

Add customer-facing timing context to an already-authorized Signal without changing the Signal contract, lifecycle, distribution, wallet state, or trading execution.

## Authority

`Signal` remains the setup contract. `SignalDelivery` remains customer access authority. Timing intelligence is computed live from the current market snapshot and never becomes ownership or execution authority.

## Timing model

The customer Signal detail surface now exposes:

- current entry timing classification;
- optimal entry zone;
- moderate tolerance band;
- dangerous price area;
- best Signal timeframe;
- preferred market session;
- current market-session status and clock.

For Live stocks, the preferred session is the regular U.S. equity session (`09:30–16:00 ET`). Pre-market, after-hours, weekends, and market holidays are treated as elevated execution-risk timing.

The optimal range is the immutable Signal entry zone. The moderate tolerance extends 25% of the original stop distance below/against the entry zone and 25% of the first-target distance beyond/toward reward. The calculation is mirrored for SELL Signals. Price outside that band is classified as dangerous for a fresh entry because it is materially removed from the original risk/reward contract.

## Boundaries

Timing intelligence:

- does not change Signal status;
- does not revise entries, stops, or targets;
- does not create a SignalRevision or SignalEvent;
- does not publish or distribute;
- does not place a trade;
- does not state or imply a win probability.

The classification is execution context only and can change as market price/session state changes.

## Acceptance checkpoint — 2026-09-18

Timing intelligence is installed on the customer Signal detail surface. It derives optimal/moderate/dangerous entry context, best timeframe and live session context from the current market snapshot without mutating the Signal contract. For Forex expansion, the same presentation concept will use Forex-specific session authority rather than the U.S. equity session model.
