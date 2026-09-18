<x-user-layout>
    <x-slot name="header">Signals</x-slot>

    <div class="mx-auto max-w-7xl space-y-4">
        <section class="ui-panel overflow-hidden">
            <div class="flex flex-col gap-4 px-5 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="ui-kicker">Signal intelligence</p>
                    <h1 class="mt-1 text-xl font-semibold text-foreground">Your current Signals</h1>
                    <p class="mt-1 max-w-2xl text-sm text-muted-foreground">Only Signals actually delivered to your account appear here. Published Signals are waiting for entry; Active Signals have entered their market lifecycle.</p>
                </div>
                @include('signals._nav')
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['Current', $summary['current'], 'radio-tower'],
                ['Active now', $summary['active'], 'activity'],
                ['Waiting entry', $summary['waiting'], 'clock-3'],
                ['Unread deliveries', $summary['unread_deliveries'], 'mail'],
            ] as [$label,$value,$icon])
                <article class="ui-metric-card">
                    <div class="ui-metric-icon"><i data-lucide="{{ $icon }}" class="h-5 w-5"></i></div>
                    <div><p class="ui-label">{{ $label }}</p><p class="mt-2 text-2xl font-semibold tabular-nums">{{ number_format($value) }}</p></div>
                </article>
            @endforeach
        </section>

        @if($deliveries->isEmpty())
            <section class="ui-panel">
                <div class="ui-empty-state py-16">
                    <div class="ui-empty-icon"><i data-lucide="radio-tower" class="h-5 w-5"></i></div>
                    <h2 class="font-medium text-foreground">No current Signals</h2>
                    <p class="mt-1 max-w-md text-sm text-muted-foreground">When a Signal is distributed to your account, it will appear here together with its entry, stop and targets.</p>
                </div>
            </section>
        @else
            <section class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                @foreach($deliveries as $delivery)
                    @php
                        $signal = $delivery->signal;
                        $instrument = $signal?->marketInstrument; $precision = $signal?->price_precision ?? 2;
                        $targets = $signal?->targets ?? collect();
                        $active = $signal?->status === 'active';
                    @endphp
                    <a href="{{ route('signals.show', $signal) }}" class="ui-panel group overflow-hidden transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex items-start justify-between gap-4 border-b border-border px-5 py-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-lg font-semibold">{{ $signal?->instrument_symbol ?? '—' }}</h2>
                                    <span class="rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase tracking-[.1em] {{ strtoupper((string)$signal->direction) === 'BUY' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-red-500/10 text-red-600 dark:text-red-400' }}">{{ strtoupper((string)$signal->direction) }}</span>
                                    <span class="rounded-full border border-border bg-muted/30 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-[.1em]">{{ strtoupper((string)$signal->status) }}</span>
                                </div>
                                <p class="mt-1 truncate text-xs text-muted-foreground">{{ $signal?->instrument_name }} · {{ strtoupper((string)$signal->marketplace) }} · {{ strtoupper((string)$signal->timeframe) }}</p>
                            </div>
                            @if(!$delivery->read_at)<span class="rounded-full bg-primary/10 px-2 py-1 text-[9px] font-semibold uppercase tracking-[.1em] text-primary">New</span>@endif
                        </div>

                        <div class="grid grid-cols-2 gap-px bg-border/70">
                            <div class="bg-background p-4"><p class="ui-label">Entry zone</p><p class="mt-1.5 text-sm font-semibold tabular-nums">{{ number_format((float)$signal->entry_min,$precision) }} – {{ number_format((float)$signal->entry_max,$precision) }}</p></div>
                            <div class="bg-background p-4"><p class="ui-label">Stop loss</p><p class="mt-1.5 text-sm font-semibold tabular-nums">{{ number_format((float)$signal->stop_loss,$precision) }}</p></div>
                            <div class="bg-background p-4"><p class="ui-label">Strength</p><p class="mt-1.5 text-sm font-semibold">{{ str_replace('_',' ', strtoupper((string)$signal->strength)) }}</p></div>
                            <div class="bg-background p-4"><p class="ui-label">Confluence</p><p class="mt-1.5 text-sm font-semibold tabular-nums">{{ number_format((float)$signal->confluence_score,2) }}%</p></div>
                        </div>

                        <div class="flex items-center justify-between gap-3 px-5 py-4">
                            <div class="flex min-w-0 flex-wrap gap-1.5">
                                @foreach($targets->take(3) as $target)
                                    <span class="rounded-lg border border-border bg-muted/20 px-2 py-1 text-[10px] font-medium tabular-nums">TP{{ $target->sequence }} {{ number_format((float)$target->price,$precision) }}</span>
                                @endforeach
                            </div>
                            <i data-lucide="arrow-up-right" class="h-4 w-4 shrink-0 text-muted-foreground transition group-hover:text-foreground"></i>
                        </div>
                    </a>
                @endforeach
            </section>

            @if($deliveries->hasPages())
                <div>{{ $deliveries->links() }}</div>
            @endif
        @endif
    </div>
</x-user-layout>
