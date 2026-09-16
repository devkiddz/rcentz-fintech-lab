# V5.26.6 — Ownership + Admin Mutation Authority

This milestone corrects the routing boundary and explicitly gives administrators mutation authority without weakening customer ownership.

## Boundary

Customer account routes:
- customer owns and mutates their own account,
- admin may GET them in audit mode.

Admin mutation routes:
- POST /admin/investments/accounts/{user}/watchlist/{instrument}
- PATCH /admin/investments/accounts/{user}/watchlist/{instrument}
- DELETE /admin/investments/accounts/{user}/watchlist/{instrument}

The target customer is explicit, so admin never accidentally mutates an "admin-owned customer account".

This pattern is the template for holdings, subscriptions, redemptions and future account operations.
