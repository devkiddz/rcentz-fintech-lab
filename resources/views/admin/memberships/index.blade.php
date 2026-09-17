<x-admin-layout>
    <x-slot name="header">
        <div>
            <div class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[.18em] text-muted-foreground"><span>Admin</span><span>•</span><span>Memberships</span></div>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-foreground">Membership Control</h1>
            <p class="mt-1 text-sm text-muted-foreground">One administrative domain for every membership product and its lifecycle authority.</p>
        </div>
    </x-slot>

    <div class="ui-page max-w-[1500px]">
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="ui-metric-card"><div class="ui-metric-icon"><i data-lucide="layers-3" class="h-5 w-5"></i></div><div><p class="ui-label">VIP plans</p><p class="mt-1 text-2xl font-semibold">{{ number_format($stats['vip_plans']) }}</p></div></article>
            <article class="ui-metric-card"><div class="ui-metric-icon"><i data-lucide="circle-check" class="h-5 w-5"></i></div><div><p class="ui-label">Active VIP plans</p><p class="mt-1 text-2xl font-semibold">{{ number_format($stats['vip_active_plans']) }}</p></div></article>
            <article class="ui-metric-card"><div class="ui-metric-icon"><i data-lucide="crown" class="h-5 w-5"></i></div><div><p class="ui-label">Active VIP members</p><p class="mt-1 text-2xl font-semibold">{{ number_format($stats['vip_active_memberships']) }}</p></div></article>
            <article class="ui-metric-card"><div class="ui-metric-icon"><i data-lucide="clock-3" class="h-5 w-5"></i></div><div><p class="ui-label">Pending VIP</p><p class="mt-1 text-2xl font-semibold">{{ number_format($stats['vip_pending_memberships']) }}</p></div></article>
        </section>

        <section class="mt-4 ui-panel p-5 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-amber-500/20 bg-amber-500/10 text-amber-600 dark:text-amber-400"><i data-lucide="crown" class="h-5 w-5"></i></div>
                    <div><p class="ui-kicker">Membership type</p><h2 class="mt-1 text-lg font-semibold">VIP Membership</h2><p class="mt-1 text-sm text-muted-foreground">Plans, entitlements, assignments and lifecycle controls remain owned by the VIP admin authority.</p></div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.memberships.vip.index') }}" class="ui-btn ui-btn-secondary"><i data-lucide="layers-3" class="h-4 w-4"></i>Plans & Entitlements</a>
                    <a href="{{ route('admin.memberships.vip.memberships') }}" class="ui-btn ui-btn-primary"><i data-lucide="badge-check" class="h-4 w-4"></i>Memberships</a>
                </div>
            </div>
        </section>
    </div>
</x-admin-layout>
