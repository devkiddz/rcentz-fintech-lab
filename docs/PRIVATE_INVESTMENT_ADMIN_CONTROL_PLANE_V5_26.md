# V5.26 — Private Investment Admin Control Plane

The new /admin/investments route is now the canonical mutation authority for PrivateInvestmentInstrument.

Admin can:
- create and configure instruments,
- pause/resume and control visibility,
- manage underlying asset composition,
- apply audited valuation events through PrivateInvestmentValuationService,
- automatically write price history and revalue active holdings,
- edit the customer-facing Pricing Authority title/message,
- jump directly to the customer market for audit.

Legacy InvestmentPlan/NAV admin routes remain available as explicitly labeled migration-era compatibility surfaces.
