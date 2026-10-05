<x-admin-layout>
<x-slot name="header">Market Settings</x-slot>
<style>
[data-market-settings] .market-choice { display:block; position:relative; min-width:0; cursor:pointer; }
[data-market-settings] .market-choice-surface { display:flex; height:100%; flex-direction:column; gap:.75rem; padding:1rem; border:1px solid hsl(var(--border)); border-radius:1rem; background:hsl(var(--card)); transition:border-color 150ms ease, background-color 150ms ease; }
[data-market-settings] .market-choice:hover .market-choice-surface { background:hsl(var(--muted)); }
[data-market-settings] .market-choice input:checked + .market-choice-surface { border-color:hsl(var(--primary)); background:color-mix(in srgb,hsl(var(--primary)) 9%,hsl(var(--card))); box-shadow:inset 0 0 0 1px hsl(var(--primary)); }
[data-market-settings] .market-choice input:focus-visible + .market-choice-surface { outline:2px solid hsl(var(--ring)); outline-offset:3px; }
[data-market-settings] .market-choice-check { display:inline-flex; flex-shrink:0; align-items:center; justify-content:center; width:1.25rem; height:1.25rem; border:1px solid hsl(var(--border)); border-radius:999px; }
[data-market-settings] .market-choice-check svg { opacity:0; }
[data-market-settings] .market-choice input:checked + .market-choice-surface .market-choice-check { background:hsl(var(--primary)); border-color:hsl(var(--primary)); color:hsl(var(--primary-foreground)); }
[data-market-settings] .market-choice input:checked + .market-choice-surface .market-choice-check svg { opacity:1; }
[data-market-settings] .market-choice-selected { visibility:hidden; font-size:.625rem; font-weight:600; color:hsl(var(--primary)); }
[data-market-settings] .market-choice input:checked + .market-choice-surface .market-choice-selected { visibility:visible; }
@media (prefers-reduced-motion:reduce) { [data-market-settings] .market-choice-surface { transition:none; } }
[data-market-settings] :disabled { opacity:.45; cursor:not-allowed; }
[data-market-settings] .market-choice:has(input:disabled) { cursor:not-allowed; }
[data-market-settings] .market-choice input:disabled + .market-choice-surface { opacity:.45; }
</style>
<div class="ui-page max-w-[1500px]" data-market-settings>
    <p class="ui-panel p-4 text-sm">{{ $marketEnvironment->active_marketplace === 'live' ? 'Movement controls are disabled while Live is selected. Background movement continues.' : 'Configured price movement is active.' }}</p>
    <section class="flex flex-col gap-4 border-b border-border/60 pb-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="ui-kicker">Admin · Settings</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">Market Settings</h1>
            <p class="mt-1.5 max-w-3xl text-xs leading-5 text-muted-foreground">Choose the pricing source and configure market movement.</p>
        </div>
        <div class="flex flex-wrap gap-2"><a href="{{ route('admin.settings.index') }}" class="ui-btn ui-btn-secondary ui-btn-sm">All settings</a><a href="{{ route('admin.trading.marketplace') }}" class="ui-btn ui-btn-secondary ui-btn-sm">Instrument operations</a></div>
    </section>

    <div class="mt-4">@include('admin.settings.partials.flash')</div>
    <section class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Saved market settings">
        @foreach([
            ['Active source',$marketEnvironment->active_marketplace==='live'?'Live market':'Market'],
            ['Saved direction',['up'=>'Drive up','down'=>'Drive down','range'=>'Consolidate','neutral'=>'Neutral'][$marketEnvironment->controlled_drive_mode] ?? 'Not set'],
            ['Strength',number_format((float)$marketEnvironment->controlled_drive_strength,1)],
            ['Movement interval',(int)$marketEnvironment->controlled_tick_seconds.' seconds'],
        ] as [$label,$value])<div class="ui-panel p-4"><p class="text-[9px] uppercase tracking-widest text-muted-foreground">{{ $label }}</p><p class="mt-3 text-sm font-semibold">{{ $value }}</p></div>@endforeach
    </section>
    <div class="mt-5 grid items-start gap-5 xl:grid-cols-[minmax(0,1.45fr)_minmax(0,.55fr)]">
        <main class="min-w-0 space-y-5">
                <div class="ui-panel p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="ui-kicker">Price source</p>
                            <h2 class="mt-1 text-sm font-semibold">Price source</h2>
                            <p class="mt-1 text-xs text-muted-foreground">New trades and browsing use this source. Existing positions keep their opening source.</p>
                        </div>
                        <span class="rounded-full border border-border bg-muted px-3 py-1 text-[9px] font-semibold">
                            {{ $marketEnvironment->active_marketplace === 'live' ? 'Live market' : 'Market' }}
                        </span>
                    </div>

                    <form method="POST" action="{{ route('admin.settings.market.source') }}" class="mt-5">
                        @csrf
                        <fieldset><legend class="mb-3 text-xs font-medium">Choose your price source</legend>
                        <div class="grid gap-3 sm:grid-cols-2">
                        @foreach([
                            ['live','Live market','External prices, subject to market sessions and executable quote checks.','globe-2'],
                            ['controlled','Market','Configured instrument prices for controlled market scenarios.','sliders-horizontal'],
                        ] as [$value,$label,$description,$icon])
                            <label class="market-choice">
                                <input class="sr-only" type="radio" name="active_marketplace" value="{{ $value }}" @checked(old('active_marketplace',$marketEnvironment->active_marketplace)===$value) required>
                                <span class="market-choice-surface">
                                    <span class="flex items-center justify-between gap-3"><i data-lucide="{{ $icon }}" class="h-5 w-5 text-muted-foreground"></i><span class="market-choice-check" aria-hidden="true"><svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6" /></svg></span></span>
                                    <span class="block text-sm font-semibold">{{ $label }}</span><span class="block text-[11px] leading-5 text-muted-foreground">{{ $description }}</span><span class="market-choice-selected">Selected</span>
                                </span>
                            </label>
                        @endforeach
                        </div></fieldset>
                        @error('active_marketplace')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                        <div class="mt-4 flex flex-col gap-3 border-t border-border/70 pt-4 sm:flex-row sm:items-center sm:justify-between"><p class="text-[10px] text-muted-foreground">Apply to save your selected source.</p><button type="submit" class="ui-btn ui-btn-primary justify-center">Apply price source</button></div>
                    </form>

                    <div class="mt-4 rounded-xl border border-border bg-muted/15 p-3 text-[9px] leading-5 text-muted-foreground">
                        Existing exposure stays bound to its opening source.<br>
                        Live: {{ $marketExposure['external_positions'] }} open positions / {{ $marketExposure['external_holdings'] }} holdings ·
                        Market: {{ $marketExposure['internal_positions'] }} open positions / {{ $marketExposure['internal_holdings'] }} holdings
                    </div>
                </div>

            <section class="ui-panel p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="ui-kicker">Market movement</p>
                        <h2 class="mt-1 text-sm font-semibold">Market movement</h2>
                        <p class="mt-1 text-xs text-muted-foreground">Neutral changes direction and volatility without a forced trend or consolidation target. Movement controls are disabled while Live is selected. Background movement continues.</p>
                    </div>
                    <span class="rounded-full border border-border px-3 py-1 text-[9px] font-semibold">{{ (int)$marketEnvironment->controlled_tick_seconds }}s interval</span>
                </div>

                <form method="POST" action="{{ route('admin.settings.market.movement') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                    @csrf
                    <fieldset class="sm:col-span-2"><legend class="text-xs font-medium">Direction</legend>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            @foreach(['up'=>'Drive Up','down'=>'Drive Down','range'=>'Consolidate','neutral'=>'Neutral'] as $value=>$label)
                                <label class="market-choice">
                                    <input @disabled($marketEnvironment->active_marketplace === 'live') type="radio" class="sr-only" name="controlled_drive_mode" value="{{ $value }}" @checked(old('controlled_drive_mode',$marketEnvironment->controlled_drive_mode)===$value) required>
                                    <span class="market-choice-surface"><span class="flex items-center justify-between gap-2"><span class="text-xs font-semibold">{{ $label }}</span><span class="market-choice-check" aria-hidden="true"><svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6" /></svg></span></span><span class="market-choice-selected">Selected</span></span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    @error('controlled_drive_mode')<p class="text-xs text-red-600 sm:col-span-2">{{ $message }}</p>@enderror
                    <div>
                        <label for="market-strength" class="text-xs font-medium">Strength</label>
                        <input @disabled($marketEnvironment->active_marketplace === 'live') id="market-strength" class="ui-input mt-2 w-full" type="number" step="0.1" min="0.1" max="3" name="controlled_drive_strength" value="{{ old('controlled_drive_strength',(float)$marketEnvironment->controlled_drive_strength) }}" required>
                        @error('controlled_drive_strength')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="market-interval" class="text-xs font-medium">Movement interval</label>
                        <select @disabled($marketEnvironment->active_marketplace === 'live') id="market-interval" class="ui-input mt-2 w-full" name="controlled_tick_seconds">
                            @foreach([5=>'5 seconds',10=>'10 seconds',15=>'15 seconds',30=>'30 seconds',60=>'1 minute',120=>'2 minutes',300=>'5 minutes'] as $seconds=>$label)
                                <option value="{{ $seconds }}" @selected((int)old('controlled_tick_seconds',$marketEnvironment->controlled_tick_seconds)===$seconds)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('controlled_tick_seconds')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex flex-col gap-3 border-t border-border/70 pt-4 sm:col-span-2 sm:flex-row sm:items-center sm:justify-between"><p class="text-[10px] leading-5 text-muted-foreground">Saving does not start the scheduler.</p><button @disabled($marketEnvironment->active_marketplace === 'live') type="submit" class="ui-btn ui-btn-primary justify-center">Save movement settings</button></div>
                </form>
            </section>
        </main>
        <aside class="min-w-0" aria-label="External market feed status">
                <div class="ui-panel p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="ui-kicker">Stock feed health</p>
                            <h2 class="mt-1 text-sm font-semibold">External stock data</h2>
                        </div>
                        <span class="rounded-full border px-3 py-1 text-[9px] font-semibold {{ $marketHealth['healthy'] ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : 'border-amber-500/20 bg-amber-500/10 text-amber-600' }}">
                            {{ $marketHealth['healthy'] ? 'Detected' : 'Needs data' }}
                        </span>
                    </div>
                    <div class="mt-5 grid grid-cols-2 gap-px overflow-hidden rounded-xl bg-border">
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
                    <p class="mt-3 text-[10px] leading-5 text-muted-foreground">Feed status does not confirm that the market session is open or a quote is executable.</p>
                </div>
        </aside>
    </div>
</div>
</x-admin-layout>
