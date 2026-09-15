<x-admin-layout>
<x-slot name="header">Manage Copy Strategy</x-slot>
@php $m=$strategy->performance_metrics; @endphp

<div class="ui-page max-w-[1600px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker text-[10px]">Copy Trading · Strategy</p>
            <h1 class="ui-heading !text-2xl">{{ $strategy->name }}</h1>
            <p class="ui-lead !text-[13px]">Provider · {{ $strategy->profile->user->name }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.copy-trading.strategies') }}" class="ui-btn ui-btn-secondary">Back</a>
            <a href="{{ route('admin.copy-trading.strategies.edit',$strategy) }}" class="ui-btn ui-btn-primary">
                <i data-lucide="pencil" class="h-4 w-4"></i> Edit
            </a>
            <form method="POST" action="{{ route('admin.copy-trading.strategies.toggle',$strategy) }}">
                @csrf @method('PATCH')
                <button class="ui-btn ui-btn-secondary">{{ $strategy->is_active ? 'Pause' : 'Activate' }}</button>
            </form>
        </div>
    </section>

    <section class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="ui-panel p-4"><p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Contracts</p><p class="mt-2 text-xl font-semibold">{{ $strategy->relationships->count() }}</p></div>
        <div class="ui-panel p-4"><p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Current P/L</p><p class="mt-2 text-xl font-semibold">{{ format_currency($m['profit_loss'] ?? 0) }}</p></div>
        <div class="ui-panel p-4"><p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Return</p><p class="mt-2 text-xl font-semibold">{{ number_format($m['return_percent'] ?? 0,2) }}%</p></div>
        <div class="ui-panel p-4"><p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Status</p><p class="mt-2 text-sm font-semibold">{{ $strategy->is_active ? 'Active' : 'Paused' }}</p></div>
    </section>

    <section class="ui-panel mt-5 overflow-hidden">
        <div class="border-b border-border/70 px-4 py-4">
            <h2 class="text-[13px] font-semibold">Follower contracts</h2>
            <p class="mt-1 text-[10px] text-muted-foreground">Every customer contract attached to this strategy.</p>
        </div>

        <div class="divide-y divide-border/70">
            @forelse($strategy->relationships as $relationship)
                <div class="grid gap-3 px-4 py-4 sm:grid-cols-[1.1fr_.55fr_.7fr_.65fr_.7fr] sm:items-center">
                    <div>
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Follower</p>
                        <p class="mt-1 text-[11px] font-semibold">{{ $relationship->follower?->name ?? 'Unknown' }}</p>
                    </div>
                    <div>
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Status</p>
                        <p class="mt-1 text-[10px] font-semibold">{{ ucfirst($relationship->status) }}</p>
                    </div>
                    <div>
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Allocation</p>
                        <p class="mt-1 text-[10px] font-semibold">{{ format_currency($relationship->allocation_limit) }}</p>
                    </div>
                    <div>
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Copy %</p>
                        <p class="mt-1 text-[10px] font-semibold">{{ number_format((float)$relationship->copy_ratio_percent,0) }}%</p>
                    </div>
                    <div>
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Window</p>
                        <p class="mt-1 text-[9px] font-medium">{{ $relationship->started_at?->format('M d · H:i') ?? '—' }} → {{ $relationship->ends_at?->format('M d · H:i') ?? 'Open' }}</p>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-[10px] text-muted-foreground">No contracts attached to this strategy.</div>
            @endforelse
        </div>
    </section>

    <section class="mt-5 flex justify-end gap-2">
        <form method="POST" action="{{ route('admin.copy-trading.strategies.retire',$strategy) }}">
            @csrf @method('PATCH')
            <button class="ui-btn ui-btn-secondary">
                <i data-lucide="archive" class="h-4 w-4"></i> Retire strategy
            </button>
        </form>

        @if($strategy->relationships->isEmpty())
            <form method="POST" action="{{ route('admin.copy-trading.strategies.destroy',$strategy) }}"
                  onsubmit="return confirm('Delete this unused strategy permanently?')">
                @csrf @method('DELETE')
                <button class="ui-btn ui-btn-secondary text-red-600">
                    <i data-lucide="trash-2" class="h-4 w-4"></i> Delete unused strategy
                </button>
            </form>
        @endif
    </section>
</div>
</x-admin-layout>
