<x-admin-layout>
<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Admin · Trading Command</p>
            <h1 class="ui-heading">Trading operations</h1>
            <p class="ui-lead">Manage markets, direct trades, user trades, open positions and execution history from one command surface.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.trading.manual') }}" class="ui-btn ui-btn-primary">Manual Trade Desk</a>
            <a href="{{ route('admin.trading.history') }}" class="ui-btn ui-btn-secondary">Trade History</a>
        </div>
    </section>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Active stocks',$activeStocks,'activity'],
            ['Open positions',$openPositions,'target'],
            ['Closed positions',$closedPositions,'circle-check'],
            ['Stock transactions',$transactions,'receipt-text'],
        ] as [$label,$value,$icon])
            <div class="ui-panel p-4">
                <div class="flex items-center justify-between">
                    <p class="text-[10px] uppercase tracking-[.12em] text-muted-foreground">{{ $label }}</p>
                    <i data-lucide="{{ $icon }}" class="h-4 w-4 text-muted-foreground"></i>
                </div>
                <p class="mt-3 text-2xl font-semibold">{{ number_format($value) }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-4 grid gap-4 xl:grid-cols-[1.25fr_.75fr]">
        <section class="ui-panel p-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="ui-kicker">Position lifecycle</p>
                    <h2 class="text-sm font-semibold">Recent positions</h2>
                </div>
                <a href="{{ route('admin.trading.positions') }}" class="ui-btn ui-btn-secondary !h-8 !px-3">Manage positions</a>
            </div>

            <div class="mt-4 divide-y divide-border">
                @forelse($recentPositions as $position)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div>
                            <p class="text-xs font-semibold">{{ $position->stock?->symbol ?? '—' }} · Position #{{ $position->id }}</p>
                            <p class="mt-1 text-[10px] text-muted-foreground">{{ $position->user?->name ?? 'Unknown user' }} · {{ ucfirst(str_replace('_',' ',$position->context_type)) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-semibold">{{ ucfirst(str_replace('_',' ',$position->status)) }}</p>
                            <p class="mt-1 text-[10px] text-muted-foreground">Open qty {{ number_format((float)$position->open_quantity,6) }}</p>
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-xs text-muted-foreground">No positions yet.</p>
                @endforelse
            </div>
        </section>

        <section class="ui-panel p-4">
            <p class="ui-kicker">Trading capabilities</p>
            <h2 class="text-sm font-semibold">Admin actions</h2>

            <div class="mt-4 space-y-2">
                <a href="{{ route('admin.trading.manual') }}" class="flex items-center justify-between rounded-xl border border-border p-3 hover:bg-muted/50">
                    <span class="flex items-center gap-3 text-xs font-medium"><i data-lucide="mouse-pointer-click" class="h-4 w-4"></i>Strategy / Admin / User trade</span>
                    <i data-lucide="arrow-right" class="h-4 w-4 text-muted-foreground"></i>
                </a>
                <a href="{{ route('admin.stocks.holdings.index') }}" class="flex items-center justify-between rounded-xl border border-border p-3 hover:bg-muted/50">
                    <span class="flex items-center gap-3 text-xs font-medium"><i data-lucide="briefcase-business" class="h-4 w-4"></i>Inspect holdings</span>
                    <i data-lucide="arrow-right" class="h-4 w-4 text-muted-foreground"></i>
                </a>
                <a href="{{ route('admin.stocks.transactions.index') }}" class="flex items-center justify-between rounded-xl border border-border p-3 hover:bg-muted/50">
                    <span class="flex items-center gap-3 text-xs font-medium"><i data-lucide="receipt-text" class="h-4 w-4"></i>Inspect transactions</span>
                    <i data-lucide="arrow-right" class="h-4 w-4 text-muted-foreground"></i>
                </a>
                <a href="{{ route('admin.copy-trading.strategies') }}" class="flex items-center justify-between rounded-xl border border-border p-3 hover:bg-muted/50">
                    <span class="flex items-center gap-3 text-xs font-medium"><i data-lucide="workflow" class="h-4 w-4"></i>Copy strategies</span>
                    <i data-lucide="arrow-right" class="h-4 w-4 text-muted-foreground"></i>
                </a>
                <a href="{{ route('admin.ai-bots.executions') }}" class="flex items-center justify-between rounded-xl border border-border p-3 hover:bg-muted/50">
                    <span class="flex items-center gap-3 text-xs font-medium"><i data-lucide="bot" class="h-4 w-4"></i>Bot executions</span>
                    <i data-lucide="arrow-right" class="h-4 w-4 text-muted-foreground"></i>
                </a>
            </div>
        </section>
    </div>
</div>
</x-admin-layout>
