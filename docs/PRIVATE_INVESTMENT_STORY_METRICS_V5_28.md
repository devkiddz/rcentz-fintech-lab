# V5.28 — Investment Story Metrics + Adaptive Return Cycles

Private Investment listings now carry flexible product terms rather than assuming all returns happen daily.

New instrument terms:
- duration_days
- return_interval_days
- projected_return_min_percent
- projected_return_max_percent
- subscription_fee_percent
- redemption_fee_percent

Examples can therefore express:
- 0.12%–0.24% every 3 days,
- 0.28%–0.48% every week,
- 0.65%–0.95% every 30 days,
while the instrument itself can run for 90, 180, 360 days, etc.

Projection cards calculate from the minimum subscription or selected capital using:
authoritative unit price + subscription fee + management fee + configured return cycle.

The displayed values are illustrative product projections, not guarantees or live-market forecasts.

V5.28 also moves Investment Action beside the chart on wide screens and enriches the market overview cards.
