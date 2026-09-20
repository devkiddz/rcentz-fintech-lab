<x-user-layout>
<x-slot name="header">Configure Bot</x-slot>

@php
    $product = $subscription->product;
    $bot = $subscription->bot;
    $isDca = $product->strategy === 'dca';
    $isBelow = $product->strategy === 'price_below';
    $isAbove = $product->strategy === 'price_above';
    $symbol = $product->marketInstrument?->display_symbol ?? $product->stock?->symbol ?? '—';
    $assetClass = strtoupper($product->marketInstrument?->asset_class ?? 'stock');
@endphp

<div class="ui-page max-w-5xl">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">AI Trading Bots</p>
            <h1 class="ui-heading">Configure {{ $product->name }}</h1>
            <p class="ui-lead">Control your runtime settings without changing the admin-owned bot strategy.</p>
        </div>
        <a href="{{ route('ai-bots.my-bots') }}" class="ui-btn ui-btn-secondary">Back to My Bots</a>
    </section>

    <div class="grid gap-6 lg:grid-cols-[1.2fr_.8fr]">
        <form method="POST" action="{{ route('ai-bots.update',$subscription) }}" class="ui-panel p-6 space-y-6">
            @csrf
            @method('PATCH')

            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl bg-muted/35 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">Strategy</p>
                    <p class="mt-2 text-lg font-semibold">{{ strtoupper(str_replace('_',' ',$product->strategy)) }}</p>
                </div>
                <div class="rounded-xl bg-muted/35 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">Asset</p>
                    <p class="mt-2 text-lg font-semibold">{{ $symbol }}</p><p class="mt-1 text-[10px] uppercase tracking-[.12em] text-muted-foreground">{{ $assetClass }}</p>
                </div>
                <div class="rounded-xl bg-muted/35 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">Product Limit</p>
                    <p class="mt-2 text-lg font-semibold">{{ $product->max_user_allocation ? format_currency($product->max_user_allocation) : 'Open' }}</p>
                </div>
            </div>

            <div class="space-y-5">
                <div>
                    <label class="ui-label">Trade Amount</label>
                    <input
                        name="amount_per_trade"
                        type="number"
                        min="1"
                        step="0.01"
                        class="ui-input"
                        value="{{ old('amount_per_trade',$bot?->amount_per_trade) }}"
                        {{ $product->allow_user_trade_amount ? '' : 'readonly' }}
                    >
                    <p class="mt-1.5 text-xs text-muted-foreground">
                        Amount the bot attempts to use for each successful execution.
                        @unless($product->allow_user_trade_amount) This value is locked by admin product rules. @endunless
                    </p>
                </div>

                @unless($isDca)
                    <div>
                        <label class="ui-label">
                            {{ $isBelow ? 'Buy Below Price' : ($isAbove ? 'Buy Above Price' : 'Trigger Price') }}
                        </label>
                        <input
                            name="trigger_price"
                            type="number"
                            min="0.00000001"
                            step="any"
                            class="ui-input"
                            value="{{ old('trigger_price',$bot?->trigger_price) }}"
                            {{ $product->allow_user_trigger_price ? '' : 'readonly' }}
                        >
                        <p class="mt-1.5 text-xs text-muted-foreground">
                            @if($isBelow)
                                The bot only executes when {{ $symbol }} is at or below this price.
                            @elseif($isAbove)
                                The bot only executes when {{ $symbol }} is at or above this price.
                            @else
                                The market condition that must be met before execution.
                            @endif
                            @unless($product->allow_user_trigger_price) This value is locked by admin product rules. @endunless
                        </p>
                    </div>
                @endunless

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="ui-label">{{ $isDca ? 'Run Every' : 'Check Market Every' }}</label>
                        <div class="relative">
                            <input name="interval_minutes" type="number" min="5" max="10080" class="ui-input pr-24" value="{{ old('interval_minutes',$bot?->interval_minutes) }}" required>
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-muted-foreground">minutes</span>
                        </div>
                        <p class="mt-1.5 text-xs text-muted-foreground">
                            {{ $isDca ? 'Controls how often the bot attempts its next DCA execution.' : 'Controls how often the bot checks whether the price condition is true.' }}
                        </p>
                    </div>

                    <div>
                        <label class="ui-label">Max Daily Trades</label>
                        <input name="max_daily_trades" type="number" min="1" max="24" class="ui-input" value="{{ old('max_daily_trades',$bot?->max_daily_trades) }}" required>
                        <p class="mt-1.5 text-xs text-muted-foreground">Hard limit on successful executions in one day.</p>
                    </div>
                </div>

                <div class="rounded-xl border border-border bg-muted/10 p-4">
                    <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Position risk</p>
                    <h3 class="mt-1 text-sm font-semibold">Exit rules for bot-opened positions</h3>
                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        <div><label class="ui-label">Stop Loss %</label><input name="stop_loss_percent" type="number" min="0.01" max="100" step="0.01" class="ui-input" value="{{ old('stop_loss_percent',$bot?->stop_loss_percent) }}"></div>
                        <div><label class="ui-label">Take Profit %</label><input name="take_profit_percent" type="number" min="0.01" max="100" step="0.01" class="ui-input" value="{{ old('take_profit_percent',$bot?->take_profit_percent) }}"></div>
                        <div><label class="ui-label">Max Holding (min)</label><input name="position_duration_minutes" type="number" min="1" max="43200" class="ui-input" value="{{ old('position_duration_minutes',$bot?->position_duration_minutes) }}"></div>
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">The first of stop loss, take profit or time expiry closes the bot-attributed position.</p>
                </div>
                <div>
                    <label class="ui-label">Allocation Cap</label>
                    <input name="max_total_spend" type="number" min="1" step="0.01" class="ui-input" value="{{ old('max_total_spend',$bot?->max_total_spend) }}">
                    <p class="mt-1.5 text-xs text-muted-foreground">Maximum total amount this runtime bot may deploy.</p>
                </div>
            </div>

            <input type="hidden" name="quantity_per_trade" value="">

            <div class="flex justify-end gap-3 border-t border-border pt-5">
                <a href="{{ route('ai-bots.my-bots') }}" class="ui-btn ui-btn-secondary">Cancel</a>
                <button class="ui-btn ui-btn-primary">Save Bot Settings</button>
            </div>
        </form>

        <aside class="space-y-4">
            <div class="ui-panel p-5">
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">How this bot decides</p>

                @if($isDca)
                    <h2 class="mt-3 text-lg font-semibold">Time is the trigger.</h2>
                    <p class="mt-2 text-sm leading-6 text-muted-foreground">
                        A DCA bot does not wait for a particular market price. Every
                        <strong class="text-foreground">{{ number_format((int)($bot?->interval_minutes ?? 0)) }} minutes</strong>,
                        it attempts to invest <strong class="text-foreground">{{ format_currency($bot?->amount_per_trade ?? 0) }}</strong>.
                    </p>
                    <div class="mt-4 rounded-xl border border-border bg-muted/25 p-4 text-sm">
                        <div class="flex items-center justify-between gap-4"><span class="text-muted-foreground">Decision</span><strong>Time reached?</strong></div>
                        <div class="my-3 h-px bg-border"></div>
                        <div class="flex items-center justify-between gap-4"><span class="text-muted-foreground">Then check</span><strong>Balance + daily limit + allocation</strong></div>
                        <div class="my-3 h-px bg-border"></div>
                        <div class="flex items-center justify-between gap-4"><span class="text-muted-foreground">If valid</span><strong>Execute trade</strong></div>
                    </div>
                @elseif($isBelow)
                    <h2 class="mt-3 text-lg font-semibold">Price below is the trigger.</h2>
                    <p class="mt-2 text-sm leading-6 text-muted-foreground">
                        Every {{ number_format((int)($bot?->interval_minutes ?? 0)) }} minutes, the bot checks the market.
                        It executes only when {{ $symbol }} is at or below
                        <strong class="text-foreground">{{ format_currency($bot?->trigger_price ?? 0) }}</strong>.
                    </p>
                @elseif($isAbove)
                    <h2 class="mt-3 text-lg font-semibold">Price above is the trigger.</h2>
                    <p class="mt-2 text-sm leading-6 text-muted-foreground">
                        Every {{ number_format((int)($bot?->interval_minutes ?? 0)) }} minutes, the bot checks the market.
                        It executes only when {{ $symbol }} is at or above
                        <strong class="text-foreground">{{ format_currency($bot?->trigger_price ?? 0) }}</strong>.
                    </p>
                @else
                    <h2 class="mt-3 text-lg font-semibold">Rule-driven execution.</h2>
                    <p class="mt-2 text-sm leading-6 text-muted-foreground">The strategy determines the market condition required before execution.</p>
                @endif
            </div>

            <div class="ui-panel p-5">
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">Runtime State</p>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><span class="text-muted-foreground">Subscription</span><strong>{{ ucfirst($subscription->status) }}</strong></div>
                    <div class="flex justify-between gap-4"><span class="text-muted-foreground">Bot status</span><strong>{{ ucfirst($bot?->status ?? 'paused') }}</strong></div>
                    <div class="flex justify-between gap-4"><span class="text-muted-foreground">Spent so far</span><strong>{{ format_currency($bot?->spent_total ?? 0) }}</strong></div>
                    <div class="flex justify-between gap-4"><span class="text-muted-foreground">Next run</span><strong>{{ optional($bot?->next_run_at)->format('M d · H:i') ?? '—' }}</strong></div>
                </div>
            </div>
        </aside>
    </div>
</div>
</x-user-layout>