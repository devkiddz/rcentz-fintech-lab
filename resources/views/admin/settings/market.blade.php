<x-admin-layout>
<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Admin · Settings</p>
            <h1 class="ui-heading">Market Settings</h1>
            <p class="ui-lead max-w-3xl">Configure price authority and automatic market behaviour. Instrument operations remain separate from configuration.</p>
        </div>
        <a href="{{ route('admin.trading.marketplace') }}" class="ui-btn ui-btn-secondary">Instrument Operations</a>
    </section>

    <main class="min-w-0">
            @include('admin.settings.partials.flash')

            <section class="grid gap-4 xl:grid-cols-2">
                <div class="ui-panel p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="ui-kicker">Price source</p>
                            <h2 class="mt-1 text-lg font-semibold">Active source</h2>
                            <p class="mt-1 text-xs text-muted-foreground">The selected source supplies display and new execution prices.</p>
                        </div>
                        <span class="rounded-full border border-border bg-muted px-3 py-1 text-[9px] font-semibold">
                            {{ $marketEnvironment->active_marketplace === 'live' ? 'External Feed' : 'Internal Feed' }}
                        </span>
                    </div>

                    <form method="POST" action="{{ route('admin.settings.market.source') }}" class="mt-5 grid gap-3 sm:grid-cols-2">
                        @csrf
                        <button name="active_marketplace" value="live" class="rounded-xl border p-4 text-left transition {{ $marketEnvironment->active_marketplace === 'live' ? 'border-emerald-500 bg-emerald-500/10' : 'border-border hover:bg-muted/30' }}">
                            <p class="text-xs font-semibold">External Feed</p>
                            <p class="mt-1 text-[9px] leading-4 text-muted-foreground">External market data with regular U.S. session rules.</p>
                        </button>
                        <button name="active_marketplace" value="controlled" class="rounded-xl border p-4 text-left transition {{ $marketEnvironment->active_marketplace === 'controlled' ? 'border-violet-500 bg-violet-500/10' : 'border-border hover:bg-muted/30' }}">
                            <p class="text-xs font-semibold">Internal Feed</p>
                            <p class="mt-1 text-[9px] leading-4 text-muted-foreground">Internal pricing used by execution, P/L, risk and market scenarios.</p>
                        </button>
                    </form>

                    <div class="mt-4 rounded-xl border border-border bg-muted/15 p-3 text-[9px] leading-5 text-muted-foreground">
                        Existing exposure stays bound to its opening source.<br>
                        External: {{ $marketExposure['external_positions'] }} open positions / {{ $marketExposure['external_holdings'] }} holdings ·
                        Internal: {{ $marketExposure['internal_positions'] }} open positions / {{ $marketExposure['internal_holdings'] }} holdings
                    </div>
                </div>

                <div class="ui-panel p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="ui-kicker">Feed health</p>
                            <h2 class="mt-1 text-lg font-semibold">External data</h2>
                        </div>
                        <span class="rounded-full border px-3 py-1 text-[9px] font-semibold {{ $marketHealth['healthy'] ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : 'border-amber-500/20 bg-amber-500/10 text-amber-600' }}">
                            {{ $marketHealth['healthy'] ? 'Detected' : 'Needs data' }}
                        </span>
                    </div>
                    <div class="mt-5 grid grid-cols-2 gap-px overflow-hidden rounded-xl bg-border sm:grid-cols-4">
                        @foreach([
                            ['Finnhub', $marketHealth['finnhub_available'] ? 'Configured' : 'Unavailable'],
                            ['Yahoo', $marketHealth['yahoo_available'] ? 'Available' : 'Unavailable'],
                            ['Fresh ≤15m', $marketHealth['fresh_stocks'].' / '.$marketHealth['active_stocks']],
                            ['Latest quote', $marketHealth['latest_quote_symbol'] ?: 'None'],
                        ] as [$label,$value])
                            <div class="bg-background p-3">
                                <p class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">{{ $label }}</p>
                                <p class="mt-1 text-[10px] font-semibold">{{ $value }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="ui-panel mt-4 p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="ui-kicker">Market movement</p>
                        <h2 class="mt-1 text-lg font-semibold">Automatic pricing behaviour</h2>
                        <p class="mt-1 text-xs text-muted-foreground">Direction, strength and interval are consumed by the guarded market engine.</p>
                    </div>
                    <span class="rounded-full border border-border px-3 py-1 text-[9px] font-semibold">{{ (int)$marketEnvironment->controlled_tick_seconds }}s interval</span>
                </div>

                <form method="POST" action="{{ route('admin.settings.market.movement') }}" class="mt-5 grid gap-4 xl:grid-cols-[1.2fr_.55fr_.75fr_auto] xl:items-end">
                    @csrf
                    <div>
                        <label class="text-[9px] uppercase tracking-[.1em] text-muted-foreground">Direction</label>
                        <div class="mt-2 grid grid-cols-3 gap-2">
                            @foreach(['up'=>'Drive Up','down'=>'Drive Down','range'=>'Consolidate'] as $value=>$label)
                                <label class="cursor-pointer rounded-xl border p-3 text-center text-[10px] font-semibold {{ $marketEnvironment->controlled_drive_mode === $value ? 'border-violet-500 bg-violet-500/10' : 'border-border' }}">
                                    <input type="radio" class="sr-only" name="controlled_drive_mode" value="{{ $value }}" {{ $marketEnvironment->controlled_drive_mode === $value ? 'checked' : '' }}>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="text-[9px] uppercase tracking-[.1em] text-muted-foreground">Strength</label>
                        <input class="ui-input mt-2 w-full" type="number" step="0.1" min="0.1" max="3" name="controlled_drive_strength" value="{{ (float)$marketEnvironment->controlled_drive_strength }}">
                    </div>
                    <div>
                        <label class="text-[9px] uppercase tracking-[.1em] text-muted-foreground">Movement interval</label>
                        <select class="ui-input mt-2 w-full" name="controlled_tick_seconds">
                            @foreach([5=>'5 seconds',10=>'10 seconds',15=>'15 seconds',30=>'30 seconds',60=>'1 minute',120=>'2 minutes',300=>'5 minutes'] as $seconds=>$label)
                                <option value="{{ $seconds }}" {{ (int)$marketEnvironment->controlled_tick_seconds === $seconds ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="ui-btn ui-btn-primary justify-center">Save Market Settings</button>
                </form>
            </section>
    </main>
</div>
</x-admin-layout>
