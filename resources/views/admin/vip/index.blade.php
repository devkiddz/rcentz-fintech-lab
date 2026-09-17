<x-admin-layout>
<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">VIP · Control Center</p>
            <h1 class="ui-heading">VIP Membership Engine</h1>
            <p class="ui-lead max-w-3xl">Create membership plans, define plan entitlements and control which VIP products are available to customers.</p>
        </div>
        <div class="ui-header-actions">
            <a href="{{ route('admin.memberships.vip.memberships') }}" class="ui-btn ui-btn-primary">
                <i data-lucide="crown" class="h-4 w-4"></i>
                Memberships
            </a>
        </div>
    </section>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-600">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-xs text-red-600">{{ $errors->first() }}</div>
    @endif

    <section class="ui-metric-grid xl:grid-cols-4">
        @foreach([
            ['Plans', $stats['plans'], 'layers-3'],
            ['Active plans', $stats['active_plans'], 'badge-check'],
            ['Active members', $stats['active_memberships'], 'crown'],
            ['Pending', $stats['pending_memberships'], 'clock-3'],
        ] as [$label, $value, $icon])
            <div class="ui-metric-card">
                <div class="flex items-start justify-between gap-3">
                    <div><p class="ui-kicker">{{ $label }}</p><p class="mt-2 text-2xl font-semibold">{{ $value }}</p></div>
                    <i data-lucide="{{ $icon }}" class="h-4 w-4 text-muted-foreground"></i>
                </div>
            </div>
        @endforeach
    </section>

    <section class="mt-4 grid gap-4 xl:grid-cols-[.72fr_1.28fr]">
        <div class="ui-panel p-5">
            <p class="ui-kicker">Plan registry</p>
            <h2 class="mt-1 text-lg font-semibold">Create VIP plan</h2>
            <p class="mt-1 text-xs text-muted-foreground">The plan defines commercial terms. Entitlements define what access the membership grants.</p>

            <form method="POST" action="{{ route('admin.memberships.vip.plans.store') }}" class="mt-5 space-y-4">
                @csrf
                <div class="grid gap-3 sm:grid-cols-2">
                    <div><label class="ui-label">Plan name</label><input class="ui-input w-full" name="name" value="{{ old('name') }}" placeholder="e.g. Rcentz Elite" required></div>
                    <div><label class="ui-label">Slug</label><input class="ui-input w-full" name="slug" value="{{ old('slug') }}" placeholder="auto from name"></div>
                    <div><label class="ui-label">Price</label><input class="ui-input w-full" name="price" type="number" min="0" step="0.01" value="{{ old('price', 0) }}" required></div>
                    <div><label class="ui-label">Currency</label><input class="ui-input w-full" name="currency" maxlength="3" value="{{ old('currency', 'USD') }}" required></div>
                    <div>
                        <label class="ui-label">Billing interval</label>
                        <select class="ui-input w-full" name="billing_interval" required>
                            @foreach(['monthly'=>'Monthly','quarterly'=>'Quarterly','yearly'=>'Yearly','lifetime'=>'Lifetime','custom'=>'Custom'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('billing_interval','monthly') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label class="ui-label">Duration days</label><input class="ui-input w-full" name="duration_days" type="number" min="1" value="{{ old('duration_days', 30) }}"><p class="mt-1 text-[9px] text-muted-foreground">Ignored for lifetime plans.</p></div>
                    <div><label class="ui-label">Sort order</label><input class="ui-input w-full" name="sort_order" type="number" min="0" value="{{ old('sort_order', 0) }}"></div>
                    <label class="flex items-center gap-2 self-end pb-2 text-xs"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))><span>Plan active</span></label>
                </div>
                <div><label class="ui-label">Description</label><textarea class="ui-input w-full" name="description" rows="4" placeholder="Explain the membership value proposition.">{{ old('description') }}</textarea></div>
                <button class="ui-btn ui-btn-primary w-full justify-center">Create VIP plan</button>
            </form>
        </div>

        <div class="space-y-4">
            @forelse($plans as $plan)
                <article class="ui-panel overflow-hidden">
                    <div class="flex flex-col gap-3 border-b border-border p-5 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-semibold">{{ $plan->name }}</h2>
                                <span class="rounded-full border px-2 py-1 text-[9px] font-semibold {{ $plan->is_active ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : 'border-border bg-muted text-muted-foreground' }}">{{ $plan->is_active ? 'Active' : 'Inactive' }}</span>
                            </div>
                            <p class="mt-1 text-[10px] text-muted-foreground">{{ $plan->slug }} · {{ strtoupper($plan->currency) }} {{ number_format((float)$plan->price, 2) }} / {{ $plan->billing_interval }} · {{ $plan->memberships_count }} membership records</p>
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('admin.memberships.vip.plans.toggle', $plan) }}">@csrf @method('PATCH')<button class="ui-btn ui-btn-secondary !h-8">{{ $plan->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                            @if($plan->memberships_count === 0)
                                <form method="POST" action="{{ route('admin.memberships.vip.plans.destroy', $plan) }}" onsubmit="return confirm('Delete this unused VIP plan?');">@csrf @method('DELETE')<button class="ui-btn !h-8 border border-red-500/25 bg-red-500/10 text-red-600">Delete</button></form>
                            @endif
                        </div>
                    </div>

                    <details class="group">
                        <summary class="flex cursor-pointer items-center justify-between px-5 py-3 text-xs font-semibold hover:bg-muted/20">
                            <span>Plan configuration & entitlements</span>
                            <span class="flex items-center gap-2 text-[10px] text-muted-foreground">{{ $plan->entitlements->count() }} entitlements <i data-lucide="chevron-down" class="h-4 w-4 transition-transform group-open:rotate-180"></i></span>
                        </summary>
                        <div class="grid gap-5 border-t border-border p-5 2xl:grid-cols-[.9fr_1.1fr]">
                            <form method="POST" action="{{ route('admin.memberships.vip.plans.update', $plan) }}" class="space-y-3">
                                @csrf @method('PATCH')
                                <p class="ui-kicker">Commercial terms</p>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div><label class="ui-label">Name</label><input class="ui-input w-full" name="name" value="{{ $plan->name }}" required></div>
                                    <div><label class="ui-label">Slug</label><input class="ui-input w-full" name="slug" value="{{ $plan->slug }}" required></div>
                                    <div><label class="ui-label">Price</label><input class="ui-input w-full" name="price" type="number" min="0" step="0.01" value="{{ $plan->price }}" required></div>
                                    <div><label class="ui-label">Currency</label><input class="ui-input w-full" name="currency" maxlength="3" value="{{ $plan->currency }}" required></div>
                                    <div><label class="ui-label">Interval</label><select class="ui-input w-full" name="billing_interval">@foreach(['monthly','quarterly','yearly','lifetime','custom'] as $interval)<option value="{{ $interval }}" @selected($plan->billing_interval === $interval)>{{ ucfirst($interval) }}</option>@endforeach</select></div>
                                    <div><label class="ui-label">Duration days</label><input class="ui-input w-full" name="duration_days" type="number" min="1" value="{{ $plan->duration_days }}"></div>
                                    <div><label class="ui-label">Sort order</label><input class="ui-input w-full" name="sort_order" type="number" min="0" value="{{ $plan->sort_order }}"></div>
                                    <label class="flex items-center gap-2 self-end pb-2 text-xs"><input type="checkbox" name="is_active" value="1" @checked($plan->is_active)><span>Active</span></label>
                                </div>
                                <div><label class="ui-label">Description</label><textarea class="ui-input w-full" name="description" rows="3">{{ $plan->description }}</textarea></div>
                                <button class="ui-btn ui-btn-secondary">Save plan</button>
                            </form>

                            <div>
                                <p class="ui-kicker">Entitlements</p>
                                <p class="mt-1 text-xs text-muted-foreground">Feature keys become the authority checked by VipAccessService.</p>

                                <div class="mt-3 space-y-2">
                                    @forelse($plan->entitlements as $entitlement)
                                        <details class="rounded-xl border border-border bg-muted/10 p-3">
                                            <summary class="cursor-pointer list-none">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div><p class="text-xs font-semibold">{{ $entitlement->label }}</p><p class="mt-1 font-mono text-[9px] text-muted-foreground">{{ $entitlement->key }}</p></div>
                                                    <span class="text-[9px] {{ $entitlement->enabled ? 'text-emerald-600' : 'text-muted-foreground' }}">{{ $entitlement->enabled ? 'Enabled' : 'Disabled' }}</span>
                                                </div>
                                            </summary>
                                            <form method="POST" action="{{ route('admin.memberships.vip.entitlements.update', [$plan, $entitlement]) }}" class="mt-4 space-y-3">@csrf @method('PATCH')
                                                <div class="grid gap-3 sm:grid-cols-2"><div><label class="ui-label">Key</label><input class="ui-input w-full" name="key" value="{{ $entitlement->key }}" required></div><div><label class="ui-label">Label</label><input class="ui-input w-full" name="label" value="{{ $entitlement->label }}" required></div></div>
                                                <div><label class="ui-label">Description</label><textarea class="ui-input w-full" name="description" rows="2">{{ $entitlement->description }}</textarea></div>
                                                <div><label class="ui-label">Value JSON (optional)</label><textarea class="ui-input w-full font-mono text-xs" name="value_json" rows="2">{{ $entitlement->value === null ? '' : json_encode($entitlement->value, JSON_UNESCAPED_SLASHES) }}</textarea></div>
                                                <div class="flex flex-wrap items-center justify-between gap-2"><label class="flex items-center gap-2 text-xs"><input type="checkbox" name="enabled" value="1" @checked($entitlement->enabled)><span>Enabled</span></label><button class="ui-btn ui-btn-secondary !h-8">Save entitlement</button></div>
                                            </form>
                                            <form method="POST" action="{{ route('admin.memberships.vip.entitlements.destroy', [$plan, $entitlement]) }}" class="mt-2 flex justify-end" onsubmit="return confirm('Remove this entitlement?');">@csrf @method('DELETE')<button class="ui-btn !h-8 border border-red-500/25 bg-red-500/10 text-red-600">Remove</button></form>
                                        </details>
                                    @empty
                                        <div class="rounded-xl border border-dashed border-border p-4 text-xs text-muted-foreground">No entitlements yet. The plan currently grants no VIP-specific capabilities.</div>
                                    @endforelse
                                </div>

                                <form method="POST" action="{{ route('admin.memberships.vip.entitlements.store', $plan) }}" class="mt-4 rounded-xl border border-border p-4">@csrf
                                    <p class="text-xs font-semibold">Add entitlement</p>
                                    <div class="mt-3 grid gap-3 sm:grid-cols-2"><div><label class="ui-label">Feature key</label><input class="ui-input w-full" name="key" placeholder="signals.premium" required></div><div><label class="ui-label">Label</label><input class="ui-input w-full" name="label" placeholder="Premium signals" required></div></div>
                                    <div class="mt-3"><label class="ui-label">Description</label><input class="ui-input w-full" name="description" placeholder="What this entitlement unlocks"></div>
                                    <div class="mt-3"><label class="ui-label">Value JSON (optional)</label><input class="ui-input w-full font-mono text-xs" name="value_json" placeholder='{"limit":10}'></div>
                                    <div class="mt-3 flex items-center justify-between"><label class="flex items-center gap-2 text-xs"><input type="checkbox" name="enabled" value="1" checked><span>Enabled</span></label><button class="ui-btn ui-btn-primary !h-8">Add entitlement</button></div>
                                </form>
                            </div>
                        </div>
                    </details>
                </article>
            @empty
                <div class="ui-panel p-10 text-center text-sm text-muted-foreground">No VIP plans yet. Create the first plan from the registry form.</div>
            @endforelse
        </div>
    </section>
</div>
</x-admin-layout>
