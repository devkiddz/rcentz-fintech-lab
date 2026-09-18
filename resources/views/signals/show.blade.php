<x-user-layout>
    <x-slot name="header">{{ $signal->instrument_symbol }} Signal</x-slot>

    @php
        $precision = $signal->price_precision;
        $targets = $signal->targets->sortBy('sequence')->values();
        $distributionScope = data_get($delivery->metadata, 'audience_scope', 'individual');
        $distributionReason = data_get($delivery->metadata, 'distribution_reason');
        $timingToneClasses = match(data_get($timing, 'entry.tone')) {
            'positive' => 'bg-emerald-500/10 text-emerald-600 ring-emerald-500/15 dark:text-emerald-400',
            'warning' => 'bg-amber-500/10 text-amber-700 ring-amber-500/15 dark:text-amber-400',
            'negative' => 'bg-red-500/10 text-red-600 ring-red-500/15 dark:text-red-400',
            default => 'bg-muted text-muted-foreground ring-border',
        };
        $timingIcon = match(data_get($timing, 'entry.state')) {
            'optimal' => 'badge-check',
            'moderate' => 'triangle-alert',
            'dangerous' => 'shield-alert',
            'inactive' => 'circle-off',
            default => 'clock-3',
        };
    @endphp

    <div class="mx-auto max-w-7xl space-y-4" data-customer-signal-room>
        <section class="ui-panel overflow-hidden">
            <div class="flex flex-col gap-4 px-5 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-semibold tracking-tight">{{ $signal->instrument_symbol }}</h1>
                        <span class="rounded-full px-2.5 py-1 text-[9px] font-bold uppercase tracking-[.12em] {{ strtoupper((string)$signal->direction)==='BUY' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-red-500/10 text-red-600 dark:text-red-400' }}">{{ strtoupper((string)$signal->direction) }}</span>
                        <span class="rounded-full border border-border bg-muted/30 px-2.5 py-1 text-[9px] font-bold uppercase tracking-[.12em]">{{ strtoupper((string)$signal->status) }}</span>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">{{ $signal->instrument_name }} · {{ strtoupper((string)$signal->marketplace) }} · {{ strtoupper((string)$signal->timeframe) }}</p>
                    <p class="mt-2 text-[11px] text-muted-foreground">Delivered {{ optional($delivery->delivered_at)->format('M d, Y · H:i') }} · {{ ucfirst((string)$delivery->reason) }} · {{ ucfirst((string)$distributionScope) }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">@include('signals._nav')</div>
            </div>
        </section>

        <section class="ui-panel overflow-hidden" data-signal-timing>
            <div class="flex flex-col gap-3 border-b border-border px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <p class="ui-kicker">Timing intelligence</p>
                    <h2 class="mt-1 text-lg font-semibold">When this setup is safest to approach</h2>
                    <p class="mt-1 max-w-3xl text-xs leading-5 text-muted-foreground">Live timing is derived from the current price, the original entry contract, stop distance, first target and market session.</p>
                </div>
                <span class="inline-flex self-start items-center gap-2 rounded-full px-3 py-1.5 text-[10px] font-bold uppercase tracking-[.1em] ring-1 {{ $timingToneClasses }}">
                    <i data-lucide="{{ $timingIcon }}" class="h-3.5 w-3.5"></i>
                    {{ data_get($timing, 'entry.label', 'Timing unavailable') }}
                </span>
            </div>

            <div class="grid gap-px bg-border/70 lg:grid-cols-[1.15fr_1fr_1fr]">
                <article class="bg-background p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="ui-label">Current timing</p>
                            <p class="mt-2 text-xl font-semibold">{{ data_get($timing, 'entry.label', 'Unavailable') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="ui-label">Market price</p>
                            <p class="mt-2 text-lg font-semibold tabular-nums">{{ data_get($timing, 'current_price') > 0 ? number_format((float)data_get($timing, 'current_price'), 2) : '—' }}</p>
                        </div>
                    </div>
                    <p class="mt-3 text-xs leading-5 text-muted-foreground">{{ data_get($timing, 'entry.description') }}</p>
                </article>

                <article class="bg-background p-5 sm:p-6">
                    <p class="ui-label">Entry timing bands</p>
                    <dl class="mt-3 space-y-3 text-xs">
                        <div class="flex items-start justify-between gap-4"><dt class="text-muted-foreground">Optimal</dt><dd class="text-right font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">{{ data_get($timing, 'optimal_range.label') }}</dd></div>
                        <div class="flex items-start justify-between gap-4"><dt class="text-muted-foreground">Moderate tolerance</dt><dd class="text-right font-semibold tabular-nums text-amber-700 dark:text-amber-400">{{ data_get($timing, 'moderate_range.label') }}</dd></div>
                        <div class="flex items-start justify-between gap-4"><dt class="text-muted-foreground">Danger zone</dt><dd class="max-w-[13rem] text-right font-semibold text-red-600 dark:text-red-400">{{ data_get($timing, 'danger.label') }}</dd></div>
                    </dl>
                    <p class="mt-3 text-[10px] leading-4 text-muted-foreground">Moderate applies only outside the optimal entry zone while price remains inside the wider tolerance band.</p>
                </article>

                <article class="bg-background p-5 sm:p-6">
                    <p class="ui-label">Timeframe & session</p>
                    <dl class="mt-3 space-y-3 text-xs">
                        <div class="flex items-start justify-between gap-4"><dt class="text-muted-foreground">Best timeframe</dt><dd class="font-semibold">{{ data_get($timing, 'best_timeframe', strtoupper((string)$signal->timeframe)) }}</dd></div>
                        <div class="flex items-start justify-between gap-4"><dt class="text-muted-foreground">Best session</dt><dd class="text-right font-semibold">{{ data_get($timing, 'session.best') }}</dd></div>
                        <div class="flex items-start justify-between gap-4"><dt class="text-muted-foreground">Session window</dt><dd class="text-right font-semibold tabular-nums">{{ data_get($timing, 'session.window') }}</dd></div>
                        <div class="flex items-start justify-between gap-4"><dt class="text-muted-foreground">Current session</dt><dd class="text-right font-semibold">{{ data_get($timing, 'session.label') }} · {{ data_get($timing, 'session.market_time') }} {{ data_get($timing, 'session.timezone') }}</dd></div>
                    </dl>
                </article>
            </div>

            <div class="border-t border-border bg-muted/15 px-5 py-3 text-[10px] leading-4 text-muted-foreground sm:px-6">{{ data_get($timing, 'method') }} Timing describes execution context, not guaranteed profitability.</div>
        </section>

        @include('signals._instrument_chart', [
            'signal' => $signal,
            'analysis' => $analysis,
            'chartHeight' => 'h-[280px] md:h-[360px]',
        ])

        <section class="grid gap-4 lg:grid-cols-3">
            <article class="ui-panel overflow-hidden">
                <div class="border-b border-border px-5 py-4"><p class="ui-kicker">Entry</p><h2 class="mt-1 font-semibold">Entry zone</h2></div>
                <div class="divide-y divide-border">
                    @foreach([['Entry minimum',$signal->entry_min],['Entry maximum',$signal->entry_max]] as [$label,$value])
                        <div class="flex items-center justify-between gap-4 px-5 py-4"><div><p class="ui-label">{{ $label }}</p><p class="mt-1 text-lg font-semibold tabular-nums">{{ number_format((float)$value,2) }}</p></div><button type="button" data-copy-signal-value="{{ $value }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border text-muted-foreground hover:bg-muted hover:text-foreground" title="Copy {{ strtolower($label) }}"><i data-lucide="copy" class="h-3.5 w-3.5"></i></button></div>
                    @endforeach
                </div>
            </article>

            <article class="ui-panel overflow-hidden">
                <div class="border-b border-border px-5 py-4"><p class="ui-kicker">Risk & quality</p><h2 class="mt-1 font-semibold">Protection and strength</h2></div>
                <div class="divide-y divide-border">
                    <div class="flex items-center justify-between gap-4 px-5 py-3"><span class="text-xs text-muted-foreground">Stop loss</span><span class="flex items-center gap-2"><strong class="text-sm tabular-nums">{{ number_format((float)$signal->stop_loss,$precision) }}</strong><button type="button" data-copy-signal-value="{{ $signal->stop_loss }}" class="text-muted-foreground hover:text-foreground"><i data-lucide="copy" class="h-3.5 w-3.5"></i></button></span></div>
                    <div class="flex items-center justify-between gap-4 px-5 py-3"><span class="text-xs text-muted-foreground">Risk : Reward</span><strong class="text-sm tabular-nums">1 : {{ rtrim(rtrim(number_format((float)$signal->risk_reward,2,'.',''),'0'),'.') }}</strong></div>
                    <div class="flex items-center justify-between gap-4 px-5 py-3"><span class="text-xs text-muted-foreground">Strength</span><strong class="text-sm">{{ str_replace('_',' ', strtoupper((string)$signal->strength)) }}</strong></div>
                    <div class="flex items-center justify-between gap-4 px-5 py-3"><span class="text-xs text-muted-foreground">Confluence</span><strong class="text-sm tabular-nums">{{ number_format((float)$signal->confluence_score,2) }}%</strong></div>
                </div>
            </article>

            <article class="ui-panel overflow-hidden">
                <div class="border-b border-border px-5 py-4"><p class="ui-kicker">Targets</p><h2 class="mt-1 font-semibold">Profit targets</h2></div>
                <div class="divide-y divide-border">
                    @foreach($targets as $target)
                        <div class="flex items-center justify-between gap-4 px-5 py-3">
                            <div><p class="text-xs font-medium">Target {{ $target->sequence }}</p><p class="mt-0.5 text-[10px] uppercase tracking-[.1em] text-muted-foreground">{{ strtoupper((string)$target->status) }}</p></div>
                            <span class="flex items-center gap-2"><strong class="text-sm tabular-nums">{{ number_format((float)$target->price,$precision) }}</strong><button type="button" data-copy-signal-value="{{ $target->price }}" class="text-muted-foreground hover:text-foreground"><i data-lucide="copy" class="h-3.5 w-3.5"></i></button></span>
                        </div>
                    @endforeach
                </div>
            </article>
        </section>

        <section class="grid gap-4 lg:grid-cols-[1.2fr_.8fr]">
            <article class="ui-panel p-5 sm:p-6">
                <p class="ui-kicker">Analysis rationale</p>
                <h2 class="mt-1 text-lg font-semibold">Why this Signal was qualified</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-muted-foreground">{{ $signal->rationale ?: 'No additional rationale was recorded for this Signal.' }}</p>
            </article>

            <article class="ui-panel p-5 sm:p-6">
                <p class="ui-kicker">Delivery</p>
                <h2 class="mt-1 text-lg font-semibold">Your access record</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-muted-foreground">Delivery</dt><dd class="font-medium">{{ ucfirst((string)$delivery->reason) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted-foreground">Audience</dt><dd class="font-medium">{{ ucfirst((string)$distributionScope) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted-foreground">Delivered</dt><dd class="text-right font-medium">{{ optional($delivery->delivered_at)->format('M d, Y · H:i') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted-foreground">Opened</dt><dd class="text-right font-medium">{{ optional($delivery->read_at)->format('M d, Y · H:i') }}</dd></div>
                </dl>
                @if($distributionReason)<p class="mt-4 rounded-xl border border-border bg-muted/20 p-3 text-xs leading-5 text-muted-foreground">{{ $distributionReason }}</p>@endif
            </article>
        </section>

        <section class="ui-panel overflow-hidden" data-signal-update-timeline>
            <div class="flex flex-col gap-2 border-b border-border px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <p class="ui-kicker">Lifecycle record</p>
                    <h2 class="mt-1 text-lg font-semibold">Signal updates</h2>
                    <p class="mt-1 text-xs text-muted-foreground">A readable view of the immutable Signal event and revision history.</p>
                </div>
                <span class="self-start rounded-full border border-border bg-muted/20 px-2.5 py-1 text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">{{ $timeline->count() }} events</span>
            </div>

            @if($timeline->isEmpty())
                <div class="px-5 py-6 text-sm text-muted-foreground sm:px-6">No lifecycle events have been recorded yet.</div>
            @else
                <div class="divide-y divide-border">
                    @foreach($timeline as $item)
                        @php
                            $toneClasses = match($item['tone']) {
                                'positive' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
                                'negative' => 'bg-red-500/10 text-red-600 dark:text-red-400',
                                'warning' => 'bg-amber-500/10 text-amber-700 dark:text-amber-400',
                                default => 'bg-muted text-muted-foreground',
                            };
                        @endphp
                        <article class="flex gap-4 px-5 py-5 sm:px-6">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $toneClasses }}">
                                <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-sm font-semibold text-foreground">{{ $item['title'] }}</h3>
                                            @if($item['badge'])
                                                <span class="rounded-full border border-border bg-muted/20 px-2 py-0.5 text-[8px] font-semibold uppercase tracking-[.08em] text-muted-foreground">{{ $item['badge'] }}</span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-xs leading-5 text-muted-foreground">{{ $item['summary'] }}</p>
                                    </div>
                                    <time class="shrink-0 text-[10px] font-medium text-muted-foreground">{{ optional($item['occurred_at'])->format('M d, Y · H:i') }}</time>
                                </div>

                                @if(!empty($item['changes']))
                                    <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                        @foreach($item['changes'] as $change)
                                            <div class="rounded-xl border border-border bg-muted/15 px-3 py-2.5">
                                                <p class="text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">{{ $change['label'] }}</p>
                                                <p class="mt-1 text-[11px] font-medium text-foreground">
                                                    <span class="text-muted-foreground line-through decoration-border">{{ $change['before'] }}</span>
                                                    <span class="mx-1 text-muted-foreground">→</span>
                                                    <span>{{ $change['after'] }}</span>
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <div class="rounded-xl border border-border bg-muted/20 px-4 py-3 text-[11px] leading-5 text-muted-foreground">Signal strength and confluence describe the analysis engine's evidence, not a guaranteed market outcome. Execution remains a separate trading action.</div>
    </div>

    <script>
        (() => {
            if (window.__customerSignalCopyBound) return;
            window.__customerSignalCopyBound = true;
            document.addEventListener('click', async (event) => {
                const button = event.target.closest('[data-copy-signal-value]');
                if (!button) return;
                const value = button.dataset.copySignalValue || '';
                try {
                    await navigator.clipboard.writeText(value);
                    const icon = button.querySelector('[data-lucide]');
                    if (icon) icon.setAttribute('data-lucide', 'check');
                    window.lucide?.createIcons();
                    window.setTimeout(() => { if (icon) icon.setAttribute('data-lucide', 'copy'); window.lucide?.createIcons(); }, 1200);
                } catch (_) {}
            });
        })();
    </script>
</x-user-layout>
