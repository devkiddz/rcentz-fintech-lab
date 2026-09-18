<x-admin-layout>
    <div class="ui-page max-w-[1500px] space-y-4">
        <section class="ui-panel p-5 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-border bg-muted/40"><i data-lucide="{{ $icon }}" class="h-5 w-5"></i></div>
                    <div><p class="ui-kicker">Signal Engine</p><h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $title }}</h1><p class="mt-1 text-sm text-muted-foreground">{{ $description }}</p></div>
                </div>
                <a href="{{ route('admin.signals.index') }}" class="ui-btn ui-btn-secondary"><i data-lucide="arrow-left" class="h-4 w-4"></i>Signal Desk</a>
            </div>
        </section>

        @include('admin.signals._nav')

        <section class="grid gap-4 xl:grid-cols-2">
            @forelse($signals as $signal)
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

                <article class="ui-panel overflow-hidden">
                    <div class="p-5 sm:p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-border bg-muted/40">
                                    <i data-lucide="{{ $signal->direction === 'sell' ? 'trending-down' : 'trending-up' }}" class="h-5 w-5"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('admin.signals.show', $signal) }}" class="text-lg font-semibold tracking-tight hover:underline">{{ $signal->instrument_symbol }}</a>
                                        <span class="rounded-full px-2 py-1 text-[9px] font-semibold uppercase {{ $directionClass }}">{{ $signal->direction }}</span>
                                        <span class="rounded-full px-2 py-1 text-[9px] font-semibold uppercase {{ $statusClass }}">{{ $signal->status }}</span>
                                    </div>
                                    <p class="mt-1 truncate text-xs text-muted-foreground">{{ $signal->instrument_name }}</p>
                                    <p class="mt-1 text-[10px] text-muted-foreground">Signal #{{ $signal->id }} · {{ str_replace('_', ' ', $signal->source) }} · {{ $signal->generated_at?->diffForHumans() }}</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.signals.show', $signal) }}" class="ui-btn ui-btn-primary ui-btn-sm shrink-0"><i data-lucide="eye" class="h-4 w-4"></i>Open Signal</a>
                        </div>

                        <div class="mt-5 grid grid-cols-2 gap-2 sm:grid-cols-4">
                            <div class="flex h-16 min-w-0 flex-col rounded-xl border border-border bg-muted/20 px-3 py-2.5">
                                <p class="text-[9px] font-semibold uppercase tracking-[.13em] text-muted-foreground">Market</p>
                                <p class="mt-1 whitespace-nowrap text-[11px] font-semibold leading-4 tracking-tight uppercase">{{ $signal->marketplace }}</p>
                            </div>
                            <div class="flex h-16 min-w-0 flex-col rounded-xl border border-border bg-muted/20 px-3 py-2.5">
                                <p class="text-[9px] font-semibold uppercase tracking-[.13em] text-muted-foreground">Timeframe</p>
                                <p class="mt-1 whitespace-nowrap text-[11px] font-semibold leading-4 tracking-tight uppercase">{{ $signal->timeframe }}</p>
                            </div>
                            <div class="flex h-16 min-w-0 flex-col rounded-xl border border-border bg-muted/20 px-3 py-2.5">
                                <p class="text-[9px] font-semibold uppercase tracking-[.13em] text-muted-foreground">Strength</p>
                                <p class="mt-1 whitespace-nowrap text-[11px] font-semibold leading-4 tracking-tight">{{ strtoupper(str_replace('_', ' ', (string) $signal->strength)) }}</p>
                            </div>
                            <div class="flex h-16 min-w-0 flex-col rounded-xl border border-border bg-muted/20 px-3 py-2.5">
                                <p class="text-[9px] font-semibold uppercase tracking-[.13em] text-muted-foreground">Confluence</p>
                                <p class="mt-1 whitespace-nowrap text-[11px] font-semibold leading-4 tracking-tight">{{ number_format((float) $signal->confluence_score, 2) }}%</p>
                            </div>
                        </div>

                        <div class="mt-3 rounded-xl border border-border bg-card p-4">
                            <div class="grid gap-4 sm:grid-cols-3">
                                <div>
                                    <p class="ui-label">Entry zone</p>
                                    <p class="mt-1 text-sm font-semibold">{{ number_format((float) $signal->entry_min, $signal->price_precision) }} – {{ number_format((float) $signal->entry_max, $signal->price_precision) }}</p>
                                </div>
                                <div>
                                    <p class="ui-label">Stop loss</p>
                                    <p class="mt-1 text-sm font-semibold">{{ number_format((float) $signal->stop_loss, $signal->price_precision) }}</p>
                                </div>
                                <div>
                                    <p class="ui-label">Risk : Reward</p>
                                    <p class="mt-1 text-sm font-semibold">1:{{ number_format((float) $signal->risk_reward, 2) }}</p>
                                </div>
                            </div>

                            <div class="mt-4 border-t border-border pt-4">
                                <p class="ui-label">Targets</p>
                                <div class="mt-2 grid gap-2 sm:grid-cols-3">
                                    @forelse($signal->targets as $target)
                                        <div class="flex items-center justify-between rounded-lg bg-muted/35 px-3 py-2">
                                            <span class="text-[10px] font-semibold uppercase text-muted-foreground">TP{{ $target->sequence }}</span>
                                            <span class="text-xs font-semibold">{{ number_format((float) $target->price, 2) }}</span>
                                        </div>
                                    @empty
                                        <p class="text-xs text-muted-foreground sm:col-span-3">No targets attached.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-col gap-3 border-t border-border pt-4 text-[10px] text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-wrap gap-x-4 gap-y-1">
                                <span><strong class="font-semibold text-foreground">{{ $signal->analysis_runs_count }}</strong> analyses</span>
                                <span><strong class="font-semibold text-foreground">{{ $signal->revisions_count }}</strong> revisions</span>
                                <span><strong class="font-semibold text-foreground">{{ $signal->deliveries_count }}</strong> deliveries</span>
                            </div>
                            <div class="flex flex-wrap gap-x-4 gap-y-1 sm:justify-end">
                                <span>Expires {{ $signal->expires_at?->format('M j, Y g:i A') ?? 'n/a' }}</span>
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="ui-panel p-12 text-center xl:col-span-2">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-muted"><i data-lucide="inbox" class="h-5 w-5"></i></div>
                    <p class="mt-3 text-sm font-semibold">No Signals in this workspace</p>
                    <p class="mt-1 text-xs text-muted-foreground">The engine has no records matching {{ implode(', ', $statuses) }}.</p>
                </div>
            @endforelse
        </section>

        @if($signals->hasPages())
            <div class="ui-panel px-5 py-4">{{ $signals->links() }}</div>
        @endif
    </div>
</x-admin-layout>
