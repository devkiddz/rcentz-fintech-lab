<x-admin-layout>
<div class="ui-page max-w-[1500px] space-y-5">
    <section class="ui-page-header">
        <div><p class="ui-kicker">Rewards Authority</p><h1 class="ui-heading">Rewards, Bonuses & Giveaways</h1><p class="ui-lead">Campaigns define eligibility and fulfillment. Cash grants settle through the wallet ledger; non-cash grants never mutate wallet balances.</p></div>
    </section>

    @if(session('success'))<div class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-600">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-xs text-red-600">{{ $errors->first() }}</div>@endif

    <section class="ui-metric-grid xl:grid-cols-4">
        <div class="ui-metric-card"><p class="ui-kicker">Campaigns</p><p class="mt-2 text-2xl font-semibold">{{ $stats['campaigns'] }}</p></div>
        <div class="ui-metric-card"><p class="ui-kicker">Active</p><p class="mt-2 text-2xl font-semibold">{{ $stats['active_campaigns'] }}</p></div>
        <div class="ui-metric-card"><p class="ui-kicker">Fulfilled grants</p><p class="mt-2 text-2xl font-semibold">{{ $stats['fulfilled_grants'] }}</p></div>
        <div class="ui-metric-card"><p class="ui-kicker">Cash distributed</p><p class="mt-2 text-2xl font-semibold">${{ number_format($stats['cash_distributed'],2) }}</p></div>
    </section>

    <section class="grid gap-5 xl:grid-cols-[.8fr_1.2fr]">
        <div class="ui-panel p-5">
            <p class="ui-kicker">Create campaign</p><h2 class="mt-1 text-lg font-semibold">Reward authority</h2>
            <form method="POST" action="{{ route('admin.rewards.store') }}" class="mt-5 space-y-3">@csrf
                <div><label class="ui-label">Name</label><input class="ui-input w-full" name="name" required></div>
                <div class="grid gap-3 sm:grid-cols-2"><div><label class="ui-label">Type</label><select class="ui-input w-full" name="campaign_type" required><option value="bonus">Bonus</option><option value="referral">Referral</option><option value="giveaway">Giveaway</option><option value="promotion">Promotion</option></select></div><div><label class="ui-label">Reward kind</label><select class="ui-input w-full" name="reward_kind" required><option value="cash">Cash</option><option value="non_cash">Non-cash</option></select></div></div>
                <div><label class="ui-label">Description</label><textarea class="ui-input w-full" name="description" rows="3"></textarea></div>
                <div class="grid gap-3 sm:grid-cols-2"><div><label class="ui-label">Cash amount</label><input class="ui-input w-full" type="number" min="0.01" step="0.01" name="cash_amount"></div><div><label class="ui-label">Non-cash label</label><input class="ui-input w-full" name="non_cash_label" placeholder="e.g. Research Access Pass"></div></div>
                <div class="grid gap-3 sm:grid-cols-2"><div><label class="ui-label">Eligibility key</label><input class="ui-input w-full" name="eligibility_key" placeholder="e.g. referral.completed"></div><div><label class="ui-label">Per-user limit</label><input class="ui-input w-full" type="number" min="1" max="100" name="per_user_limit" value="1"></div></div>
                <div class="grid gap-3 sm:grid-cols-2"><div><label class="ui-label">Max grants</label><input class="ui-input w-full" type="number" min="1" name="max_grants"></div><div><label class="ui-label">Currency</label><input class="ui-input w-full" name="currency" maxlength="3" value="USD"></div></div>
                <div class="grid gap-3 sm:grid-cols-2"><div><label class="ui-label">Starts at</label><input class="ui-input w-full" type="datetime-local" name="starts_at"></div><div><label class="ui-label">Ends at</label><input class="ui-input w-full" type="datetime-local" name="ends_at"></div></div>
                <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="is_visible" value="1" checked><span>Visible to customers</span></label>
                <button class="ui-btn ui-btn-primary w-full justify-center">Create campaign</button>
            </form>
        </div>

        <div class="space-y-4">
            @forelse($campaigns as $campaign)
                <section class="ui-panel p-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div><div class="flex items-center gap-2"><h2 class="text-base font-semibold">{{ $campaign->name }}</h2><span class="rounded-full border border-border px-2 py-1 text-[9px] font-semibold uppercase">{{ $campaign->status }}</span></div><p class="mt-1 text-xs text-muted-foreground">{{ ucfirst($campaign->campaign_type) }} · {{ $campaign->reward_kind === 'cash' ? strtoupper($campaign->currency).' '.number_format((float)$campaign->cash_amount,2) : $campaign->non_cash_label }} · {{ $campaign->grants_count }} grants</p></div>
                        <form method="POST" action="{{ route('admin.rewards.toggle',$campaign) }}">@csrf @method('PATCH')<button class="ui-btn ui-btn-secondary !h-8">{{ $campaign->status === 'active' ? 'Pause' : 'Activate' }}</button></form>
                    </div>
                    <form method="POST" action="{{ route('admin.rewards.grant',$campaign) }}" class="mt-4 grid gap-2 lg:grid-cols-[1fr_180px_220px_auto]">@csrf
                        <select class="ui-input" name="user_id" required><option value="">Select customer</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }} · {{ $user->email }}</option>@endforeach</select>
                        <input class="ui-input" name="source_type" value="admin_manual" required>
                        <input class="ui-input" name="source_reference" placeholder="Unique source reference" required>
                        <button class="ui-btn ui-btn-primary">Grant</button>
                    </form>
                </section>
            @empty
                <section class="ui-panel p-8 text-center text-xs text-muted-foreground">No reward campaigns exist yet.</section>
            @endforelse
        </div>
    </section>

    <section class="ui-panel overflow-hidden">
        <div class="border-b border-border px-5 py-4"><p class="ui-kicker">Grant audit</p><h2 class="mt-1 text-lg font-semibold">Recent reward fulfillment</h2></div>
        <div class="overflow-x-auto"><table class="w-full min-w-[1000px] text-left"><thead class="border-b border-border bg-muted/20"><tr class="text-[9px] uppercase tracking-[.1em] text-muted-foreground"><th class="px-5 py-3">Customer</th><th>Campaign</th><th>Kind</th><th>Value</th><th>Source</th><th>Reference</th><th class="px-5 py-3">Granted</th></tr></thead><tbody class="divide-y divide-border">
            @forelse($grants as $grant)<tr><td class="px-5 py-3"><p class="text-xs font-semibold">{{ $grant->user?->name }}</p><p class="text-[9px] text-muted-foreground">{{ $grant->user?->email }}</p></td><td class="text-xs">{{ $grant->campaign?->name }}</td><td class="text-xs">{{ $grant->reward_kind }}</td><td class="text-xs font-semibold">{{ $grant->reward_kind === 'cash' ? strtoupper($grant->currency).' '.number_format((float)$grant->amount,2) : data_get($grant->non_cash_payload,'label','—') }}</td><td class="text-[10px]">{{ $grant->source_type }}</td><td class="font-mono text-[9px] text-muted-foreground">{{ $grant->reference }}</td><td class="px-5 py-3 text-[10px] text-muted-foreground">{{ $grant->granted_at?->format('M j, Y H:i') }}</td></tr>
            @empty<tr><td colspan="7" class="px-5 py-10 text-center text-xs text-muted-foreground">No reward grants yet.</td></tr>@endforelse
        </tbody></table></div>
    </section>
</div>
</x-admin-layout>
