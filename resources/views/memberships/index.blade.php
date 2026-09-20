<x-user-layout>
<x-slot name="header">Membership</x-slot>

@php
    $displayStatus = $currentMembership
        ? ($currentMembership->status === 'active' && ! $currentMembership->is_active
            ? 'expired'
            : $currentMembership->status)
        : null;
@endphp

<div class="ui-page max-w-[1280px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Account access</p>
            <h1 class="ui-heading">Membership</h1>
            <p class="ui-lead">Your commercial access authority, active privileges and available membership plans.</p>
        </div>
        <div class="ui-header-actions">
            <span class="ui-btn ui-btn-ghost pointer-events-none">
                Wallet {{ currency_symbol() }}{{ number_format($walletAvailable, 2) }}
            </span>
            @if($currentMembership)
                <span class="ui-btn ui-btn-ghost pointer-events-none">
                    <i data-lucide="badge-check" class="h-4 w-4"></i>
                    {{ ucfirst($displayStatus ?? 'unknown') }}
                </span>
            @endif
        </div>
    </section>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-600">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-xs text-red-600">
            {{ $errors->first() }}
        </div>
    @endif

    @if($currentMembership)
        <section class="ui-panel overflow-hidden">
            <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex min-w-0 items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-border bg-muted/40 text-foreground">
                        <i data-lucide="badge-check" class="h-5 w-5"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="ui-kicker">Current membership</p>
                        <h2 class="mt-1 text-xl font-semibold text-foreground">
                            {{ $currentMembership->plan?->type?->name ?? 'Membership' }} · {{ $currentMembership->plan?->name ?? 'Plan' }}
                        </h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                            {{ $currentMembership->plan?->description ?: 'Your membership controls access to eligible platform services and privileges.' }}
                        </p>
                        <p class="mt-2 text-[10px] text-muted-foreground">
                            Authority: {{ str_replace('_', ' ', ucfirst($currentMembership->source ?? 'unknown')) }}
                            @if($currentMembership->reference) · {{ $currentMembership->reference }} @endif
                        </p>
                    </div>
                </div>
                <span class="rounded-full px-3 py-1 text-[10px] font-semibold uppercase tracking-wide {{ $currentMembership->is_active ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-muted text-muted-foreground' }}">
                    {{ ucfirst($displayStatus ?? 'unknown') }}
                </span>
            </div>

            <div class="grid border-t border-border sm:grid-cols-2 lg:grid-cols-4">
                @foreach([
                    ['Status', ucfirst($displayStatus ?? 'Unknown')],
                    ['Started', $currentMembership->starts_at?->format('M j, Y') ?? 'Not started'],
                    ['Expires', $currentMembership->ends_at?->format('M j, Y') ?? 'No fixed expiry'],
                    ['Paid', strtoupper($currentMembership->currency ?? 'USD').' '.number_format((float)$currentMembership->price_paid, 2)],
                ] as [$label,$value])
                    <div class="border-t border-border p-5 first:border-t-0 sm:border-l sm:first:border-l-0 sm:border-t-0 sm:p-6">
                        <p class="ui-label">{{ $label }}</p>
                        <p class="mt-1 text-sm font-semibold text-foreground">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="mt-5 ui-panel p-5 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="ui-kicker">Included access</p>
                    <h2 class="mt-1 text-lg font-semibold text-foreground">Membership privileges</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Privileges become authoritative only while the membership is active.</p>
                </div>
                @if($currentMembership->is_active)
                    <span class="ui-btn ui-btn-ghost pointer-events-none">{{ number_format($privileges->count()) }} privileges</span>
                @endif
            </div>

            @if(! $currentMembership->is_active)
                <div class="mt-5 rounded-xl border border-border bg-muted/20 p-5 text-sm text-muted-foreground">
                    The latest membership record is {{ $displayStatus ?? 'inactive' }}. Its privileges are not active.
                </div>
            @elseif($privileges->isEmpty())
                <div class="mt-5 rounded-xl border border-border bg-muted/20 p-5 text-sm text-muted-foreground">
                    This membership is active, but no privileges have been configured on its plan yet.
                </div>
            @else
                <div class="mt-5 grid gap-3 md:grid-cols-2">
                    @foreach($privileges as $privilege)
                        <div class="rounded-xl border border-border bg-muted/20 p-4">
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600">
                                    <i data-lucide="check" class="h-4 w-4"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold">{{ $privilege->label }}</p>
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
        <section class="ui-panel p-7">
            <p class="ui-kicker">No active membership</p>
            <h2 class="mt-1 text-lg font-semibold">Choose a plan when you are ready</h2>
            <p class="mt-2 text-sm leading-6 text-muted-foreground">
                A paid membership becomes active only after the wallet payment is completed by the financial authority.
            </p>
        </section>
    @endif

    <section class="mt-5">
        <div class="mb-3">
            <p class="ui-kicker">Available access</p>
            <h2 class="mt-1 text-lg font-semibold">Membership plans</h2>
            <p class="mt-1 text-xs text-muted-foreground">Plans are grouped by membership type. Different membership types may coexist.</p>
        </div>

        @forelse($availableTypes as $type)
            @php $activeForType = $activeMemberships->get($type->slug); @endphp
            <div class="ui-panel mb-4 overflow-hidden">
                <div class="border-b border-border px-5 py-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="ui-kicker">{{ $type->name }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">{{ $type->description }}</p>
                        </div>
                        @if($activeForType)
                            <span class="rounded-full bg-emerald-500/10 px-2.5 py-1 text-[9px] font-semibold uppercase text-emerald-600">Active</span>
                        @endif
                    </div>
                </div>

                <div class="grid gap-0 lg:grid-cols-3">
                    @foreach($type->plans as $plan)
                        @php
                            $price = (float)$plan->price;
                            $canAfford = $walletAvailable + 0.000001 >= $price;
                        @endphp
                        <div class="border-t border-border p-5 first:border-t-0 lg:border-l lg:first:border-l-0 lg:border-t-0">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-semibold">{{ $plan->name }}</h3>
                                    <p class="mt-1 text-[10px] uppercase tracking-[.1em] text-muted-foreground">
                                        {{ ucfirst($plan->billing_interval) }}{{ $plan->duration_days ? ' · '.$plan->duration_days.' days' : '' }}
                                    </p>
                                </div>
                                <p class="text-lg font-semibold">{{ strtoupper($plan->currency) }} {{ number_format($price,2) }}</p>
                            </div>

                            @if($plan->description)
                                <p class="mt-3 text-xs leading-5 text-muted-foreground">{{ $plan->description }}</p>
                            @endif

                            <div class="mt-4 space-y-2">
                                @foreach($plan->entitlements->take(5) as $entitlement)
                                    <div class="flex items-start gap-2 text-[10px] text-muted-foreground">
                                        <i data-lucide="check" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-600"></i>
                                        <span>{{ $entitlement->label }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <form method="POST" action="{{ route('memberships.purchase', [$type, $plan]) }}" class="mt-5">
                                @csrf
                                <input type="hidden" name="idempotency_key" value="{{ (string)\Illuminate\Support\Str::uuid() }}">
                                @if($activeForType)
                                    <button type="button" class="ui-btn ui-btn-secondary w-full justify-center" disabled>Membership type already active</button>
                                @elseif(! $canAfford)
                                    <button type="button" class="ui-btn ui-btn-secondary w-full justify-center" disabled>Insufficient wallet balance</button>
                                @else
                                    <button class="ui-btn ui-btn-primary w-full justify-center">
                                        {{ $price > 0 ? 'Purchase membership' : 'Activate membership' }}
                                    </button>
                                @endif
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="ui-panel p-8 text-center text-sm text-muted-foreground">No public membership plans are currently available.</div>
        @endforelse
    </section>

    @if($recentPurchases->isNotEmpty())
        <section class="ui-panel mt-5 overflow-hidden">
            <div class="border-b border-border px-5 py-4">
                <p class="ui-kicker">Commercial history</p>
                <h2 class="mt-1 text-sm font-semibold">Membership purchase receipts</h2>
            </div>
            <div class="divide-y divide-border">
                @foreach($recentPurchases as $purchase)
                    <div class="grid gap-2 px-5 py-4 sm:grid-cols-[1.2fr_.8fr_.7fr_1fr] sm:items-center">
                        <div><p class="text-xs font-semibold">{{ $purchase->plan?->type?->name }} · {{ $purchase->plan?->name }}</p><p class="mt-1 text-[9px] text-muted-foreground">{{ $purchase->reference }}</p></div>
                        <div class="text-xs font-semibold">{{ strtoupper($purchase->currency) }} {{ number_format((float)$purchase->amount,2) }}</div>
                        <div class="text-[10px] font-semibold uppercase">{{ $purchase->status }}</div>
                        <div class="text-[10px] text-muted-foreground sm:text-right">{{ $purchase->processed_at?->format('M j, Y · H:i') }}</div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
</x-user-layout>
