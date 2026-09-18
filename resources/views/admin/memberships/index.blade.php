<x-admin-layout>
    <x-slot name="header">
        <div>
            <div class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[.18em] text-muted-foreground"><span>Admin</span><span>•</span><span>Memberships</span></div>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-foreground">Membership Control</h1>
            <p class="mt-1 text-sm text-muted-foreground">Create membership types, define plans and control every customer membership from one engine.</p>
        </div>
    </x-slot>

    <div class="ui-page max-w-[1500px]">
        @if(session('success'))<div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-600">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-xs text-red-600">{{ $errors->first() }}</div>@endif

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="ui-metric-card"><div class="ui-metric-icon"><i data-lucide="layers-3" class="h-5 w-5"></i></div><div><p class="ui-label">Membership types</p><p class="mt-1 text-2xl font-semibold">{{ number_format($stats['types']) }}</p></div></article>
            <article class="ui-metric-card"><div class="ui-metric-icon"><i data-lucide="circle-check" class="h-5 w-5"></i></div><div><p class="ui-label">Active types</p><p class="mt-1 text-2xl font-semibold">{{ number_format($stats['active_types']) }}</p></div></article>
            <article class="ui-metric-card"><div class="ui-metric-icon"><i data-lucide="badge-check" class="h-5 w-5"></i></div><div><p class="ui-label">Active memberships</p><p class="mt-1 text-2xl font-semibold">{{ number_format($stats['active_memberships']) }}</p></div></article>
            <article class="ui-metric-card"><div class="ui-metric-icon"><i data-lucide="clock-3" class="h-5 w-5"></i></div><div><p class="ui-label">Pending</p><p class="mt-1 text-2xl font-semibold">{{ number_format($stats['pending_memberships']) }}</p></div></article>
        </section>

        <section class="mt-4 grid gap-4 xl:grid-cols-[.72fr_1.28fr]">
            <article class="ui-panel p-5 sm:p-6">
                <p class="ui-kicker">Membership registry</p>
                <h2 class="mt-1 text-lg font-semibold">Create membership type</h2>
                <p class="mt-1 text-xs text-muted-foreground">VIP is now one type in this registry. New membership products reuse the same plans, entitlements and lifecycle engine.</p>

                <form method="POST" action="{{ route('admin.memberships.types.store') }}" class="mt-5 space-y-4">@csrf
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div><label class="ui-label">Name</label><input class="ui-input w-full" name="name" value="{{ old('name') }}" placeholder="Signals Membership" required></div>
                        <div><label class="ui-label">Slug</label><input class="ui-input w-full" name="slug" value="{{ old('slug') }}" placeholder="signals"></div>
                        <div><label class="ui-label">Icon</label><input class="ui-input w-full" name="icon" value="{{ old('icon','badge-check') }}" placeholder="badge-check"></div>
                        <div><label class="ui-label">Sort order</label><input class="ui-input w-full" type="number" min="0" name="sort_order" value="{{ old('sort_order',0) }}"></div>
                    </div>
                    <div><label class="ui-label">Description</label><textarea class="ui-input w-full" rows="3" name="description" placeholder="What this membership type represents">{{ old('description') }}</textarea></div>
                    <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="is_active" value="1" checked><span>Active</span></label>
                    <button class="ui-btn ui-btn-primary w-full justify-center"><i data-lucide="plus" class="h-4 w-4"></i>Create membership type</button>
                </form>
            </article>

            <div class="space-y-3">
                @forelse($types as $type)
                    <article class="ui-panel overflow-hidden">
                        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex items-start gap-4">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-border bg-muted/40"><i data-lucide="{{ $type->icon ?: 'badge-check' }}" class="h-5 w-5"></i></div>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2"><h2 class="text-base font-semibold">{{ $type->name }}</h2><span class="rounded-full border px-2 py-1 text-[9px] font-semibold {{ $type->is_active ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : 'border-border bg-muted text-muted-foreground' }}">{{ $type->is_active ? 'Active' : 'Inactive' }}</span></div>
                                    <p class="mt-1 text-xs text-muted-foreground">{{ $type->slug }} · {{ $type->plans_count }} plans · {{ $type->memberships_count }} membership records</p>
                                    @if($type->description)<p class="mt-2 max-w-2xl text-xs leading-5 text-muted-foreground">{{ $type->description }}</p>@endif
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.memberships.show', $type) }}" class="ui-btn ui-btn-secondary"><i data-lucide="layers-3" class="h-4 w-4"></i>Plans & Entitlements</a>
                                <a href="{{ route('admin.memberships.registry', $type) }}" class="ui-btn ui-btn-primary"><i data-lucide="badge-check" class="h-4 w-4"></i>Memberships</a>
                            </div>
                        </div>
                        <details class="group border-t border-border">
                            <summary class="flex cursor-pointer items-center justify-between px-5 py-3 text-xs font-semibold hover:bg-muted/20"><span>Type configuration</span><i data-lucide="chevron-down" class="h-4 w-4 transition-transform group-open:rotate-180"></i></summary>
                            <div class="grid gap-3 border-t border-border p-5 lg:grid-cols-[1fr_auto]">
                                <form method="POST" action="{{ route('admin.memberships.types.update', $type) }}" class="grid gap-3 sm:grid-cols-2">@csrf @method('PATCH')
                                    <div><label class="ui-label">Name</label><input class="ui-input w-full" name="name" value="{{ $type->name }}" required></div>
                                    <div><label class="ui-label">Slug</label><input class="ui-input w-full" name="slug" value="{{ $type->slug }}" required></div>
                                    <div><label class="ui-label">Icon</label><input class="ui-input w-full" name="icon" value="{{ $type->icon }}"></div>
                                    <div><label class="ui-label">Sort order</label><input class="ui-input w-full" type="number" min="0" name="sort_order" value="{{ $type->sort_order }}"></div>
                                    <div class="sm:col-span-2"><label class="ui-label">Description</label><textarea class="ui-input w-full" rows="2" name="description">{{ $type->description }}</textarea></div>
                                    <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="is_active" value="1" @checked($type->is_active)><span>Active</span></label>
                                    <div class="flex justify-end"><button class="ui-btn ui-btn-secondary">Save type</button></div>
                                </form>
                                <div class="flex gap-2 lg:flex-col">
                                    <form method="POST" action="{{ route('admin.memberships.types.toggle', $type) }}">@csrf @method('PATCH')<button class="ui-btn ui-btn-secondary w-full">{{ $type->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                                    @if($type->plans_count === 0)<form method="POST" action="{{ route('admin.memberships.types.destroy', $type) }}" onsubmit="return confirm('Delete this unused membership type?');">@csrf @method('DELETE')<button class="ui-btn w-full border border-red-500/25 bg-red-500/10 text-red-600">Delete</button></form>@endif
                                </div>
                            </div>
                        </details>
                    </article>
                @empty
                    <div class="ui-panel p-10 text-center text-sm text-muted-foreground">No membership types exist yet.</div>
                @endforelse
            </div>
        </section>
    </div>
</x-admin-layout>
