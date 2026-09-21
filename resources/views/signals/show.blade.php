<x-user-layout>
    <x-slot name="header">{{ localize('ui.signals.detail.header', ':symbol Signal', ['symbol' => $signal->instrument_symbol]) }}</x-slot>

    @php
        $precision = $signal->price_precision;
        $targets = $signal->targets->sortBy('sequence')->values();
        $distributionScope = data_get($delivery->metadata, 'audience_scope', 'individual');
        $distributionReason = data_get($delivery->metadata, 'distribution_reason');
        $timingToneClasses = match(data_get($timing, 'entry.tone')) {
            'positive' => 'bg-emerald-500/10 text-emerald-600 ring-emerald-500/15 dark:text-emerald-400',
            'info' => 'bg-sky-500/10 text-sky-700 ring-sky-500/15 dark:text-sky-400',
            'warning' => 'bg-amber-500/10 text-amber-700 ring-amber-500/15 dark:text-amber-400',
            'negative' => 'bg-red-500/10 text-red-600 ring-red-500/15 dark:text-red-400',
            default => 'bg-muted text-muted-foreground ring-border',
        };
        $timingState = (string) data_get($timing, 'entry.state', 'inactive');
        $timingIcon = match($timingState) {
            'early' => 'clock-3',
            'optimal' => 'badge-check',
            'moderate' => 'triangle-alert',
            'too_late' => 'clock-alert',
            'dangerous' => 'shield-alert',
            'inactive' => 'circle-off',
            default => 'clock-3',
        };
        $timingStateLabel = match($timingState) {
            'early' => localize('ui.signals.detail.early', 'Early'),
            'optimal' => localize('ui.signals.detail.optimal', 'Optimal'),
            'moderate' => localize('ui.signals.detail.moderate', 'Moderate'),
            'too_late' => localize('ui.signals.detail.too_late', 'Too late'),
            'dangerous' => localize('ui.signals.detail.danger', 'Danger'),
            'inactive' => localize('ui.signals.detail.inactive', 'Inactive'),
            default => localize('ui.signals.detail.timing_unavailable', 'Timing unavailable'),
        };
        $timingStateDescription = match($timingState) {
            'early' => localize('ui.signals.detail.desc_early', 'Price has not reached the preferred entry zone yet.'),
            'optimal' => localize('ui.signals.detail.desc_optimal', 'Price is inside the preferred entry zone.'),
            'moderate' => localize('ui.signals.detail.desc_moderate', 'Price is outside the ideal zone but remains within the accepted tolerance.'),
            'too_late' => localize('ui.signals.detail.desc_too_late', 'Price has moved beyond a reasonable fresh-entry range.'),
            'dangerous' => localize('ui.signals.detail.desc_danger', 'A fresh entry is outside the original risk or session contract.'),
            'inactive' => localize('ui.signals.detail.desc_inactive', 'This Signal is not currently available for a fresh entry.'),
            default => localize('ui.signals.detail.timing_unavailable', 'Timing unavailable'),
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
                    <p class="mt-2 text-[11px] text-muted-foreground">{{ localize('ui.signals.detail.delivered_line', 'Delivered :time · :reason · :scope', ['time' => optional($delivery->delivered_at)->format('M d, Y · H:i'), 'reason' => ucfirst((string)$delivery->reason), 'scope' => ucfirst((string)$distributionScope)]) }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">@include('signals._nav')</div>
            </div>
        </section>

        @include('signals._instrument_chart', [
            'signal' => $signal,
            'analysis' => $analysis,
            'chartHeight' => 'h-[280px] md:h-[360px]',
        ])

        <section class="ui-panel overflow-hidden" data-signal-timing data-timing-layout="chart-first">
            <div class="flex flex-col gap-4 border-b border-border px-5 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <p class="ui-kicker">{{ localize('ui.signals.detail.entry_timing', 'Entry timing') }}</p>
                    <h2 class="mt-1 text-lg font-semibold">{{ localize('ui.signals.detail.execution_timing', 'Execution timing') }}</h2>
                    <p class="mt-1 max-w-3xl text-xs leading-5 text-muted-foreground">{{ localize('ui.signals.detail.timing_lead', 'Read the setup after the chart: where price sits now, where a fresh entry remains acceptable, and when the original risk contract is no longer worth chasing.') }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                    <div class="rounded-xl border border-border bg-muted/15 px-3 py-2">
                        <p class="text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">{{ financial_term('market_price') }}</p>
                        <p class="mt-1 text-sm font-semibold tabular-nums">{{ data_get($timing, 'current_price') > 0 ? number_format((float)data_get($timing, 'current_price'), $precision) : '—' }}</p>
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-[10px] font-bold uppercase tracking-[.1em] ring-1 {{ $timingToneClasses }}">
                        <i data-lucide="{{ $timingIcon }}" class="h-3.5 w-3.5"></i>
                        {{ $timingStateLabel }}
                    </span>
                </div>
            </div>

            <div class="grid gap-px bg-border/70 xl:grid-cols-[.9fr_1.35fr_.95fr]">
                <article class="bg-background p-5 sm:p-6">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1 {{ $timingToneClasses }}">
                            <i data-lucide="{{ $timingIcon }}" class="h-4 w-4"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="ui-label">{{ localize('ui.signals.detail.current_assessment', 'Current assessment') }}</p>
                            <p class="mt-1 text-xl font-semibold">{{ $timingStateLabel }}</p>
                        </div>
                    </div>
                    <p class="mt-4 text-xs leading-5 text-muted-foreground">{{ $timingStateDescription }}</p>
                    <div class="mt-4 rounded-xl border border-border bg-muted/15 px-3.5 py-3">
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <span class="text-muted-foreground">{{ localize('ui.signals.detail.signal_timeframe', 'Signal timeframe') }}</span>
                            <strong>{{ data_get($timing, 'best_timeframe', strtoupper((string)$signal->timeframe)) }}</strong>
                        </div>
                    </div>
                </article>

                <article class="bg-background p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="ui-label">{{ localize('ui.signals.detail.fresh_entry_map', 'Fresh-entry map') }}</p>
                            <h3 class="mt-1 text-sm font-semibold">{{ localize('ui.signals.detail.price_zones', 'Price zones') }}</h3>
                        </div>
                        <span class="rounded-full border border-border bg-muted/20 px-2.5 py-1 text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">{{ strtoupper((string)$signal->direction) }}</span>
                    </div>
                    <div class="mt-4 grid gap-2 sm:grid-cols-2" data-entry-timing-spectrum>
                        <div @class(['rounded-xl border p-3', 'border-sky-500/30 bg-sky-500/[.06]' => data_get($timing, 'entry.state') === 'early', 'border-border bg-muted/10' => data_get($timing, 'entry.state') !== 'early'])>
                            <div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-sky-500"></span><span class="text-[9px] font-bold uppercase tracking-[.12em] text-sky-700 dark:text-sky-400">{{ localize('ui.signals.detail.early', 'Early') }}</span></div>
                            <p class="mt-2 text-xs font-semibold leading-5">{{ data_get($timing, 'early.label') }}</p>
                        </div>
                        <div @class(['rounded-xl border p-3', 'border-emerald-500/30 bg-emerald-500/[.06]' => data_get($timing, 'entry.state') === 'optimal', 'border-border bg-muted/10' => data_get($timing, 'entry.state') !== 'optimal'])>
                            <div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-emerald-500"></span><span class="text-[9px] font-bold uppercase tracking-[.12em] text-emerald-600 dark:text-emerald-400">{{ localize('ui.signals.detail.optimal', 'Optimal') }}</span></div>
                            <p class="mt-2 text-xs font-semibold tabular-nums">{{ data_get($timing, 'optimal_range.label') }}</p>
                        </div>
                        <div @class(['rounded-xl border p-3', 'border-amber-500/30 bg-amber-500/[.06]' => data_get($timing, 'entry.state') === 'moderate', 'border-border bg-muted/10' => data_get($timing, 'entry.state') !== 'moderate'])>
                            <div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-500"></span><span class="text-[9px] font-bold uppercase tracking-[.12em] text-amber-700 dark:text-amber-400">{{ localize('ui.signals.detail.moderate', 'Moderate') }}</span></div>
                            <p class="mt-2 text-xs font-semibold tabular-nums">{{ data_get($timing, 'moderate_range.label') }}</p>
                        </div>
                        <div @class(['rounded-xl border p-3', 'border-rose-500/30 bg-rose-500/[.06]' => data_get($timing, 'entry.state') === 'too_late', 'border-border bg-muted/10' => data_get($timing, 'entry.state') !== 'too_late'])>
                            <div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-rose-500"></span><span class="text-[9px] font-bold uppercase tracking-[.12em] text-rose-600 dark:text-rose-400">{{ localize('ui.signals.detail.too_late', 'Too late') }}</span></div>
                            <p class="mt-2 text-xs font-semibold leading-5">{{ data_get($timing, 'too_late.label') }}</p>
                        </div>
                        <div @class(['rounded-xl border p-3 sm:col-span-2', 'border-red-500/30 bg-red-500/[.06]' => data_get($timing, 'entry.state') === 'dangerous', 'border-border bg-muted/10' => data_get($timing, 'entry.state') !== 'dangerous'])>
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-red-500"></span><span class="text-[9px] font-bold uppercase tracking-[.12em] text-red-600 dark:text-red-400">{{ localize('ui.signals.detail.danger', 'Danger') }}</span></div>
                                <p class="text-xs font-semibold leading-5 sm:text-right">{{ data_get($timing, 'danger.label') }}</p>
                            </div>
                        </div>
                    </div>
                    <p class="mt-3 text-[10px] leading-4 text-muted-foreground">{{ localize('ui.signals.detail.timing_legend', 'Early: wait. Optimal: preferred entry zone. Moderate: still tolerable. Too late: do not chase. Danger: the original stop/session contract is no longer safe for a fresh entry.') }}</p>
                </article>

                <article class="bg-background p-5 sm:p-6">
                    <p class="ui-label">{{ localize('ui.signals.detail.market_context', 'Market context') }}</p>
                    <h3 class="mt-1 text-sm font-semibold">{{ localize('ui.signals.detail.session_intelligence', 'Session intelligence') }}</h3>
                    <dl class="mt-4 divide-y divide-border rounded-xl border border-border bg-muted/10 text-xs">
                        <div class="flex items-start justify-between gap-4 px-3.5 py-3"><dt class="text-muted-foreground">{{ localize('ui.signals.detail.best_session', 'Best session') }}</dt><dd class="max-w-[12rem] text-right font-semibold">{{ data_get($timing, 'session.best') }}</dd></div>
                        <div class="flex items-start justify-between gap-4 px-3.5 py-3"><dt class="text-muted-foreground">{{ localize('ui.signals.detail.window', 'Window') }}</dt><dd class="max-w-[12rem] text-right font-semibold tabular-nums">{{ data_get($timing, 'session.window') }}</dd></div>
                        <div class="flex items-start justify-between gap-4 px-3.5 py-3"><dt class="text-muted-foreground">{{ localize('ui.signals.detail.now', 'Now') }}</dt><dd class="max-w-[12rem] text-right font-semibold">{{ data_get($timing, 'session.label') }}</dd></div>
                        <div class="flex items-start justify-between gap-4 px-3.5 py-3"><dt class="text-muted-foreground">{{ localize('ui.signals.detail.market_clock', 'Market clock') }}</dt><dd class="text-right font-semibold tabular-nums">{{ data_get($timing, 'session.market_time') }} {{ data_get($timing, 'session.timezone') }}</dd></div>
                    </dl>
                </article>
            </div>

            <div class="border-t border-border bg-muted/15 px-5 py-3 text-[10px] leading-4 text-muted-foreground sm:px-6">{{ data_get($timing, 'method') }} {{ localize('ui.signals.detail.timing_disclaimer', 'Timing describes execution context, not guaranteed profitability.') }}</div>
        </section>

        <section class="grid gap-4 lg:grid-cols-3">
            <article class="ui-panel overflow-hidden">
                <div class="border-b border-border px-5 py-4"><p class="ui-kicker">{{ localize('ui.signals.detail.entry', 'Entry') }}</p><h2 class="mt-1 font-semibold">{{ localize('ui.signals.detail.entry_zone', 'Entry zone') }}</h2></div>
                <div class="divide-y divide-border">
                    @foreach([[localize('ui.signals.detail.entry_minimum', 'Entry minimum'),$signal->entry_min],[localize('ui.signals.detail.entry_maximum', 'Entry maximum'),$signal->entry_max]] as [$label,$value])
                        <div class="flex items-center justify-between gap-4 px-5 py-4"><div><p class="ui-label">{{ $label }}</p><p class="mt-1 text-lg font-semibold tabular-nums">{{ number_format((float)$value,2) }}</p></div><button type="button" data-copy-signal-value="{{ $value }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border text-muted-foreground hover:bg-muted hover:text-foreground" title="{{ localize('ui.signals.detail.copy_value', 'Copy :label', ['label' => $label]) }}"><i data-lucide="copy" class="h-3.5 w-3.5"></i></button></div>
                    @endforeach
                </div>
            </article>

            <article class="ui-panel overflow-hidden">
                <div class="border-b border-border px-5 py-4"><p class="ui-kicker">{{ localize('ui.signals.detail.risk_quality', 'Risk & quality') }}</p><h2 class="mt-1 font-semibold">{{ localize('ui.signals.detail.protection_strength', 'Protection and strength') }}</h2></div>
                <div class="divide-y divide-border">
                    <div class="flex items-center justify-between gap-4 px-5 py-3"><span class="text-xs text-muted-foreground">{{ financial_term('stop_loss') }}</span><span class="flex items-center gap-2"><strong class="text-sm tabular-nums">{{ number_format((float)$signal->stop_loss,$precision) }}</strong><button type="button" data-copy-signal-value="{{ $signal->stop_loss }}" class="text-muted-foreground hover:text-foreground"><i data-lucide="copy" class="h-3.5 w-3.5"></i></button></span></div>
                    <div class="flex items-center justify-between gap-4 px-5 py-3"><span class="text-xs text-muted-foreground">{{ financial_term('risk_reward') }}</span><strong class="text-sm tabular-nums">1 : {{ rtrim(rtrim(number_format((float)$signal->risk_reward,2,'.',''),'0'),'.') }}</strong></div>
                    <div class="flex items-center justify-between gap-4 px-5 py-3"><span class="text-xs text-muted-foreground">{{ localize('ui.signals.detail.strength', 'Strength') }}</span><strong class="text-sm">{{ str_replace('_',' ', strtoupper((string)$signal->strength)) }}</strong></div>
                    <div class="flex items-center justify-between gap-4 px-5 py-3"><span class="text-xs text-muted-foreground">{{ localize('ui.signals.detail.confluence', 'Confluence') }}</span><strong class="text-sm tabular-nums">{{ number_format((float)$signal->confluence_score,2) }}%</strong></div>
                </div>
            </article>

            <article class="ui-panel overflow-hidden">
                <div class="border-b border-border px-5 py-4"><p class="ui-kicker">{{ localize('ui.signals.detail.targets', 'Targets') }}</p><h2 class="mt-1 font-semibold">{{ localize('ui.signals.detail.profit_targets', 'Profit targets') }}</h2></div>
                <div class="divide-y divide-border">
                    @foreach($targets as $target)
                        <div class="flex items-center justify-between gap-4 px-5 py-3">
                            <div><p class="text-xs font-medium">{{ localize('ui.signals.detail.target', 'Target :number', ['number' => $target->sequence]) }}</p><p class="mt-0.5 text-[10px] uppercase tracking-[.1em] text-muted-foreground">{{ strtoupper((string)$target->status) }}</p></div>
                            <span class="flex items-center gap-2"><strong class="text-sm tabular-nums">{{ number_format((float)$target->price,$precision) }}</strong><button type="button" data-copy-signal-value="{{ $target->price }}" class="text-muted-foreground hover:text-foreground"><i data-lucide="copy" class="h-3.5 w-3.5"></i></button></span>
                        </div>
                    @endforeach
                </div>
            </article>
        </section>

        <section class="grid gap-4 lg:grid-cols-[1.2fr_.8fr]">
            <article class="ui-panel p-5 sm:p-6">
                <p class="ui-kicker">{{ localize('ui.signals.detail.analysis_rationale', 'Analysis rationale') }}</p>
                <h2 class="mt-1 text-lg font-semibold">{{ localize('ui.signals.detail.why_qualified', 'Why this Signal was qualified') }}</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-muted-foreground">{{ $signal->rationale ?: localize('ui.signals.detail.no_rationale', 'No additional rationale was recorded for this Signal.') }}</p>
            </article>

            <article class="ui-panel p-5 sm:p-6">
                <p class="ui-kicker">{{ localize('ui.signals.detail.delivery', 'Delivery') }}</p>
                <h2 class="mt-1 text-lg font-semibold">{{ localize('ui.signals.detail.access_record', 'Your access record') }}</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-muted-foreground">{{ localize('ui.signals.detail.delivery', 'Delivery') }}</dt><dd class="font-medium">{{ ucfirst((string)$delivery->reason) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted-foreground">{{ localize('ui.signals.detail.audience', 'Audience') }}</dt><dd class="font-medium">{{ ucfirst((string)$distributionScope) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted-foreground">{{ localize('ui.signals.detail.delivered', 'Delivered') }}</dt><dd class="text-right font-medium">{{ optional($delivery->delivered_at)->format('M d, Y · H:i') }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted-foreground">{{ localize('ui.signals.detail.opened', 'Opened') }}</dt><dd class="text-right font-medium">{{ optional($delivery->read_at)->format('M d, Y · H:i') }}</dd></div>
                </dl>
                @if($distributionReason)<p class="mt-4 rounded-xl border border-border bg-muted/20 p-3 text-xs leading-5 text-muted-foreground">{{ $distributionReason }}</p>@endif
            </article>
        </section>

        <section class="ui-panel overflow-hidden" data-signal-update-timeline>
            <div class="flex flex-col gap-2 border-b border-border px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <p class="ui-kicker">{{ localize('ui.signals.detail.lifecycle_record', 'Lifecycle record') }}</p>
                    <h2 class="mt-1 text-lg font-semibold">{{ localize('ui.signals.detail.signal_updates', 'Signal updates') }}</h2>
                    <p class="mt-1 text-xs text-muted-foreground">{{ localize('ui.signals.detail.timeline_help', 'A readable view of the immutable Signal event and revision history.') }}</p>
                </div>
                <span class="self-start rounded-full border border-border bg-muted/20 px-2.5 py-1 text-[9px] font-semibold uppercase tracking-[.1em] text-muted-foreground">{{ $timeline->count() }} {{ $timeline->count() === 1 ? localize('ui.trading.positions.event', 'event') : localize('ui.trading.positions.events', 'events') }}</span>
            </div>

            @if($timeline->isEmpty())
                <div class="px-5 py-6 text-sm text-muted-foreground sm:px-6">{{ localize('ui.signals.detail.no_lifecycle', 'No lifecycle events have been recorded yet.') }}</div>
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

        <div class="rounded-xl border border-border bg-muted/20 px-4 py-3 text-[11px] leading-5 text-muted-foreground">{{ localize('ui.signals.detail.disclaimer', "Signal strength and confluence describe the analysis engine's evidence, not a guaranteed market outcome. Execution remains a separate trading action.") }}</div>
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
