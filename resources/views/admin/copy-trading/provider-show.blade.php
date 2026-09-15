<x-admin-layout>
<x-slot name="header">Manage Copy Provider</x-slot>
<div class="ui-page max-w-[1600px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker text-[10px]">Copy Trading · Provider</p>
            <h1 class="ui-heading !text-2xl">{{ $provider->user->name }}</h1>
            <p class="ui-lead !text-[13px]">{{ $provider->strategy_name }} · {{ ucfirst($provider->risk_level) }} risk</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.copy-trading.providers') }}" class="ui-btn ui-btn-secondary">Back</a>
            <form method="POST" action="{{ route('admin.copy-trading.providers.toggle',$provider) }}">
                @csrf @method('PATCH')
                <button class="ui-btn {{ $provider->is_accepting_copiers ? 'ui-btn-secondary' : 'ui-btn-primary' }}">
                    {{ $provider->is_accepting_copiers ? 'Stop new copiers' : 'Accept new copiers' }}
                </button>
            </form>
        </div>
    </section>

    <section class="mt-5 grid gap-3 sm:grid-cols-3">
        <div class="ui-panel p-4"><p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Strategies</p><p class="mt-2 text-xl font-semibold">{{ $provider->strategies->count() }}</p></div>
        <div class="ui-panel p-4"><p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Contracts shown</p><p class="mt-2 text-xl font-semibold">{{ $provider->relationships->count() }}</p></div>
        <div class="ui-panel p-4"><p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Provider access</p><p class="mt-2 text-sm font-semibold">{{ $provider->is_accepting_copiers ? 'Accepting copiers' : 'Closed' }}</p></div>
    </section>

    <section class="ui-panel mt-5 overflow-hidden">
        <div class="border-b border-border/70 px-4 py-4">
            <h2 class="text-[13px] font-semibold">Provider strategies</h2>
            <p class="mt-1 text-[10px] text-muted-foreground">Manage each strategy independently.</p>
        </div>
        <div class="divide-y divide-border/70">
            @forelse($provider->strategies as $strategy)
                <div class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="text-[12px] font-semibold">{{ $strategy->name }}</p>
                            <span class="rounded-full px-2 py-0.5 text-[8px] font-semibold uppercase {{ $strategy->is_active ? 'bg-emerald-500/10 text-emerald-600' : 'bg-muted text-muted-foreground' }}">
                                {{ $strategy->is_active ? 'Active' : 'Paused' }}
                            </span>
                        </div>
                        <p class="mt-1 text-[9px] text-muted-foreground">{{ $strategy->relationships_count }} contract(s) · {{ ucfirst($strategy->risk_level) }} risk</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.copy-trading.strategies.show',$strategy) }}" class="ui-btn ui-btn-primary !h-8 !px-3">Manage</a>
                        <a href="{{ route('admin.copy-trading.strategies.edit',$strategy) }}" class="ui-btn ui-btn-secondary !h-8 !px-3">Edit</a>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-[10px] text-muted-foreground">No strategies for this provider.</div>
            @endforelse
        </div>
    </section>

    <section class="ui-panel mt-5 overflow-hidden">
        <div class="border-b border-border/70 px-4 py-4">
            <h2 class="text-[13px] font-semibold">Follower contracts</h2>
            <p class="mt-1 text-[10px] text-muted-foreground">Who is using which strategy and the current contract state.</p>
        </div>
        <div class="divide-y divide-border/70">
            @forelse($provider->relationships as $relationship)
                <div class="grid gap-3 px-4 py-4 sm:grid-cols-[1fr_1fr_.6fr_.7fr_.7fr] sm:items-center">
                    <div><p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Follower</p><p class="mt-1 text-[11px] font-semibold">{{ $relationship->follower?->name ?? 'Unknown' }}</p></div>
                    <div><p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Strategy</p><p class="mt-1 text-[11px] font-medium">{{ $relationship->strategy?->name ?? '—' }}</p></div>
                    <div><p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Status</p><p class="mt-1 text-[10px] font-semibold">{{ ucfirst($relationship->status) }}</p></div>
                    <div><p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Allocation</p><p class="mt-1 text-[10px] font-semibold">{{ format_currency($relationship->allocation_limit) }}</p></div>
                    <div><p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Ends</p><p class="mt-1 text-[10px] font-medium">{{ $relationship->ends_at?->format('M d · H:i') ?? 'Open' }}</p></div>
                </div>
            @empty
                <div class="p-8 text-center text-[10px] text-muted-foreground">No follower contracts yet.</div>
            @endforelse
        </div>
    </section>
</div>
</x-admin-layout>
