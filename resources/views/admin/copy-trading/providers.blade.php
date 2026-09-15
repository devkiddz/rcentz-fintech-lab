<x-admin-layout>
<x-slot name="header">Copy Trading Providers</x-slot>

<div class="ui-page max-w-[1600px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker text-[10px]">Copy Trading</p>
            <h1 class="ui-heading !text-2xl">Approved providers</h1>
            <p class="ui-lead !max-w-3xl !text-[13px]">Manage provider availability, inspect strategies and see exactly which customers are following them.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.copy-trading.applications') }}" class="ui-btn ui-btn-secondary">Applications</a>
            <a href="{{ route('admin.copy-trading.strategies') }}" class="ui-btn ui-btn-secondary">Strategies</a>
        </div>
    </section>

    <section class="ui-panel mt-5 overflow-hidden">
        <div class="divide-y divide-border/70">
            @forelse($providers as $p)
                <div class="grid gap-4 px-4 py-4 lg:grid-cols-[1.4fr_.45fr_.55fr_.55fr_auto] lg:items-center">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-border bg-muted text-[10px] font-semibold">
                                {{ strtoupper(substr($p->user->name,0,1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-[12px] font-semibold">{{ $p->user->name }}</p>
                                <div class="mt-1 flex flex-wrap items-center gap-2">
                                    <span class="text-[9px] text-muted-foreground">Approved provider</span>
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[8px] font-semibold uppercase tracking-[.1em]
                                        {{ $p->is_accepting_copiers ? 'border border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : 'border border-border bg-muted text-muted-foreground' }}">
                                        {{ $p->is_accepting_copiers ? 'Accepting copiers' : 'Closed to new copiers' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Strategies</p>
                        <p class="mt-1 text-[12px] font-semibold tabular-nums">{{ number_format($p->strategies_count) }}</p>
                    </div>

                    <div>
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Active copiers</p>
                        <p class="mt-1 text-[12px] font-semibold tabular-nums">{{ number_format($p->active_relationships_count) }}</p>
                    </div>

                    <div>
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Approved</p>
                        <p class="mt-1 text-[10px] font-medium">{{ optional($p->approved_at)->format('M d, Y') ?: '—' }}</p>
                    </div>

                    <div class="flex justify-end gap-2">
                        <a href="{{ route('admin.copy-trading.providers.show',$p) }}" class="ui-btn ui-btn-primary !h-8 !px-3">
                            <i data-lucide="settings-2" class="h-3.5 w-3.5"></i> Manage
                        </a>
                        <form method="POST" action="{{ route('admin.copy-trading.providers.toggle',$p) }}">
                            @csrf @method('PATCH')
                            <button class="ui-btn ui-btn-secondary !h-8 !px-3">
                                <i data-lucide="{{ $p->is_accepting_copiers ? 'user-x' : 'user-check' }}" class="h-3.5 w-3.5"></i>
                                {{ $p->is_accepting_copiers ? 'Close' : 'Reopen' }}
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-10 text-center text-[10px] text-muted-foreground">No approved providers found.</div>
            @endforelse
        </div>
    </section>

    <div class="mt-4">{{ $providers->links() }}</div>
</div>
</x-admin-layout>
