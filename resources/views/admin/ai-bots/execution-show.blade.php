<x-admin-layout>
<x-slot name="header">Bot Execution</x-slot>

<div class="mx-auto max-w-5xl space-y-5">
    <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">Execution #{{ $execution->id }}</p>
            <h1 class="mt-1.5 text-xl font-semibold tracking-tight">{{ $execution->subscription?->product?->name ?? $execution->bot?->name }}</h1>
            <p class="mt-1 text-sm text-muted-foreground">{{ $execution->subscription?->user?->name }} · {{ $execution->marketInstrument?->display_symbol ?? $execution->bot?->marketInstrument?->display_symbol ?? $execution->bot?->stock?->symbol ?? '—' }}</p>
        </div>
        <a href="{{ route('admin.ai-bots.executions') }}" class="ui-btn ui-btn-secondary">
            <i data-lucide="arrow-left" class="h-4 w-4"></i>
            Back to Executions
        </a>
    </section>

    <article class="ui-panel overflow-hidden">
        <div class="p-5 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1 rounded-full border border-sky-500/20 bg-sky-500/10 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-sky-600">
                            <i data-lucide="candlestick-chart" class="h-3.5 w-3.5"></i>{{ $execution->marketInstrument?->display_symbol ?? $execution->bot?->marketInstrument?->display_symbol ?? $execution->bot?->stock?->symbol ?? '—' }}
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-full border border-border px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">
                            <i data-lucide="cpu" class="h-3.5 w-3.5"></i>{{ strtoupper($execution->action) }}
                        </span>
                    </div>
                    <h2 class="mt-3 text-lg font-semibold">Runtime Decision</h2>
                    <p class="mt-1 text-[13px] text-muted-foreground">Market comparison, execution numbers and current outcome.</p>
                </div>
                <span class="inline-flex items-center gap-1 self-start rounded-full px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.14em]
                    {{ $execution->status === 'completed' ? 'bg-emerald-500/10 text-emerald-600' : ($execution->status === 'failed' ? 'bg-red-500/10 text-red-600' : 'bg-amber-500/10 text-amber-600') }}">
                    @if($execution->status === 'completed')
                        <i data-lucide="check-circle-2" class="h-3.5 w-3.5"></i>
                    @elseif($execution->status === 'failed')
                        <i data-lucide="x-circle" class="h-3.5 w-3.5"></i>
                    @else
                        <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse"></span>
                    @endif
                    {{ ucfirst($execution->status) }}
                </span>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-xl border border-border bg-background/50 p-3">
                    <div class="flex items-center gap-2 text-[10px] uppercase tracking-[0.14em] text-muted-foreground"><i data-lucide="wallet" class="h-3.5 w-3.5 text-emerald-600"></i>Trade Amount</div>
                    <p class="mt-1.5 text-sm font-semibold">{{ (float)$execution->amount > 0 ? format_currency($execution->amount) : '—' }}</p>
                </div>
                <div class="rounded-xl border border-border bg-background/50 p-3">
                    <div class="flex items-center gap-2 text-[10px] uppercase tracking-[0.14em] text-muted-foreground"><i data-lucide="hash" class="h-3.5 w-3.5 text-sky-600"></i>Quantity</div>
                    <p class="mt-1.5 text-sm font-semibold">{{ (float)$execution->quantity > 0 ? number_format((float)$execution->quantity,6) : '—' }}</p>
                </div>
                <div class="rounded-xl border border-border bg-background/50 p-3">
                    <div class="flex items-center gap-2 text-[10px] uppercase tracking-[0.14em] text-muted-foreground"><i data-lucide="badge-dollar-sign" class="h-3.5 w-3.5 text-amber-600"></i>Entry Price</div>
                    <p class="mt-1.5 text-sm font-semibold">{{ (float)$execution->price > 0 ? $entryPriceDisplay : '—' }}</p>
                </div>
                <div class="rounded-xl border border-border bg-background/50 p-3">
                    <div class="flex items-center gap-2 text-[10px] uppercase tracking-[0.14em] text-muted-foreground"><i data-lucide="radar" class="h-3.5 w-3.5 text-violet-600"></i>Current Price</div>
                    <p class="mt-1.5 text-sm font-semibold">{{ $currentPrice > 0 ? $currentPriceDisplay : '—' }}</p>
                </div>
                <div class="rounded-xl border {{ $profitLoss > 0 ? 'border-emerald-500/20 bg-emerald-500/5' : ($profitLoss < 0 ? 'border-red-500/20 bg-red-500/5' : 'border-border bg-background/50') }} p-3">
                    <div class="flex items-center gap-2 text-[10px] uppercase tracking-[0.14em] text-muted-foreground"><i data-lucide="line-chart" class="h-3.5 w-3.5 {{ $profitLoss > 0 ? 'text-emerald-600' : ($profitLoss < 0 ? 'text-red-600' : '') }}"></i>Current P/L</div>
                    <p class="mt-1.5 text-sm font-semibold {{ $profitLoss < 0 ? 'text-red-600' : ($profitLoss > 0 ? 'text-emerald-600' : '') }}">{{ $profitLoss > 0 ? '+' : '' }}{{ format_currency($profitLoss) }}</p>
                </div>
                <div class="rounded-xl border {{ $returnPercent > 0 ? 'border-sky-500/20 bg-sky-500/5' : 'border-border bg-background/50' }} p-3">
                    <div class="flex items-center gap-2 text-[10px] uppercase tracking-[0.14em] text-muted-foreground"><i data-lucide="trending-up" class="h-3.5 w-3.5 {{ $returnPercent > 0 ? 'text-sky-600' : '' }}"></i>Current Return</div>
                    <p class="mt-1.5 text-sm font-semibold {{ $returnPercent > 0 ? 'text-sky-600' : '' }}">{{ $returnPercent > 0 ? '+' : '' }}{{ number_format($returnPercent,2) }}%</p>
                </div>
            </div>

            @if($execution->reason)
                <div class="mt-5 rounded-xl border border-border bg-muted/25 p-4">
                    <div class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                        <i data-lucide="file-search" class="h-3.5 w-3.5 text-amber-600"></i>Execution Reason
                    </div>
                    <p class="mt-2 text-sm leading-6">{{ $execution->reason }}</p>
                </div>
            @endif

            <div class="mt-5 flex items-center gap-2 border-t border-border pt-4 text-xs text-muted-foreground">
                <i data-lucide="clock-3" class="h-3.5 w-3.5"></i>
                {{ optional($execution->executed_at)->format('M d, Y · h:i A') ?? '—' }}
            </div>
        </div>
    </article>
</div>
</x-admin-layout>
