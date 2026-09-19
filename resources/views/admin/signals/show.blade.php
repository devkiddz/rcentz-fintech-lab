<x-admin-layout>
    @php
        $statusClass = match($signal->status) {
            'ready' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
            'published' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
            'active' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
            'stopped','cancelled','invalidated' => 'bg-red-500/10 text-red-600 dark:text-red-400',
            default => 'bg-muted text-muted-foreground',
        };
        $tp1 = $signal->targets->firstWhere('sequence', 1)?->price;
        $tp2 = $signal->targets->firstWhere('sequence', 2)?->price;
        $tp3 = $signal->targets->firstWhere('sequence', 3)?->price;

        $precision = $signal->price_precision;
        $entryMinRaw = number_format((float) $signal->entry_min, $precision, '.', '');
        $entryMaxRaw = number_format((float) $signal->entry_max, $precision, '.', '');
        $stopRaw = number_format((float) $signal->stop_loss, $precision, '.', '');
        $copyParts = [
            strtoupper((string) $signal->instrument_symbol).' '.strtoupper((string) $signal->direction),
            'Entry '.$entryMinRaw.' - '.$entryMaxRaw,
            'SL '.$stopRaw,
        ];
        if ($tp1 !== null) $copyParts[] = 'TP1 '.number_format((float) $tp1, 8, '.', '');
        if ($tp2 !== null) $copyParts[] = 'TP2 '.number_format((float) $tp2, 8, '.', '');
        if ($tp3 !== null) $copyParts[] = 'TP3 '.number_format((float) $tp3, 8, '.', '');
        $copyParts[] = 'TF '.strtoupper((string) $signal->timeframe);
        $copySetup = implode(' | ', $copyParts);
    @endphp

    <div class="ui-page mx-auto max-w-[1500px] space-y-4" data-signal-room-layout="balanced-grid">
        <section class="ui-panel overflow-hidden" data-signal-room-layer="identity">
            <div class="relative p-5 sm:p-6">
                <div class="absolute inset-y-0 right-0 hidden w-1/3 bg-gradient-to-l from-red-500/[.05] to-transparent lg:block"></div>
                <div class="relative flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-border bg-muted/40"><i data-lucide="{{ $signal->direction === 'sell' ? 'trending-down' : 'trending-up' }}" class="h-6 w-6"></i></div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2"><p class="ui-kicker">Signal #{{ $signal->id }}</p><span class="rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase {{ $statusClass }}">{{ $signal->status }}</span></div>
                            <h1 class="mt-1 text-3xl font-semibold tracking-tight">{{ $signal->instrument_symbol }} <span class="text-base font-semibold {{ $signal->direction === 'sell' ? 'text-red-600' : 'text-emerald-600' }}">{{ strtoupper((string) $signal->direction) }}</span></h1>
                            <p class="mt-1 text-sm text-muted-foreground">{{ $signal->instrument_name }} · {{ strtoupper($signal->marketplace) }} · {{ strtoupper((string) $signal->timeframe) }} · {{ strtoupper(str_replace('_',' ', (string) $signal->strength)) }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.signals.candidates') }}" class="ui-btn ui-btn-secondary"><i data-lucide="arrow-left" class="h-4 w-4"></i>Back</a>
                        @if(in_array($signal->status, \App\Models\Signal::OPEN_STATUSES, true))
                            <form method="POST" action="{{ route('admin.signals.reanalyze', $signal) }}">@csrf<button class="ui-btn ui-btn-secondary"><i data-lucide="scan-search" class="h-4 w-4"></i>Re-analyze</button></form>
                        @endif
                        @if($signal->status === 'ready')
                            <form method="POST" action="{{ route('admin.signals.publish', $signal) }}" onsubmit="return confirm('Publish this Signal? Publication starts its market lifecycle but does not distribute it automatically.');">@csrf<button class="ui-btn ui-btn-primary"><i data-lucide="send" class="h-4 w-4"></i>Publish</button></form>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        @include('admin.signals._nav')

        <section class="grid gap-4 xl:grid-cols-[minmax(0,1.75fr)_minmax(300px,.65fr)] xl:items-stretch" data-signal-room-layer="market-grid">
            <div class="min-w-0" data-signal-room-layer="chart">
                @include('signals._instrument_chart', [
                    'signal' => $signal,
                    'analysis' => $analysis,
                    'chartHeight' => 'h-[250px] md:h-[320px]',
                ])
            </div>

            <aside class="ui-panel overflow-hidden" data-signal-room-card="snapshot">
                <div class="flex items-center justify-between gap-3 border-b border-border px-4 py-3.5">
                    <div><p class="ui-kicker">Signal snapshot</p><h2 class="mt-1 text-sm font-semibold">Current contract</h2></div>
                    <button type="button" data-copy-signal-value="{{ $copySetup }}" class="ui-btn ui-btn-secondary ui-btn-sm" title="Copy complete setup"><i data-lucide="copy" class="h-3.5 w-3.5"></i><span data-copy-label>Copy setup</span></button>
                </div>
                <div class="grid grid-cols-2 gap-px bg-border">
                    @foreach([
                        ['Status', strtoupper((string)$signal->status)],
                        ['Market', strtoupper((string)$signal->marketplace)],
                        ['Timeframe', strtoupper((string)$signal->timeframe)],
                        ['Direction', strtoupper((string)$signal->direction)],
                        ['Strength', strtoupper(str_replace('_',' ', (string)$signal->strength))],
                        ['Confluence', $signal->confluence_score !== null ? number_format((float)$signal->confluence_score, 2).'%' : 'Manual'],
                    ] as [$label, $value])
                        <div class="bg-card p-3.5">
                            <p class="text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">{{ $label }}</p>
                            <p class="mt-1.5 truncate text-xs font-semibold">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="border-t border-border p-4 text-[10px] leading-5 text-muted-foreground">
                    <div class="flex items-center justify-between gap-3"><span>Generated</span><span class="text-right font-medium text-foreground">{{ $signal->generated_at?->format('M j · H:i') ?? '—' }}</span></div>
                    <div class="mt-1.5 flex items-center justify-between gap-3"><span>Expires</span><span class="text-right font-medium text-foreground">{{ $signal->expires_at?->format('M j · H:i') ?? '—' }}</span></div>
                </div>
            </aside>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-signal-room-layer="contract-grid">
            <article class="ui-panel overflow-hidden" data-signal-room-card="entry">
                <div class="border-b border-border px-4 py-3.5"><p class="ui-kicker">Entry zone</p><h2 class="mt-1 text-sm font-semibold">Execution range</h2></div>
                <div class="divide-y divide-border">
                    @foreach([
                        ['Entry min', number_format((float)$signal->entry_min, $precision), $entryMinRaw],
                        ['Entry max', number_format((float)$signal->entry_max, $precision), $entryMaxRaw],
                    ] as [$label, $display, $copyValue])
                        <div class="flex items-center justify-between gap-4 px-4 py-4">
                            <div><p class="text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">{{ $label }}</p><p class="mt-1.5 text-base font-semibold tabular-nums">{{ $display }}</p></div>
                            <button type="button" data-copy-signal-value="{{ $copyValue }}" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-border text-muted-foreground transition hover:bg-muted hover:text-foreground" title="Copy {{ strtolower($label) }}"><i data-lucide="copy" class="h-3.5 w-3.5"></i></button>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="ui-panel overflow-hidden" data-signal-room-card="risk">
                <div class="border-b border-border px-4 py-3.5"><p class="ui-kicker">Risk & quality</p><h2 class="mt-1 text-sm font-semibold">Protection & conviction</h2></div>
                <div class="grid grid-cols-2 gap-px bg-border">
                    @foreach([
                        ['Stop loss', number_format((float)$signal->stop_loss, $precision), $stopRaw],
                        ['Primary R:R', '1:'.number_format((float)$signal->risk_reward, 2), '1:'.number_format((float)$signal->risk_reward, 2)],
                        ['Confluence', $signal->confluence_score !== null ? number_format((float)$signal->confluence_score, 2).'%' : 'Manual', $signal->confluence_score !== null ? number_format((float)$signal->confluence_score, 2).'%' : 'Manual'],
                        ['Strength', strtoupper(str_replace('_',' ', (string)$signal->strength)), strtoupper(str_replace('_',' ', (string)$signal->strength))],
                    ] as [$label, $display, $copyValue])
                        <div class="bg-card p-3.5">
                            <div class="flex items-start justify-between gap-2"><p class="text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">{{ $label }}</p><button type="button" data-copy-signal-value="{{ $copyValue }}" class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-muted-foreground transition hover:bg-muted hover:text-foreground" title="Copy {{ strtolower($label) }}"><i data-lucide="copy" class="h-3 w-3"></i></button></div>
                            <p class="mt-1.5 truncate text-xs font-semibold tabular-nums">{{ $display }}</p>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="ui-panel overflow-hidden md:col-span-2 xl:col-span-1" data-signal-room-card="targets">
                <div class="border-b border-border px-4 py-3.5"><p class="ui-kicker">Profit targets</p><h2 class="mt-1 text-sm font-semibold">Take-profit ladder</h2></div>
                <div class="divide-y divide-border">
                    @forelse($signal->targets as $target)
                        @php $targetRaw = number_format((float) $target->price, $precision, '.', ''); @endphp
                        <div class="flex items-center justify-between gap-3 px-4 py-3.5">
                            <div class="flex min-w-0 items-center gap-3"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-muted text-[9px] font-semibold">TP{{ $target->sequence }}</span><div class="min-w-0"><p class="truncate text-sm font-semibold tabular-nums">{{ number_format((float)$target->price, $precision) }}</p><p class="mt-0.5 text-[9px] uppercase text-muted-foreground">{{ $target->status }}</p></div></div>
                            <button type="button" data-copy-signal-value="{{ $targetRaw }}" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-border text-muted-foreground transition hover:bg-muted hover:text-foreground" title="Copy TP{{ $target->sequence }}"><i data-lucide="copy" class="h-3.5 w-3.5"></i></button>
                        </div>
                    @empty
                        <p class="p-5 text-xs text-muted-foreground">No targets attached.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            <article class="ui-panel overflow-hidden">
                <div class="border-b border-border px-5 py-4"><p class="ui-kicker">Trade context</p><h2 class="mt-1 text-base font-semibold">Rationale & timing</h2></div>
                <div class="p-5">
                    <div class="rounded-xl border border-border bg-muted/10 p-4"><p class="ui-label">Rationale</p><p class="mt-2 text-sm leading-6 text-muted-foreground">{{ $signal->rationale ?: 'No narrative rationale is attached to this Signal.' }}</p></div>
                    @if($signal->marketInstrument?->isCrypto())
                        <div class="mt-4 grid gap-px overflow-hidden rounded-xl border border-border bg-border sm:grid-cols-3" data-crypto-timing-context>
                            <div class="bg-card p-4">
                                <p class="ui-label">Market hours</p>
                                <p class="mt-1.5 text-sm font-semibold">24/7</p>
                            </div>
                            <div class="bg-card p-4">
                                <p class="ui-label">Session</p>
                                <p class="mt-1.5 text-sm font-semibold">Always open</p>
                            </div>
                            <div class="bg-card p-4">
                                <p class="ui-label">Timing model</p>
                                <p class="mt-1.5 text-sm font-semibold">Continuous crypto market</p>
                            </div>
                        </div>
                    @endif
                    <div class="mt-4 grid gap-3 text-xs text-muted-foreground sm:grid-cols-2"><div><span class="font-semibold text-foreground">Generated:</span> {{ $signal->generated_at?->format('M j, Y g:i A') ?? '—' }}</div><div><span class="font-semibold text-foreground">Expires:</span> {{ $signal->expires_at?->format('M j, Y g:i A') ?? '—' }}</div><div><span class="font-semibold text-foreground">Published:</span> {{ $signal->published_at?->format('M j, Y g:i A') ?? '—' }}</div><div><span class="font-semibold text-foreground">Activated:</span> {{ $signal->activated_at?->format('M j, Y g:i A') ?? '—' }}</div></div>
                    <div class="mt-4 flex flex-wrap gap-2 text-[10px] text-muted-foreground"><span class="rounded-full border border-border px-2.5 py-1">{{ $signal->revisions->count() }} revisions</span><span class="rounded-full border border-border px-2.5 py-1">{{ $signal->analysisRuns->count() }} analyses</span><span class="rounded-full border border-border px-2.5 py-1">{{ $signal->distributions->sum('deliveries_count') }} deliveries</span></div>
                </div>
            </article>

            <article class="ui-panel overflow-hidden">
                <div class="border-b border-border px-5 py-4"><p class="ui-kicker">Control plane</p><h2 class="mt-1 text-base font-semibold">Admin actions</h2></div>
                <div class="space-y-4 p-5">
                    @if(in_array($signal->status, ['published','active'], true))
                        <div class="rounded-xl border border-border p-4"><div class="flex items-start gap-3"><div class="ui-metric-icon"><i data-lucide="users" class="h-5 w-5"></i></div><div><p class="text-sm font-semibold">Membership distribution</p><p class="mt-1 text-xs leading-5 text-muted-foreground">Deliver to active customers whose current membership exposes <code>signals.access</code>. S6 will add quotas and instrument restrictions.</p></div></div><form method="POST" action="{{ route('admin.signals.distribute', $signal) }}" class="mt-3">@csrf<button class="ui-btn ui-btn-primary w-full justify-center"><i data-lucide="send" class="h-4 w-4"></i>Distribute to eligible members</button></form></div>

                        <div class="rounded-xl border border-border p-4"><div class="flex items-start gap-3"><div class="ui-metric-icon"><i data-lucide="gift" class="h-5 w-5"></i></div><div><p class="text-sm font-semibold">Complimentary distribution</p><p class="mt-1 text-xs leading-5 text-muted-foreground">Admin override. Delivery is audited and does not consume normal membership allowance.</p></div></div><form method="POST" action="{{ route('admin.signals.complimentary', $signal) }}" class="mt-3 space-y-3">@csrf<div class="grid gap-3 sm:grid-cols-2"><select name="scope" class="ui-input w-full"><option value="all">All active customers</option><option value="specific">Specific customer</option></select><select name="user_id" class="ui-input w-full"><option value="">Select customer if specific</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }} — {{ $customer->email }}</option>@endforeach</select></div><input name="reason" class="ui-input w-full" placeholder="Reason / promotion note (optional)"><button class="ui-btn ui-btn-secondary w-full justify-center"><i data-lucide="gift" class="h-4 w-4"></i>Send complimentary</button></form></div>
                    @elseif($signal->status === 'ready')
                        <div class="rounded-xl border border-amber-500/20 bg-amber-500/[.06] p-4"><p class="text-sm font-semibold">Ready, not public</p><p class="mt-1 text-xs leading-5 text-muted-foreground">Publish this Signal before distribution. Ready Signals remain private to the admin desk.</p></div>
                    @else
                        <div class="rounded-xl border border-border bg-muted/20 p-4"><p class="text-sm font-semibold">Terminal Signal</p><p class="mt-1 text-xs leading-5 text-muted-foreground">Distribution and contract mutation are locked because this Signal has left the open lifecycle.</p></div>
                    @endif

                    @if(in_array($signal->status, \App\Models\Signal::OPEN_STATUSES, true))
                        <form method="POST" action="{{ route('admin.signals.cancel', $signal) }}" class="rounded-xl border border-red-500/20 bg-red-500/[.04] p-4" onsubmit="return confirm('Cancel this Signal?');">@csrf<label class="ui-label">Cancel reason</label><input name="reason" class="ui-input mt-2 w-full" placeholder="Optional audit reason"><button class="ui-btn mt-3 w-full justify-center border border-red-500/25 bg-red-500/10 text-red-600"><i data-lucide="ban" class="h-4 w-4"></i>Cancel Signal</button></form>
                    @endif
                    @if(in_array($signal->status, ['published','active'], true))
                        <form method="POST" action="{{ route('admin.signals.close', $signal) }}" class="rounded-xl border border-border p-4" onsubmit="return confirm('Manually close this Signal?');">@csrf<label class="ui-label">Close reason</label><input name="reason" class="ui-input mt-2 w-full" placeholder="Optional audit reason"><button class="ui-btn ui-btn-secondary mt-3 w-full justify-center"><i data-lucide="circle-stop" class="h-4 w-4"></i>Close Signal</button></form>
                    @endif
                </div>
            </article>
        </section>

        @if(in_array($signal->status, ['ready','published'], true))
            <details class="ui-panel group overflow-hidden">
                <summary class="flex cursor-pointer items-center justify-between px-5 py-4"><div><p class="ui-kicker">Immutable editing</p><h2 class="mt-1 text-base font-semibold">Edit Signal contract</h2></div><i data-lucide="chevron-down" class="h-4 w-4 transition-transform group-open:rotate-180"></i></summary>
                <div class="border-t border-border p-5">
                    <p class="mb-4 text-xs leading-5 text-muted-foreground">Every material edit creates a numbered revision. Published direction and timeframe are locked; active Signals cannot be manually edited here.</p>
                    <form method="POST" action="{{ route('admin.signals.update', $signal) }}" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">@csrf @method('PATCH')
                        <div><label class="ui-label">Direction</label><select name="direction" class="ui-input w-full"><option value="buy" @selected($signal->direction==='buy')>BUY</option><option value="sell" @selected($signal->direction==='sell')>SELL</option></select></div>
                        <div><label class="ui-label">Timeframe</label><select name="timeframe" class="ui-input w-full">@foreach(['5m','15m','1h','4h','1d','1w'] as $tf)<option value="{{ $tf }}" @selected($signal->timeframe===$tf)>{{ strtoupper($tf) }}</option>@endforeach</select></div>
                        <div><label class="ui-label">Strength</label><select name="strength" class="ui-input w-full">@foreach(['moderate','strong','very_strong'] as $strength)<option value="{{ $strength }}" @selected($signal->strength===$strength)>{{ strtoupper(str_replace('_',' ',$strength)) }}</option>@endforeach</select></div>
                        <div><label class="ui-label">Confluence %</label><input name="confluence_score" type="number" min="0" max="100" step="0.01" class="ui-input w-full" value="{{ $signal->confluence_score }}"></div>
                        <div><label class="ui-label">Entry min</label><input name="entry_min" type="number" min="0" step="0.00000001" class="ui-input w-full" value="{{ $signal->entry_min }}" required></div>
                        <div><label class="ui-label">Entry max</label><input name="entry_max" type="number" min="0" step="0.00000001" class="ui-input w-full" value="{{ $signal->entry_max }}" required></div>
                        <div><label class="ui-label">Stop loss</label><input name="stop_loss" type="number" min="0" step="0.00000001" class="ui-input w-full" value="{{ $signal->stop_loss }}" required></div>
                        <div><label class="ui-label">Expiry</label><input name="expires_at" type="datetime-local" class="ui-input w-full" value="{{ $signal->expires_at?->format('Y-m-d\\TH:i') }}" required></div>
                        <div><label class="ui-label">TP1</label><input name="tp1" type="number" min="0" step="0.00000001" class="ui-input w-full" value="{{ $tp1 }}" required></div>
                        <div><label class="ui-label">TP2</label><input name="tp2" type="number" min="0" step="0.00000001" class="ui-input w-full" value="{{ $tp2 }}"></div>
                        <div><label class="ui-label">TP3</label><input name="tp3" type="number" min="0" step="0.00000001" class="ui-input w-full" value="{{ $tp3 }}"></div>
                        <div><label class="ui-label">Revision reason</label><input name="revision_reason" class="ui-input w-full" placeholder="Why are terms changing?"></div>
                        <div class="md:col-span-2 lg:col-span-3"><label class="ui-label">Rationale</label><textarea name="rationale" rows="3" class="ui-input w-full">{{ $signal->rationale }}</textarea></div>
                        <div class="md:col-span-2 lg:col-span-3 flex justify-end"><button class="ui-btn ui-btn-primary"><i data-lucide="save" class="h-4 w-4"></i>Save as revision</button></div>
                    </form>
                </div>
            </details>
        @endif

        <section class="grid gap-4 lg:grid-cols-3">
            <article class="ui-panel overflow-hidden"><div class="border-b border-border px-5 py-4"><p class="ui-kicker">Analysis</p><h2 class="mt-1 font-semibold">Runs</h2></div><div class="max-h-[520px] divide-y divide-border overflow-y-auto">@forelse($signal->analysisRuns as $run)<div class="px-5 py-3.5"><div class="flex justify-between gap-3"><div><p class="text-xs font-semibold uppercase">{{ str_replace('_',' ', $run->result) }}</p><p class="mt-1 text-[10px] text-muted-foreground">{{ $run->trigger }} · {{ $run->timeframe }}</p></div><div class="text-right"><p class="text-xs font-semibold">{{ $run->confluence_score !== null ? number_format((float)$run->confluence_score,2).'%' : '—' }}</p><p class="mt-1 text-[10px] text-muted-foreground">{{ $run->analyzed_at?->diffForHumans() }}</p></div></div></div>@empty<div class="p-8 text-center text-xs text-muted-foreground">No analysis runs.</div>@endforelse</div></article>

            <article class="ui-panel overflow-hidden"><div class="border-b border-border px-5 py-4"><p class="ui-kicker">Revisions</p><h2 class="mt-1 font-semibold">Immutable change log</h2></div><div class="max-h-[520px] divide-y divide-border overflow-y-auto">@forelse($signal->revisions as $revision)<div class="px-5 py-3.5"><div class="flex justify-between gap-3"><div><p class="text-xs font-semibold">Revision #{{ $revision->revision_number }}</p><p class="mt-1 text-[10px] uppercase text-muted-foreground">{{ str_replace('_',' ', $revision->change_source) }}</p></div><p class="text-[10px] text-muted-foreground">{{ $revision->created_at?->diffForHumans() }}</p></div><p class="mt-2 text-xs leading-5 text-muted-foreground">{{ $revision->reason }}</p></div>@empty<div class="p-8 text-center text-xs text-muted-foreground">No revisions. Original contract remains intact.</div>@endforelse</div></article>

            <article class="ui-panel overflow-hidden"><div class="flex items-center justify-between gap-3 border-b border-border px-5 py-4"><div><p class="ui-kicker">Distribution</p><h2 class="mt-1 font-semibold">Batches</h2></div><a href="{{ route('admin.signals.recipients', ['signal_id' => $signal->id]) }}" class="ui-btn ui-btn-secondary ui-btn-sm"><i data-lucide="users-round" class="h-3.5 w-3.5"></i>{{ number_format($signal->deliveries_count) }} recipients</a></div><div class="max-h-[520px] divide-y divide-border overflow-y-auto">@forelse($signal->distributions as $distribution)<div class="px-5 py-3.5"><div class="flex justify-between gap-3"><div><p class="text-xs font-semibold uppercase">{{ $distribution->mode }}</p><p class="mt-1 text-[10px] text-muted-foreground">{{ $distribution->initiatedBy?->name ?? 'System' }} · {{ $distribution->created_at?->diffForHumans() }}</p></div><div class="text-right"><p class="text-xs font-semibold">{{ $distribution->delivered_count }} delivered</p><p class="mt-1 text-[10px] text-muted-foreground">{{ $distribution->skipped_count }} skipped</p></div></div></div>@empty<div class="p-8 text-center text-xs text-muted-foreground">No distribution batches.</div>@endforelse</div></article>
        </section>

        <section class="ui-panel overflow-hidden"><div class="border-b border-border px-5 py-4"><p class="ui-kicker">Lifecycle audit</p><h2 class="mt-1 font-semibold">Signal events</h2></div><div class="divide-y divide-border">@forelse($signal->events as $event)<div class="grid gap-2 px-5 py-3.5 sm:grid-cols-[1fr_auto] sm:items-center"><div class="flex items-center gap-3"><div class="flex h-8 w-8 items-center justify-center rounded-lg bg-muted"><i data-lucide="git-commit-horizontal" class="h-4 w-4"></i></div><div><p class="text-xs font-semibold uppercase">{{ str_replace('_',' ', $event->type) }}</p><p class="mt-1 text-[10px] text-muted-foreground">{{ $event->actor?->name ?? 'System' }}</p></div></div><p class="text-[10px] text-muted-foreground">{{ $event->occurred_at?->format('M j, Y g:i:s A') }}</p></div>@empty<div class="p-8 text-center text-xs text-muted-foreground">No lifecycle events.</div>@endforelse</div></section>
    </div>

    <script>
        (() => {
            if (window.__signalRoomCopyBound) return;
            window.__signalRoomCopyBound = true;

            const fallbackCopy = (value) => {
                const area = document.createElement('textarea');
                area.value = value;
                area.setAttribute('readonly', '');
                area.style.position = 'fixed';
                area.style.opacity = '0';
                document.body.appendChild(area);
                area.select();
                document.execCommand('copy');
                area.remove();
            };

            document.addEventListener('click', async (event) => {
                const button = event.target.closest('[data-copy-signal-value]');
                if (!button) return;

                const value = button.dataset.copySignalValue || '';
                if (!value) return;

                try {
                    if (navigator.clipboard?.writeText) {
                        await navigator.clipboard.writeText(value);
                    } else {
                        fallbackCopy(value);
                    }
                } catch (_) {
                    fallbackCopy(value);
                }

                const label = button.querySelector('[data-copy-label]');
                const previous = label?.textContent;
                if (label) label.textContent = 'Copied';
                button.classList.add('text-emerald-600');
                window.setTimeout(() => {
                    if (label && previous) label.textContent = previous;
                    button.classList.remove('text-emerald-600');
                }, 1200);
            });
        })();
    </script>
</x-admin-layout>
