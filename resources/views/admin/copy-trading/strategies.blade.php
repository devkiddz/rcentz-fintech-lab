<x-admin-layout>
<x-slot name="header">Copy Strategies</x-slot>

<div class="ui-page max-w-[1600px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker text-[10px]">Copy Trading</p>
            <h1 class="ui-heading !text-2xl">Published strategies</h1>
            <p class="ui-lead !max-w-3xl !text-[13px]">Inspect followers, edit strategy settings, pause marketplace activity or retire strategies without destroying contract history.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.copy-trading.applications') }}" class="ui-btn ui-btn-secondary">Applications</a>
            <a href="{{ route('admin.copy-trading.providers') }}" class="ui-btn ui-btn-secondary">Providers</a>
        </div>
    </section>

    <section class="ui-panel mt-5 overflow-hidden">
        <div class="divide-y divide-border/70">
            @forelse($strategies as $s)
                <div class="grid gap-4 px-4 py-4 xl:grid-cols-[1.3fr_.9fr_.4fr_.45fr_.55fr_auto] xl:items-center">
                    <div class="min-w-0">
                        <p class="truncate text-[12px] font-semibold">{{ $s->name }}</p>
                        <p class="mt-0.5 text-[9px] text-muted-foreground">Marketplace strategy</p>
                    </div>
                    <div>
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Provider</p>
                        <p class="mt-1 truncate text-[10px] font-medium">{{ $s->profile->user->name }}</p>
                    </div>
                    <div>
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Risk</p>
                        <span class="mt-1 inline-flex rounded-full border border-amber-500/20 bg-amber-500/10 px-2 py-1 text-[8px] font-semibold uppercase tracking-[.11em] text-amber-600">{{ ucfirst($s->risk_level) }}</span>
                    </div>
                    <div>
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Contracts</p>
                        <p class="mt-1 text-[12px] font-semibold tabular-nums">{{ number_format($s->relationships_count) }}</p>
                    </div>
                    <div>
                        <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Status</p>
                        <span class="mt-1 inline-flex items-center gap-1 rounded-full px-2 py-1 text-[8px] font-semibold uppercase tracking-[.11em]
                            {{ $s->is_active ? 'border border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : 'border border-border bg-muted text-muted-foreground' }}">
                            @if($s->is_active)<span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>@endif
                            {{ $s->is_active ? 'Active' : 'Paused' }}
                        </span>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2">
                        <a href="{{ route('admin.copy-trading.strategies.show',$s) }}" class="ui-btn ui-btn-primary !h-8 !px-3">
                            <i data-lucide="users" class="h-3.5 w-3.5"></i> Manage
                        </a>

                        <a href="{{ route('admin.copy-trading.strategies.edit',$s) }}" class="ui-btn ui-btn-secondary !h-8 !px-3">
                            <i data-lucide="pencil" class="h-3.5 w-3.5"></i> Edit
                        </a>

                        <form method="POST" action="{{ route('admin.copy-trading.strategies.toggle',$s) }}">
                            @csrf @method('PATCH')
                            <button class="ui-btn ui-btn-secondary !h-8 !px-3">
                                <i data-lucide="{{ $s->is_active ? 'pause' : 'play' }}" class="h-3.5 w-3.5"></i>
                                {{ $s->is_active ? 'Pause' : 'Activate' }}
                            </button>
                        </form>

                        <details class="relative">
                            <summary class="ui-btn ui-btn-secondary !h-8 !px-2 list-none cursor-pointer">
                                <i data-lucide="more-vertical" class="h-3.5 w-3.5"></i>
                            </summary>
                            <div class="absolute right-0 z-40 mt-2 w-48 rounded-xl border border-border bg-background p-1.5 shadow-xl">
                                <form method="POST" action="{{ route('admin.copy-trading.strategies.retire',$s) }}">
                                    @csrf @method('PATCH')
                                    <button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-[10px] font-medium hover:bg-muted">
                                        <i data-lucide="archive" class="h-3.5 w-3.5"></i> Retire strategy
                                    </button>
                                </form>

                                @if($s->relationships_count === 0)
                                    <form method="POST" action="{{ route('admin.copy-trading.strategies.destroy',$s) }}"
                                          onsubmit="return confirm('Delete this unused strategy permanently?')">
                                        @csrf @method('DELETE')
                                        <button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-[10px] font-medium text-red-600 hover:bg-red-500/10">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i> Delete unused strategy
                                        </button>
                                    </form>
                                @else
                                    <div class="px-3 py-2 text-[9px] leading-4 text-muted-foreground">
                                        Delete disabled — contract history exists.
                                    </div>
                                @endif
                            </div>
                        </details>
                    </div>
                </div>
            @empty
                <div class="p-10 text-center text-[10px] text-muted-foreground">No published strategies found.</div>
            @endforelse
        </div>
    </section>

    <div class="mt-4">{{ $strategies->links() }}</div>
</div>
</x-admin-layout>
