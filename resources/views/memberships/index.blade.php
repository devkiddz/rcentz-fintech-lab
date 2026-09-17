<x-user-layout>
    <x-slot name="header">Memberships</x-slot>

    <div class="ui-page max-w-[1440px]">
        <section class="ui-page-header">
            <div>
                <p class="ui-kicker">Account access</p>
                <h1 class="ui-heading">Memberships</h1>
                <p class="ui-lead">Your membership products live in one workspace. Each membership type keeps its own plans, benefits and lifecycle.</p>
            </div>
            <div class="ui-header-actions">
                <a href="{{ route('profile.edit') }}" class="ui-btn ui-btn-secondary"><i data-lucide="settings" class="h-4 w-4"></i>Account settings</a>
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            @php
                $vipDisplayStatus = $vipMembership
                    ? ($activeVipMembership ? 'active' : ($vipMembership->status === 'active' && ! $vipMembership->is_active ? 'expired' : $vipMembership->status))
                    : null;
            @endphp

            <a href="{{ route('memberships.vip.index') }}" class="ui-panel group block p-5 transition hover:-translate-y-0.5 hover:shadow-md sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-amber-500/20 bg-amber-500/10 text-amber-600 dark:text-amber-400">
                            <i data-lucide="crown" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <p class="ui-kicker">Membership type</p>
                            <h2 class="mt-1 text-lg font-semibold text-foreground">VIP Membership</h2>
                            <p class="mt-1 text-sm leading-6 text-muted-foreground">Premium plans and entitlements managed under the VIP membership domain.</p>
                        </div>
                    </div>
                    <i data-lucide="arrow-right" class="h-4 w-4 text-muted-foreground transition group-hover:translate-x-0.5"></i>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-border bg-muted/20 p-3">
                        <p class="ui-label">Status</p>
                        <p class="mt-1 text-sm font-semibold text-foreground">{{ $vipDisplayStatus ? ucfirst($vipDisplayStatus) : 'Not enrolled' }}</p>
                    </div>
                    <div class="rounded-xl border border-border bg-muted/20 p-3">
                        <p class="ui-label">Current plan</p>
                        <p class="mt-1 truncate text-sm font-semibold text-foreground">{{ $vipMembership?->plan?->name ?? 'None' }}</p>
                    </div>
                    <div class="rounded-xl border border-border bg-muted/20 p-3">
                        <p class="ui-label">Available plans</p>
                        <p class="mt-1 text-sm font-semibold text-foreground">{{ number_format($availableVipPlans) }}</p>
                    </div>
                </div>
            </a>

            <article class="ui-panel p-5 sm:p-6">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-border bg-muted text-muted-foreground"><i data-lucide="blocks" class="h-5 w-5"></i></div>
                    <div><p class="ui-kicker">Extensible domain</p><h2 class="mt-1 text-lg font-semibold">More membership types</h2><p class="mt-1 text-sm leading-6 text-muted-foreground">Future membership products will be added here without turning VIP into the parent domain.</p></div>
                </div>
            </article>
        </section>
    </div>
</x-user-layout>
