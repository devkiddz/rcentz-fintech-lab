# Trading Intelligence V2.7 — Trigger Clarity & Modern Runtime Cards

## Trigger correction
DCA bots no longer show a Trigger Price.

DCA is time-driven:
- interval reached
- check balance / allocation / daily trade limit
- execute if valid

Price Below / Price Above bots remain price-triggered:
- interval controls how often the market condition is checked
- trigger price controls whether the trade is allowed

The customer update controller also clears any stale DCA trigger value when bot settings are saved.

## Modern card pass
My Bots and My Copied Strategies now use:
- stronger hierarchy
- fewer boxed metrics
- prominent P/L and return
- explicit running/paused state
- allocation progress
- visual positive/negative/neutral execution bar
- next run / interval / daily limit
- clear Configure / Edit actions

## Install
Extract over the Laravel project root, then run:

    /c/xampp/php/php.exe artisan optimize:clear
    npm run build

No migration required.

## Test
1. Open My Bots — AAPL Smart DCA should have the modern card.
2. Configure AAPL Smart DCA — Trigger Price must be absent.
3. Save settings once — any stale DCA trigger value is cleared in the database.
4. Open a Price Below/Price Above bot — trigger field should appear with contextual wording.
5. Open My Copied Strategies — cards should use the new layout.
