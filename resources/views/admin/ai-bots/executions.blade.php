<x-admin-layout>
<x-slot name="header">Bot Executions</x-slot>

<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">AI Trading Bots</p>
            <h1 class="mt-1 text-lg font-semibold tracking-tight">Execution History</h1>
            <p class="mt-1 text-[13px] text-muted-foreground">Runtime decisions presented as compact activity records instead of a wide data table.</p>
        </div>
    </div>

    <div class="space-y-3">
        @forelse($executions as $execution)
            @php
                $isCompleted = $execution->status === 'completed';
                $isFailed = $execution->status === 'failed';
                $isSkipped = $execution->status === 'skipped';
                $symbol = $execution->marketInstrument?->display_symbol ?? $execution->bot?->marketInstrument?->display_symbol ?? $execution->bot?->stock?->symbol ?? '—';
                $botName = $execution->subscription?->product?->name ?? $execution->bot?->name ?? 'Bot';
            @endphp

            <article class="ui-panel overflow-visible border border-border/70 bg-gradient-to-br from-background via-background to-muted/10 shadow-sm">
                <div class="p-4">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <div class="min-w-0 xl:w-[28%]">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1 rounded-full border border-sky-500/20 bg-sky-500/10 px-2 py-1 text-[10px] font-semibold text-sky-600">
                                    <i data-lucide="candlestick-chart" class="h-3.5 w-3.5"></i>{{ $symbol }}
                                </span>

                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[10px] font-semibold
                                    {{ $isCompleted ? 'border border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : ($isFailed ? 'border border-red-500/20 bg-red-500/10 text-red-600' : 'border border-amber-500/20 bg-amber-500/10 text-amber-600') }}">
                                    @if($isCompleted)
                                        <i data-lucide="check-circle-2" class="h-3.5 w-3.5"></i>
                                    @elseif($isFailed)
                                        <i data-lucide="x-circle" class="h-3.5 w-3.5"></i>
                                    @else
                                        <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse"></span>
                                    @endif
                                    {{ ucfirst($execution->status) }}
                                </span>
                            </div>

                            <h2 class="mt-2.5 truncate text-sm font-semibold">{{ $botName }}</h2>
                            <p class="mt-1 text-xs text-muted-foreground">{{ $execution->subscription?->user?->name ?? 'Unknown customer' }}</p>
                        </div>

                        <div class="grid flex-1 grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-6">
                            <div class="rounded-xl border border-border bg-background/55 p-3">
                                <div class="flex items-center gap-2 text-[9px] uppercase tracking-[0.13em] text-muted-foreground">
                                    <i data-lucide="{{ strtolower($execution->action) === 'sell' ? 'arrow-up-right' : 'arrow-down-left' }}" class="h-3.5 w-3.5 {{ strtolower($execution->action) === 'sell' ? 'text-red-600' : 'text-emerald-600' }}"></i>
                                    Action
                                </div>
                                <p class="mt-1.5 text-xs font-semibold">{{ ucfirst($execution->action) }}</p>
                            </div>

                            <div class="rounded-xl border border-border bg-background/55 p-3">
                                <div class="flex items-center gap-2 text-[9px] uppercase tracking-[0.13em] text-muted-foreground">
                                    <i data-lucide="hash" class="h-3.5 w-3.5 text-violet-600"></i>
                                    Quantity
                                </div>
                                <p class="mt-1.5 text-xs font-semibold">{{ (float)$execution->quantity > 0 ? number_format((float)$execution->quantity,6) : '—' }}</p>
                            </div>

                            <div class="rounded-xl border border-border bg-background/55 p-3">
                                <div class="flex items-center gap-2 text-[9px] uppercase tracking-[0.13em] text-muted-foreground">
                                    <i data-lucide="badge-dollar-sign" class="h-3.5 w-3.5 text-amber-600"></i>
                                    Entry
                                </div>
                                <p class="mt-1.5 text-xs font-semibold">{{ (float)$execution->price > 0 ? format_currency($execution->price) : '—' }}</p>
                            </div>

                            <div class="rounded-xl border border-border bg-background/55 p-3">
                                <div class="flex items-center gap-2 text-[9px] uppercase tracking-[0.13em] text-muted-foreground">
                                    <i data-lucide="wallet" class="h-3.5 w-3.5 text-emerald-600"></i>
                                    Amount
                                </div>
                                <p class="mt-1.5 text-xs font-semibold">{{ (float)$execution->amount > 0 ? format_currency($execution->amount) : '—' }}</p>
                            </div>

                            <div class="rounded-xl border border-border bg-background/55 p-3 sm:col-span-2 lg:col-span-2">
                                <div class="flex items-center gap-2 text-[9px] uppercase tracking-[0.13em] text-muted-foreground">
                                    <i data-lucide="clock-3" class="h-3.5 w-3.5 text-sky-600"></i>
                                    Executed
                                </div>
                                <p class="mt-1.5 text-xs font-semibold">{{ optional($execution->executed_at)->format('M d, Y · H:i') ?? '—' }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 xl:w-[24%] xl:justify-end">
                            @if($execution->reason)
                                <div class="min-w-0 flex-1 rounded-xl border {{ $isFailed ? 'border-red-500/20 bg-red-500/5' : 'border-amber-500/20 bg-amber-500/5' }} p-3">
                                    <div class="flex items-center gap-2 text-[9px] uppercase tracking-[0.13em] text-muted-foreground">
                                        <i data-lucide="info" class="h-3.5 w-3.5 {{ $isFailed ? 'text-red-600' : 'text-amber-600' }}"></i>
                                        Reason
                                    </div>
                                    <p class="mt-1.5 truncate text-xs font-medium" title="{{ $execution->reason }}">{{ $execution->reason }}</p>
                                </div>
                            @else
                                <div class="min-w-0 flex-1 rounded-xl border border-border bg-background/40 p-3">
                                    <div class="flex items-center gap-2 text-[9px] uppercase tracking-[0.13em] text-muted-foreground">
                                        <i data-lucide="circle-check" class="h-3.5 w-3.5 text-emerald-600"></i>
                                        Result
                                    </div>
                                    <p class="mt-1.5 text-xs font-medium">Executed successfully</p>
                                </div>
                            @endif

                            <details class="relative shrink-0">
                                <summary class="list-none cursor-pointer rounded-lg border border-border bg-background p-2 text-muted-foreground transition hover:bg-muted hover:text-foreground">
                                    <i data-lucide="more-vertical" class="h-4 w-4"></i>
                                </summary>
                                <div class="absolute right-0 z-30 mt-2 w-40 rounded-xl border border-border bg-background p-1.5 shadow-lg">
                                    <a href="{{ route('admin.ai-bots.executions.show',$execution) }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium hover:bg-muted">
                                        <i data-lucide="square-arrow-out-up-right" class="h-3.5 w-3.5"></i>
                                        View details
                                    </a>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="ui-panel p-8 text-center">
                <i data-lucide="activity" class="mx-auto h-5 w-5 text-muted-foreground"></i>
                <p class="mt-2 text-sm font-medium">No executions yet.</p>
                <p class="mt-1 text-xs text-muted-foreground">Bot runtime decisions will appear here.</p>
            </div>
        @endforelse
    </div>

    {{ $executions->links() }}
</div>
</x-admin-layout>