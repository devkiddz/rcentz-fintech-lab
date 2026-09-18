<x-admin-layout>
    <div class="ui-page max-w-[1500px] space-y-4">
        <section class="ui-panel overflow-hidden">
            <div class="relative p-5 sm:p-6">
                <div class="absolute inset-y-0 right-0 hidden w-1/3 bg-gradient-to-l from-red-500/[.05] to-transparent lg:block"></div>
                <div class="relative flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[.18em] text-muted-foreground"><span>Admin</span><span>•</span><span>Signal Engine</span><span>•</span><span>Recipients</span></div>
                        <h1 class="mt-2 text-2xl font-semibold tracking-tight">Signal Recipients</h1>
                        <p class="mt-1 max-w-3xl text-sm leading-6 text-muted-foreground">See exactly who received each Signal, distinguish individual complimentary access from general distribution, and drill into the customer, Signal or delivery batch without deleting audit history.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.signals.live') }}" class="ui-btn ui-btn-secondary"><i data-lucide="radio-tower" class="h-4 w-4"></i>Live Signals</a>
                        <a href="{{ route('admin.signals.activity') }}" class="ui-btn ui-btn-secondary"><i data-lucide="activity" class="h-4 w-4"></i>Engine activity</a>
                    </div>
                </div>
            </div>
        </section>

        @include('admin.signals._nav')

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="ui-metric-card"><div class="ui-metric-icon"><i data-lucide="radio-tower" class="h-5 w-5"></i></div><div><p class="ui-label">Current live access</p><p class="mt-1 text-2xl font-semibold">{{ number_format($stats['current']) }}</p><p class="mt-1 text-[10px] text-muted-foreground">Published or active Signals</p></div></article>
            <article class="ui-metric-card"><div class="ui-metric-icon"><i data-lucide="user-round-check" class="h-5 w-5"></i></div><div><p class="ui-label">Individual</p><p class="mt-1 text-2xl font-semibold">{{ number_format($stats['individual']) }}</p><p class="mt-1 text-[10px] text-muted-foreground">Explicit customer access</p></div></article>
            <article class="ui-metric-card"><div class="ui-metric-icon"><i data-lucide="users-round" class="h-5 w-5"></i></div><div><p class="ui-label">General</p><p class="mt-1 text-2xl font-semibold">{{ number_format($stats['general']) }}</p><p class="mt-1 text-[10px] text-muted-foreground">Membership / broad distribution</p></div></article>
            <article class="ui-metric-card"><div class="ui-metric-icon"><i data-lucide="contact-round" class="h-5 w-5"></i></div><div><p class="ui-label">Customers</p><p class="mt-1 text-2xl font-semibold">{{ number_format($stats['customers']) }}</p><p class="mt-1 text-[10px] text-muted-foreground">{{ number_format($stats['deliveries']) }} total deliveries</p></div></article>
        </section>

        <section class="ui-panel p-4 sm:p-5">
            <form method="GET" action="{{ route('admin.signals.recipients') }}" class="grid gap-3 md:grid-cols-2 xl:grid-cols-6 xl:items-end">
                <div class="xl:col-span-2">
                    <label class="ui-label">Search customer or asset</label>
                    <input name="search" value="{{ $search }}" class="ui-input w-full" placeholder="Name, email, AAPL...">
                </div>
                <div>
                    <label class="ui-label">Access</label>
                    <select name="scope" class="ui-input w-full">
                        <option value="all" @selected($scope === 'all')>All access</option>
                        <option value="individual" @selected($scope === 'individual')>Individual</option>
                        <option value="general" @selected($scope === 'general')>General</option>
                    </select>
                </div>
                <div>
                    <label class="ui-label">Distribution</label>
                    <select name="mode" class="ui-input w-full">
                        <option value="all" @selected($mode === 'all')>All modes</option>
                        <option value="complimentary" @selected($mode === 'complimentary')>Complimentary</option>
                        <option value="membership" @selected($mode === 'membership')>Membership</option>
                    </select>
                </div>
                <div>
                    <label class="ui-label">Signal status</label>
                    <select name="status" class="ui-input w-full">
                        <option value="all" @selected($status === 'all')>All statuses</option>
                        @foreach(array_merge(\App\Models\Signal::OPEN_STATUSES, \App\Models\Signal::TERMINAL_STATUSES) as $signalStatus)
                            <option value="{{ $signalStatus }}" @selected($status === $signalStatus)>{{ ucfirst($signalStatus) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ui-label">Signal ID</label>
                    <input name="signal_id" type="number" min="1" value="{{ $signalId ?: '' }}" class="ui-input w-full" placeholder="#">
                </div>
                <div class="md:col-span-2 xl:col-span-6 flex flex-wrap justify-end gap-2">
                    <a href="{{ route('admin.signals.recipients') }}" class="ui-btn ui-btn-secondary">Clear</a>
                    <button class="ui-btn ui-btn-primary"><i data-lucide="search" class="h-4 w-4"></i>Filter recipients</button>
                </div>
            </form>
        </section>

        <section class="ui-panel overflow-hidden">
            <div class="flex flex-col gap-2 border-b border-border px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div><p class="ui-kicker">Delivery authority</p><h2 class="mt-1 text-base font-semibold">Who currently has Signals</h2></div>
                <p class="text-[10px] text-muted-foreground">Historical deliveries remain immutable audit records.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1120px] w-full text-left">
                    <thead class="border-b border-border bg-muted/20 text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3">Customer</th>
                            <th class="px-4 py-3">Signal</th>
                            <th class="px-4 py-3">Access</th>
                            <th class="px-4 py-3">Distribution</th>
                            <th class="px-4 py-3">Signal state</th>
                            <th class="px-4 py-3">Delivered</th>
                            <th class="px-4 py-3">Batch</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($deliveries as $delivery)
                            @php
                                $requested = (int) data_get($delivery->distribution?->audience_snapshot, 'requested_recipients', 0);
                                $audienceScope = data_get($delivery->metadata, 'audience_scope')
                                    ?? data_get($delivery->distribution?->audience_snapshot, 'audience_scope')
                                    ?? (($delivery->distribution?->mode === 'complimentary' && $requested === 1) ? 'individual' : 'general');
                                $individual = $audienceScope === 'individual';
                                $signal = $delivery->signal;
                            @endphp
                            <tr class="align-top transition-colors hover:bg-muted/15">
                                <td class="px-5 py-4">
                                    <p class="text-xs font-semibold">{{ $delivery->user?->name ?? 'Unknown customer' }}</p>
                                    <p class="mt-1 text-[10px] text-muted-foreground">{{ $delivery->user?->email ?? '—' }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2"><span class="text-xs font-semibold">{{ $signal?->instrument_symbol ?? '—' }}</span><span class="rounded-full px-2 py-0.5 text-[8px] font-semibold uppercase {{ $signal?->direction === 'sell' ? 'bg-red-500/10 text-red-600' : 'bg-emerald-500/10 text-emerald-600' }}">{{ $signal?->direction ?? '—' }}</span></div>
                                    <p class="mt-1 text-[10px] text-muted-foreground">Signal #{{ $delivery->signal_id }} · {{ strtoupper((string) ($signal?->timeframe ?? '—')) }} · {{ strtoupper((string) ($signal?->marketplace ?? '—')) }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[9px] font-semibold uppercase {{ $individual ? 'bg-violet-500/10 text-violet-600 dark:text-violet-400' : 'bg-blue-500/10 text-blue-600 dark:text-blue-400' }}">{{ $audienceScope }}</span>
                                    <p class="mt-1 text-[9px] text-muted-foreground">{{ $individual ? 'Explicit customer' : 'General audience' }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-[10px] font-semibold uppercase">{{ $delivery->distribution?->mode ?? $delivery->reason }}</p>
                                    <p class="mt-1 max-w-48 truncate text-[9px] text-muted-foreground" title="{{ data_get($delivery->metadata, 'distribution_reason') }}">{{ data_get($delivery->metadata, 'distribution_reason') ?: '—' }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full border border-border bg-muted/20 px-2 py-1 text-[9px] font-semibold uppercase">{{ $signal?->status ?? '—' }}</span>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-[10px] font-semibold">{{ $delivery->delivered_at?->format('M j, Y') ?? '—' }}</p>
                                    <p class="mt-1 text-[9px] text-muted-foreground">{{ $delivery->delivered_at?->format('g:i A') ?? '—' }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="text-[10px] font-semibold">#{{ $delivery->distribution_id }}</p>
                                    <p class="mt-1 text-[9px] text-muted-foreground">{{ $delivery->distribution?->initiatedBy?->name ?? 'System' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        @if($delivery->user)<a href="{{ route('admin.users.show', $delivery->user) }}" class="ui-btn ui-btn-secondary ui-btn-sm" title="Open customer"><i data-lucide="user-round" class="h-3.5 w-3.5"></i></a>@endif
                                        @if($signal)<a href="{{ route('admin.signals.show', $signal) }}" class="ui-btn ui-btn-primary ui-btn-sm"><i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>Signal</a>@endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-5 py-12 text-center"><i data-lucide="users-round" class="mx-auto h-6 w-6 text-muted-foreground"></i><p class="mt-3 text-sm font-semibold">No recipients match this view</p><p class="mt-1 text-xs text-muted-foreground">Deliver a published Signal or change the filters.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($deliveries->hasPages())
                <div class="border-t border-border px-5 py-4">{{ $deliveries->links() }}</div>
            @endif
        </section>
    </div>
</x-admin-layout>
