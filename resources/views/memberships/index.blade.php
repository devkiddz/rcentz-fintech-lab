<x-user-layout>
    <x-slot name="header">Membership</x-slot>

    @php
        $displayStatus = $currentMembership
            ? ($currentMembership->status === 'active' && ! $currentMembership->is_active
                ? 'expired'
                : $currentMembership->status)
            : null;
    @endphp

    <div class="ui-page max-w-[1200px]">
        <section class="ui-page-header">
            <div>
                <p class="ui-kicker">Account access</p>
                <h1 class="ui-heading">Membership</h1>
                <p class="ui-lead">Review your current membership plan, status and included privileges.</p>
            </div>
            <div class="ui-header-actions">
                @if($currentMembership)
                    <span class="ui-btn ui-btn-ghost pointer-events-none">
                        <i data-lucide="badge-check" class="h-4 w-4"></i>
                        {{ ucfirst($displayStatus ?? 'unknown') }}
                    </span>
                @endif
                <a href="{{ route('profile.edit') }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="settings" class="h-4 w-4"></i>
                    Account settings
                </a>
            </div>
        </section>

        @if($currentMembership)
            <section class="ui-panel overflow-hidden">
                <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex min-w-0 items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-border bg-muted/40 text-foreground">
                            <i data-lucide="badge-check" class="h-5 w-5"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="ui-kicker">Current plan</p>
                            <h2 class="mt-1 text-xl font-semibold text-foreground">
                                {{ $currentMembership->plan?->name ?? 'Membership Plan' }}
                            </h2>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                                {{ $currentMembership->plan?->description ?: 'Your membership controls access to eligible platform services and privileges.' }}
                            </p>
                        </div>
                    </div>

                    <span class="rounded-full px-3 py-1 text-[10px] font-semibold uppercase tracking-wide {{ $currentMembership->is_active ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-muted text-muted-foreground' }}">
                        {{ ucfirst($displayStatus ?? 'unknown') }}
                    </span>
                </div>

                <div class="grid border-t border-border sm:grid-cols-2 lg:grid-cols-4">
                    <div class="p-5 sm:p-6">
                        <p class="ui-label">Status</p>
                        <p class="mt-1 text-sm font-semibold text-foreground">{{ ucfirst($displayStatus ?? 'Unknown') }}</p>
                    </div>
                    <div class="border-t border-border p-5 sm:border-l sm:border-t-0 sm:p-6">
                        <p class="ui-label">Started</p>
                        <p class="mt-1 text-sm font-semibold text-foreground">
                            {{ $currentMembership->starts_at?->format('M j, Y') ?? 'Not started' }}
                        </p>
                    </div>
                    <div class="border-t border-border p-5 lg:border-l lg:border-t-0 lg:p-6">
                        <p class="ui-label">Expires</p>
                        <p class="mt-1 text-sm font-semibold text-foreground">
                            {{ $currentMembership->ends_at?->format('M j, Y') ?? 'No fixed expiry' }}
                        </p>
                    </div>
                    <div class="border-t border-border p-5 sm:border-l lg:border-t-0 sm:p-6">
                        <p class="ui-label">Remaining</p>
                        <p class="mt-1 text-sm font-semibold text-foreground">
                            @if($currentMembership->is_active && $currentMembership->days_remaining !== null)
                                {{ number_format($currentMembership->days_remaining) }} day{{ $currentMembership->days_remaining === 1 ? '' : 's' }}
                            @elseif($currentMembership->is_active)
                                Ongoing
                            @else
                                Not active
                            @endif
                        </p>
                    </div>
                </div>
            </section>

            <section class="mt-5 ui-panel p-5 sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="ui-kicker">Included access</p>
                        <h2 class="mt-1 text-lg font-semibold text-foreground">Membership privileges</h2>
                        <p class="mt-1 text-sm text-muted-foreground">These are the services and benefits currently attached to your active membership.</p>
                    </div>
                    @if($currentMembership->is_active)
                        <span class="ui-btn ui-btn-ghost pointer-events-none">
                            {{ number_format($privileges->count()) }} privilege{{ $privileges->count() === 1 ? '' : 's' }}
                        </span>
                    @endif
                </div>

                @if(! $currentMembership->is_active)
                    <div class="mt-5 rounded-xl border border-border bg-muted/20 p-5">
                        <div class="flex items-start gap-3">
                            <i data-lucide="lock-keyhole" class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground"></i>
                            <div>
                                <p class="text-sm font-semibold text-foreground">Privileges are not currently active</p>
                                <p class="mt-1 text-sm leading-6 text-muted-foreground">Your latest membership record is {{ $displayStatus ?? 'inactive' }}. Included privileges become available when a membership is active.</p>
                            </div>
                        </div>
                    </div>
                @elseif($privileges->isEmpty())
                    <div class="mt-5 rounded-xl border border-border bg-muted/20 p-5">
                        <div class="flex items-start gap-3">
                            <i data-lucide="badge-info" class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground"></i>
                            <div>
                                <p class="text-sm font-semibold text-foreground">No privileges have been listed yet</p>
                                <p class="mt-1 text-sm leading-6 text-muted-foreground">Your membership is active. The platform will show included service privileges here as they are configured.</p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        @foreach($privileges as $privilege)
                            <div class="rounded-xl border border-border bg-muted/20 p-4">
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                        <i data-lucide="check" class="h-4 w-4"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-foreground">{{ $privilege->label }}</p>
                                        @if($privilege->description)
                                            <p class="mt-1 text-sm leading-6 text-muted-foreground">{{ $privilege->description }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @else
            <section class="ui-panel p-8 text-center sm:p-10">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-border bg-muted">
                    <i data-lucide="badge" class="h-5 w-5"></i>
                </div>
                <h2 class="mt-4 text-lg font-semibold text-foreground">No membership assigned</h2>
                <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-muted-foreground">There is currently no membership record attached to your account. Your membership details and privileges will appear here when one becomes available.</p>
            </section>
        @endif
    </div>
</x-user-layout>
