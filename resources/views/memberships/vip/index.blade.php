<x-user-layout>
    <x-slot name="header">VIP Membership</x-slot>

    <div class="ui-page max-w-[1440px]">
        <section class="ui-page-header">
            <div>
                <p class="ui-kicker">Premium access</p>
                <h1 class="ui-heading">VIP Membership</h1>
                <p class="ui-lead">See your current membership, premium entitlements and available VIP plans.</p>
            </div>
            <div class="ui-header-actions">
                <a href="{{ route('memberships.index') }}" class="ui-btn ui-btn-ghost"><i data-lucide="arrow-left" class="h-4 w-4"></i>All memberships</a>
                <a href="{{ route('profile.edit') }}" class="ui-btn ui-btn-secondary"><i data-lucide="user-round-cog" class="h-4 w-4"></i>Membership settings</a>
                <a href="{{ route('support.index') }}" class="ui-btn ui-btn-secondary"><i data-lucide="headphones" class="h-4 w-4"></i>Support</a>
            </div>
        </section>

        @php
            $vipDisplayStatus = null;
            if ($vipMembership) {
                $vipDisplayStatus = $activeMembership
                    ? 'active'
                    : ($vipMembership->status === 'active' && ! $vipMembership->is_active ? 'expired' : $vipMembership->status);
            }
        @endphp

        <section class="grid gap-4 lg:grid-cols-[.78fr_1.22fr]">
            <article class="ui-panel overflow-hidden">
                <div class="border-b border-border px-5 py-4 sm:px-6">
                    <div class="flex items-center justify-between gap-4">
                        <div><p class="ui-kicker">Your membership</p><h2 class="mt-1 text-lg font-semibold text-foreground">Current VIP status</h2></div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-amber-500/20 bg-amber-500/10 text-amber-600 dark:text-amber-400"><i data-lucide="crown" class="h-5 w-5"></i></div>
                    </div>
                </div>
                <div class="p-5 sm:p-6">
                    @if($vipMembership)
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-xl font-semibold text-foreground">{{ $vipMembership->plan?->name ?? 'VIP Membership' }}</p>
                                <p class="mt-1 text-sm text-muted-foreground">Reference {{ $vipMembership->reference ?: 'Not assigned' }}</p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide {{ $vipDisplayStatus === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : ($vipDisplayStatus === 'pending' ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400' : 'bg-muted text-muted-foreground') }}">{{ $vipDisplayStatus }}</span>
                        </div>
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl border border-border bg-muted/20 p-4"><p class="ui-label">Started</p><p class="mt-1 text-sm font-medium text-foreground">{{ $vipMembership->starts_at?->format('M j, Y · g:i A') ?? 'Not started' }}</p></div>
                            <div class="rounded-xl border border-border bg-muted/20 p-4"><p class="ui-label">Ends</p><p class="mt-1 text-sm font-medium text-foreground">{{ $vipMembership->ends_at?->format('M j, Y · g:i A') ?? 'No fixed expiry' }}</p></div>
                        </div>

                        @if($activeMembership)
                            <div class="mt-5">
                                <div class="flex items-center justify-between gap-3"><div><p class="ui-kicker">Entitlements</p><h3 class="mt-1 font-semibold text-foreground">Included access</h3></div><span class="rounded-full bg-muted px-2.5 py-1 text-[10px] font-semibold text-muted-foreground">{{ $vipEntitlements->count() }}</span></div>
                                <div class="mt-3 space-y-2">
                                    @forelse($vipEntitlements as $entitlement)
                                        <div class="flex items-start gap-3 rounded-xl border border-border bg-muted/20 p-3">
                                            <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"><i data-lucide="check" class="h-3.5 w-3.5"></i></div>
                                            <div class="min-w-0"><p class="text-sm font-medium text-foreground">{{ $entitlement->label }}</p><p class="mt-0.5 text-xs text-muted-foreground">{{ $entitlement->description ?: $entitlement->key }}</p></div>
                                        </div>
                                    @empty
                                        <p class="rounded-xl border border-dashed border-border p-4 text-sm text-muted-foreground">This plan has no enabled entitlements yet.</p>
                                    @endforelse
                                </div>
                            </div>
                        @else
                            <p class="mt-5 rounded-xl border border-border bg-muted/20 p-4 text-sm text-muted-foreground">This membership is not currently active. Premium entitlements remain unavailable until an active period is in force.</p>
                        @endif
                    @else
                        <div class="rounded-2xl border border-dashed border-border bg-muted/15 p-6 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-border bg-card"><i data-lucide="crown" class="h-5 w-5"></i></div>
                            <h3 class="mt-4 font-semibold text-foreground">No VIP membership yet</h3>
                            <p class="mt-1 text-sm text-muted-foreground">Available plans are shown beside this card. Membership purchase automation will be connected in the next VIP phase.</p>
                        </div>
                    @endif
                    <div class="mt-5 flex flex-wrap gap-2 border-t border-border pt-4">
                        <a href="{{ route('support.index') }}" class="ui-btn ui-btn-secondary ui-btn-sm"><i data-lucide="message-circle" class="h-4 w-4"></i>Ask about VIP</a>
                        <a href="{{ route('profile.edit') }}" class="ui-btn ui-btn-ghost ui-btn-sm"><i data-lucide="settings" class="h-4 w-4"></i>Account settings</a>
                    </div>
                </div>
            </article>

            <article class="ui-panel overflow-hidden">
                <div class="border-b border-border px-5 py-4 sm:px-6">
                    <p class="ui-kicker">Available plans</p>
                    <h2 class="mt-1 text-lg font-semibold text-foreground">Choose the access level that fits</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Plan pricing and benefits are controlled by the VIP administration authority.</p>
                </div>
                <div class="grid gap-3 p-4 sm:p-5 xl:grid-cols-2">
                    @forelse($plans as $plan)
                        @php $isCurrentPlan = $vipMembership?->vip_plan_id === $plan->id; @endphp
                        <section class="rounded-2xl border {{ $isCurrentPlan ? 'border-amber-500/30 bg-amber-500/[.04]' : 'border-border bg-card' }} p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div><p class="text-base font-semibold text-foreground">{{ $plan->name }}</p><p class="mt-1 text-xs text-muted-foreground">{{ ucfirst($plan->billing_interval ?: 'membership') }}{{ $plan->duration_days ? ' · '.$plan->duration_days.' days' : '' }}</p></div>
                                @if($isCurrentPlan)<span class="rounded-full bg-amber-500/10 px-2 py-1 text-[9px] font-semibold uppercase text-amber-600 dark:text-amber-400">Current</span>@endif
                            </div>
                            <div class="mt-4"><span class="text-2xl font-semibold text-foreground">{{ $plan->currency }} {{ number_format((float) $plan->price, 2) }}</span></div>
                            @if($plan->description)<p class="mt-3 text-sm leading-6 text-muted-foreground">{{ $plan->description }}</p>@endif
                            <div class="mt-4 space-y-2">
                                @forelse($plan->entitlements as $entitlement)
                                    <div class="flex items-start gap-2 text-xs text-muted-foreground"><i data-lucide="check-circle-2" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-500"></i><span>{{ $entitlement->label }}</span></div>
                                @empty
                                    <p class="text-xs text-muted-foreground">Benefits will appear when entitlements are configured.</p>
                                @endforelse
                            </div>
                            <div class="mt-5 border-t border-border pt-4">
                                @if($isCurrentPlan)
                                    <span class="ui-btn ui-btn-secondary ui-btn-sm pointer-events-none opacity-70"><i data-lucide="badge-check" class="h-4 w-4"></i>Membership record</span>
                                @else
                                    <a href="{{ route('support.index') }}" class="ui-btn ui-btn-secondary ui-btn-sm"><i data-lucide="message-circle" class="h-4 w-4"></i>Ask about this plan</a>
                                @endif
                            </div>
                        </section>
                    @empty
                        <div class="rounded-xl border border-dashed border-border p-6 text-sm text-muted-foreground xl:col-span-2">No active VIP plans are currently available.</div>
                    @endforelse
                </div>
            </article>
        </section>
    </div>
</x-user-layout>
