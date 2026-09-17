<x-admin-layout>
<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">VIP · Memberships</p>
            <h1 class="ui-heading">Customer VIP Lifecycle</h1>
            <p class="ui-lead max-w-3xl">Assign plans, activate memberships and close membership access without changing the customer record itself.</p>
        </div>
        <div class="ui-header-actions"><a href="{{ route('admin.memberships.vip.index') }}" class="ui-btn ui-btn-secondary">VIP plans</a></div>
    </section>

    @if(session('success'))<div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-600">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-xs text-red-600">{{ $errors->first() }}</div>@endif

    <section class="ui-metric-grid xl:grid-cols-4">
        @foreach([['Active',$stats['active']],['Pending',$stats['pending']],['Cancelled',$stats['cancelled']],['Expired',$stats['expired']]] as [$label,$value])
            <div class="ui-metric-card"><p class="ui-kicker">{{ $label }}</p><p class="mt-2 text-2xl font-semibold">{{ $value }}</p></div>
        @endforeach
    </section>

    <section class="mt-4 grid gap-4 xl:grid-cols-[.75fr_1.25fr]">
        <div class="ui-panel p-5">
            <p class="ui-kicker">Membership assignment</p>
            <h2 class="mt-1 text-lg font-semibold">Add customer membership</h2>
            <p class="mt-1 text-xs text-muted-foreground">Create as pending for review or activate immediately. Active periods cannot overlap for the same customer.</p>

            @if($plans->isEmpty())
                <div class="mt-4 rounded-xl border border-dashed border-border p-4 text-xs text-muted-foreground">Create a VIP plan before assigning customer memberships.</div>
            @else
                <form method="POST" action="{{ route('admin.memberships.vip.memberships.store') }}" class="mt-5 space-y-4">@csrf
                    <div><label class="ui-label">Customer</label><select class="ui-input w-full" name="user_id" required><option value="">Select customer</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((string)old('user_id') === (string)$user->id)>{{ $user->name }} · {{ $user->email }}</option>@endforeach</select></div>
                    <div><label class="ui-label">VIP plan</label><select class="ui-input w-full" name="vip_plan_id" required><option value="">Select plan</option>@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected((string)old('vip_plan_id') === (string)$plan->id)>{{ $plan->name }}{{ $plan->is_active ? '' : ' · inactive' }}</option>@endforeach</select></div>
                    <div class="grid gap-3 sm:grid-cols-2"><div><label class="ui-label">Price paid</label><input class="ui-input w-full" name="price_paid" type="number" min="0" step="0.01" value="{{ old('price_paid') }}" placeholder="Defaults to plan price"></div><div><label class="ui-label">Currency</label><input class="ui-input w-full" name="currency" maxlength="3" value="{{ old('currency') }}" placeholder="Defaults to plan currency"></div></div>
                    <div class="grid gap-3 sm:grid-cols-2"><div><label class="ui-label">Starts at</label><input class="ui-input w-full" name="starts_at" type="datetime-local" value="{{ old('starts_at') }}"></div><div><label class="ui-label">Ends at</label><input class="ui-input w-full" name="ends_at" type="datetime-local" value="{{ old('ends_at') }}"><p class="mt-1 text-[9px] text-muted-foreground">If blank, plan duration determines the end date.</p></div></div>
                    <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="activate_now" value="1" @checked(old('activate_now'))><span>Activate immediately</span></label>
                    <button class="ui-btn ui-btn-primary w-full justify-center">Create membership</button>
                </form>
            @endif
        </div>

        <div>
            <form method="GET" class="ui-filter-panel mb-4 grid gap-3 md:grid-cols-[1fr_180px_220px_auto]">
                <input class="ui-input w-full" name="search" value="{{ request('search') }}" placeholder="Customer, email or reference">
                <select class="ui-input w-full" name="status"><option value="">All statuses</option>@foreach(['pending','active','paused','cancelled','expired'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select>
                <select class="ui-input w-full" name="plan"><option value="">All plans</option>@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected((string)request('plan') === (string)$plan->id)>{{ $plan->name }}</option>@endforeach</select>
                <div class="flex gap-2"><button class="ui-btn ui-btn-primary">Filter</button><a class="ui-btn ui-btn-secondary" href="{{ route('admin.memberships.vip.memberships') }}">Clear</a></div>
            </form>

            <section class="ui-panel overflow-hidden">
                <div class="border-b border-border px-4 py-3"><p class="ui-kicker">Membership registry</p><p class="mt-1 text-xs text-muted-foreground">{{ $memberships->total() }} membership records</p></div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1050px] text-left">
                        <thead class="border-b border-border bg-muted/20"><tr class="text-[9px] uppercase tracking-[.1em] text-muted-foreground"><th class="px-4 py-3">Customer</th><th>Plan</th><th>Status</th><th>Period</th><th>Commercial</th><th>Reference</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
                        <tbody class="divide-y divide-border">
                            @forelse($memberships as $membership)
                                @php
                                    $visualStatus = $membership->status === 'active' && $membership->ends_at && ! $membership->ends_at->isFuture() ? 'expired' : $membership->status;
                                @endphp
                                <tr>
                                    <td class="px-4 py-3"><p class="text-xs font-semibold">{{ $membership->user->name }}</p><p class="mt-1 text-[9px] text-muted-foreground">{{ $membership->user->email }}</p></td>
                                    <td><p class="text-xs font-semibold">{{ $membership->plan->name }}</p><p class="mt-1 text-[9px] text-muted-foreground">{{ $membership->plan->slug }}</p></td>
                                    <td><span class="rounded-full border px-2 py-1 text-[9px] font-semibold {{ $visualStatus === 'active' ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : ($visualStatus === 'pending' ? 'border-amber-500/20 bg-amber-500/10 text-amber-600' : 'border-border bg-muted text-muted-foreground') }}">{{ ucfirst($visualStatus) }}</span></td>
                                    <td class="text-[10px]"><p>{{ $membership->starts_at?->format('M d, Y H:i') ?? 'Not started' }}</p><p class="mt-1 text-muted-foreground">to {{ $membership->ends_at?->format('M d, Y H:i') ?? 'No fixed end' }}</p></td>
                                    <td class="text-xs"><p>{{ strtoupper($membership->currency ?? $membership->plan->currency) }} {{ number_format((float)($membership->price_paid ?? $membership->plan->price), 2) }}</p><p class="mt-1 text-[9px] text-muted-foreground">{{ $membership->source ?? '—' }}</p></td>
                                    <td class="font-mono text-[9px] text-muted-foreground">{{ $membership->reference ?? '—' }}</td>
                                    <td class="px-4 py-3"><div class="flex justify-end gap-2">
                                        @if($membership->status === 'pending' || $membership->status === 'paused')
                                            <form method="POST" action="{{ route('admin.memberships.vip.memberships.activate', $membership) }}">@csrf @method('PATCH')<button class="ui-btn ui-btn-primary !h-8">Activate</button></form>
                                        @endif
                                        @if(!in_array($membership->status, ['cancelled','expired'], true))
                                            <form method="POST" action="{{ route('admin.memberships.vip.memberships.expire', $membership) }}" onsubmit="return confirm('Expire this VIP membership now?');">@csrf @method('PATCH')<button class="ui-btn ui-btn-secondary !h-8">Expire</button></form>
                                            <form method="POST" action="{{ route('admin.memberships.vip.memberships.cancel', $membership) }}" onsubmit="return confirm('Cancel this VIP membership?');">@csrf @method('PATCH')<button class="ui-btn !h-8 border border-red-500/25 bg-red-500/10 text-red-600">Cancel</button></form>
                                        @endif
                                    </div></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-10 text-center text-xs text-muted-foreground">No VIP memberships match this view.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">{{ $memberships->links() }}</div>
            </section>
        </div>
    </section>
</div>
</x-admin-layout>
