<x-admin-layout>
    <div class="ui-page max-w-[1500px] space-y-4">
        <section class="ui-panel overflow-hidden">
            <div class="relative p-5 sm:p-6">
                <div class="absolute inset-y-0 right-0 hidden w-1/3 bg-gradient-to-l from-red-500/[.06] to-transparent lg:block"></div>
                <div class="relative flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[.18em] text-muted-foreground"><span>Admin</span><span>•</span><span>Signal Engine</span><span>•</span><span>S4</span></div>
                        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-foreground">Signal Desk</h1>
                        <p class="mt-1 max-w-3xl text-sm leading-6 text-muted-foreground">Supervise generated opportunities, publish Signal contracts, distribute them to customers and audit every analysis, revision, event and delivery.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.signals.candidates') }}" class="ui-btn ui-btn-primary"><i data-lucide="sparkles" class="h-4 w-4"></i>Review candidates</a>
                        <a href="{{ route('admin.signals.recipients') }}" class="ui-btn ui-btn-secondary"><i data-lucide="users-round" class="h-4 w-4"></i>Recipients</a>
                        <a href="{{ route('admin.signals.activity') }}" class="ui-btn ui-btn-secondary"><i data-lucide="activity" class="h-4 w-4"></i>Engine activity</a>
                    </div>
                </div>
            </div>
        </section>

        @include('admin.signals._nav')

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            <article class="ui-metric-card"><div class="ui-metric-icon"><i data-lucide="sparkles" class="h-5 w-5"></i></div><div><p class="ui-label">Ready</p><p class="mt-1 text-2xl font-semibold">{{ number_format($stats['ready']) }}</p></div></article>
            <article class="ui-metric-card"><div class="ui-metric-icon"><i data-lucide="send" class="h-5 w-5"></i></div><div><p class="ui-label">Published</p><p class="mt-1 text-2xl font-semibold">{{ number_format($stats['published']) }}</p></div></article>
            <article class="ui-metric-card"><div class="ui-metric-icon"><i data-lucide="radio-tower" class="h-5 w-5"></i></div><div><p class="ui-label">Active</p><p class="mt-1 text-2xl font-semibold">{{ number_format($stats['active']) }}</p></div></article>
            <article class="ui-metric-card"><div class="ui-metric-icon"><i data-lucide="archive" class="h-5 w-5"></i></div><div><p class="ui-label">Terminal</p><p class="mt-1 text-2xl font-semibold">{{ number_format($stats['terminal']) }}</p></div></article>
            <article class="ui-metric-card"><div class="ui-metric-icon"><i data-lucide="users-round" class="h-5 w-5"></i></div><div><p class="ui-label">Recipients</p><p class="mt-1 text-2xl font-semibold">{{ number_format($stats['recipients']) }}</p><p class="mt-1 text-[10px] text-muted-foreground">{{ number_format($stats['deliveries']) }} deliveries</p></div></article>
            <article class="ui-metric-card"><div class="ui-metric-icon"><i data-lucide="calendar-clock" class="h-5 w-5"></i></div><div><p class="ui-label">Generated today</p><p class="mt-1 text-2xl font-semibold">{{ number_format($stats['today']) }}</p></div></article>
        </section>

        <section class="grid gap-4 xl:grid-cols-[1.25fr_.75fr]">
            <article class="ui-panel overflow-hidden">
                <div class="flex items-center justify-between border-b border-border px-5 py-4">
                    <div><p class="ui-kicker">Current authority</p><h2 class="mt-1 text-base font-semibold">Latest Signals</h2></div>
                    <a href="{{ route('admin.signals.candidates') }}" class="ui-btn ui-btn-secondary ui-btn-sm">Open queue</a>
                </div>
                <div class="grid gap-3 p-4 sm:grid-cols-2">
                    @forelse($latest as $signal)
                        @php
                            $statusClass = match($signal->status) {
                                'ready' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
                                'published' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
                                'active' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
                                'stopped','cancelled','invalidated' => 'bg-red-500/10 text-red-600 dark:text-red-400',
                                default => 'bg-muted text-muted-foreground',
                            };
                            $directionClass = $signal->direction === 'sell'
                                ? 'bg-red-500/10 text-red-600 dark:text-red-400'
                                : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400';
                        @endphp
                        <article class="rounded-xl border border-border bg-muted/[.12] p-4 transition-colors hover:bg-muted/25">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-start gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border bg-card"><i data-lucide="{{ $signal->direction === 'sell' ? 'trending-down' : 'trending-up' }}" class="h-4 w-4"></i></div>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <h3 class="text-sm font-semibold">{{ $signal->instrument_symbol }}</h3>
                                            <span class="rounded-full px-2 py-0.5 text-[8px] font-semibold uppercase {{ $directionClass }}">{{ $signal->direction }}</span>
                                            <span class="rounded-full px-2 py-0.5 text-[8px] font-semibold uppercase {{ $statusClass }}">{{ $signal->status }}</span>
                                        </div>
                                        <p class="mt-1 truncate text-[10px] text-muted-foreground">Signal #{{ $signal->id }} · {{ $signal->generated_at?->diffForHumans() }}</p>
                                    </div>
                                </div>
                                <a href="{{ route('admin.signals.show', $signal) }}" class="ui-btn ui-btn-secondary ui-btn-sm shrink-0" aria-label="Open Signal #{{ $signal->id }}"><i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i></a>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                <div class="min-w-0 rounded-lg border border-border/80 bg-card/60 px-2.5 py-2">
                                    <p class="text-[8px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Market</p>
                                    <p class="mt-1 whitespace-nowrap text-[11px] font-semibold leading-4 uppercase">{{ $signal->marketplace }}</p>
                                </div>
                                <div class="min-w-0 rounded-lg border border-border/80 bg-card/60 px-2.5 py-2">
                                    <p class="text-[8px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Timeframe</p>
                                    <p class="mt-1 whitespace-nowrap text-[11px] font-semibold leading-4 uppercase">{{ $signal->timeframe }}</p>
                                </div>
                                <div class="min-w-0 rounded-lg border border-border/80 bg-card/60 px-2.5 py-2">
                                    <p class="text-[8px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Strength</p>
                                    <p class="mt-1 whitespace-nowrap text-[11px] font-semibold leading-4 tracking-tight">{{ strtoupper(str_replace('_', ' ', (string) $signal->strength)) }}</p>
                                </div>
                                <div class="min-w-0 rounded-lg border border-border/80 bg-card/60 px-2.5 py-2">
                                    <p class="text-[8px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Confluence</p>
                                    <p class="mt-1 whitespace-nowrap text-[11px] font-semibold leading-4">{{ number_format((float) $signal->confluence_score, 2) }}%</p>
                                </div>
                            </div>

                            <div class="mt-2 grid grid-cols-3 gap-2">
                                <div class="rounded-lg bg-muted/35 px-2.5 py-2"><p class="text-[8px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Entry</p><p class="mt-1 whitespace-nowrap text-[10px] font-semibold">{{ number_format((float) $signal->entry_min, $signal->price_precision) }}–{{ number_format((float) $signal->entry_max, $signal->price_precision) }}</p></div>
                                <div class="rounded-lg bg-muted/35 px-2.5 py-2"><p class="text-[8px] font-semibold uppercase tracking-[.11em] text-muted-foreground">Stop</p><p class="mt-1 whitespace-nowrap text-[10px] font-semibold">{{ number_format((float) $signal->stop_loss, $signal->price_precision) }}</p></div>
                                <div class="rounded-lg bg-muted/35 px-2.5 py-2"><p class="text-[8px] font-semibold uppercase tracking-[.11em] text-muted-foreground">R:R</p><p class="mt-1 whitespace-nowrap text-[10px] font-semibold">1:{{ number_format((float) $signal->risk_reward, 2) }}</p></div>
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-3 border-t border-border pt-3">
                                <div class="flex min-w-0 flex-wrap gap-1.5">
                                    @foreach($signal->targets->take(3) as $target)
                                        <span class="rounded-md bg-muted px-2 py-1 text-[9px] font-semibold"><span class="text-muted-foreground">TP{{ $target->sequence }}</span> {{ number_format((float) $target->price, 2) }}</span>
                                    @endforeach
                                </div>
                                <span class="shrink-0 text-[9px] text-muted-foreground">{{ $signal->targets->count() }} targets</span>
                            </div>
                        </article>
                    @empty
                        <div class="p-10 text-center text-sm text-muted-foreground sm:col-span-2">No Signals exist yet.</div>
                    @endforelse
                </div>
            </article>

            <article class="ui-panel overflow-hidden">
                <div class="border-b border-border px-5 py-4"><p class="ui-kicker">Audit stream</p><h2 class="mt-1 text-base font-semibold">Recent engine events</h2></div>
                <div class="divide-y divide-border">
                    @forelse($recentEvents as $event)
                        <div class="px-5 py-3.5">
                            <div class="flex items-start gap-3"><div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-muted"><i data-lucide="activity" class="h-4 w-4"></i></div><div class="min-w-0"><p class="text-xs font-semibold uppercase tracking-wide">{{ str_replace('_',' ', $event->type) }}</p><p class="mt-1 text-xs text-muted-foreground">Signal #{{ $event->signal_id }} · {{ $event->signal?->instrument_symbol ?? 'Unknown' }} · {{ $event->occurred_at?->diffForHumans() }}</p></div></div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-xs text-muted-foreground">No Signal events yet.</div>
                    @endforelse
                </div>
            </article>
        </section>

        <details class="ui-panel group overflow-hidden">
            <summary class="flex cursor-pointer items-center justify-between px-5 py-4 sm:px-6"><div class="flex items-center gap-3"><div class="ui-metric-icon"><i data-lucide="pen-tool" class="h-5 w-5"></i></div><div><p class="ui-kicker">Manual authority</p><h2 class="mt-1 text-base font-semibold">Create manual Signal</h2></div></div><i data-lucide="chevron-down" class="h-4 w-4 transition-transform group-open:rotate-180"></i></summary>
            <div class="border-t border-border p-5 sm:p-6">
                <p class="mb-5 max-w-3xl text-xs leading-5 text-muted-foreground">Manual Signals enter the same <strong class="text-foreground">ready</strong> state as automated candidates. Publication and distribution remain separate actions, so creating a Signal never sends it to customers.</p>
                <form method="POST" action="{{ route('admin.signals.store') }}" class="grid gap-4 lg:grid-cols-4">@csrf
                    <div><label class="ui-label">Instrument</label><select name="stock_id" class="ui-input w-full" required><option value="">Select</option>@foreach($stocks as $stock)<option value="{{ $stock->id }}" @selected(old('stock_id') == $stock->id)>{{ $stock->symbol }} — {{ $stock->company_name }}</option>@endforeach</select></div>
                    <div><label class="ui-label">Marketplace</label><select name="marketplace" class="ui-input w-full" required><option value="live">Live</option><option value="controlled">Controlled</option></select></div>
                    <div><label class="ui-label">Direction</label><select name="direction" class="ui-input w-full" required><option value="buy">BUY</option><option value="sell">SELL</option></select></div>
                    <div><label class="ui-label">Timeframe</label><select name="timeframe" class="ui-input w-full" required>@foreach(['5m','15m','1h','4h','1d','1w'] as $tf)<option value="{{ $tf }}" @selected(old('timeframe','1d') === $tf)>{{ strtoupper($tf) }}</option>@endforeach</select></div>
                    <div><label class="ui-label">Strength</label><select name="strength" class="ui-input w-full"><option value="moderate">Moderate</option><option value="strong">Strong</option><option value="very_strong">Very strong</option></select></div>
                    <div><label class="ui-label">Confluence %</label><input name="confluence_score" type="number" min="0" max="100" step="0.01" class="ui-input w-full" value="{{ old('confluence_score') }}" placeholder="Optional"></div>
                    <div><label class="ui-label">Entry min</label><input name="entry_min" type="number" min="0" step="0.00000001" class="ui-input w-full" value="{{ old('entry_min') }}" required></div>
                    <div><label class="ui-label">Entry max</label><input name="entry_max" type="number" min="0" step="0.00000001" class="ui-input w-full" value="{{ old('entry_max') }}" required></div>
                    <div><label class="ui-label">Stop loss</label><input name="stop_loss" type="number" min="0" step="0.00000001" class="ui-input w-full" value="{{ old('stop_loss') }}" required></div>
                    <div><label class="ui-label">TP1</label><input name="tp1" type="number" min="0" step="0.00000001" class="ui-input w-full" value="{{ old('tp1') }}" required></div>
                    <div><label class="ui-label">TP2</label><input name="tp2" type="number" min="0" step="0.00000001" class="ui-input w-full" value="{{ old('tp2') }}"></div>
                    <div><label class="ui-label">TP3</label><input name="tp3" type="number" min="0" step="0.00000001" class="ui-input w-full" value="{{ old('tp3') }}"></div>
                    <div class="lg:col-span-2"><label class="ui-label">Expiry</label><input name="expires_at" type="datetime-local" class="ui-input w-full" value="{{ old('expires_at', now()->addDays(2)->format('Y-m-d\\TH:i')) }}" required></div>
                    <div class="lg:col-span-2"><label class="ui-label">Rationale</label><input name="rationale" class="ui-input w-full" value="{{ old('rationale') }}" placeholder="Why this setup exists"></div>
                    <div class="lg:col-span-4 flex justify-end"><button class="ui-btn ui-btn-primary"><i data-lucide="plus" class="h-4 w-4"></i>Create ready Signal</button></div>
                </form>
            </div>
        </details>
    </div>
</x-admin-layout>
