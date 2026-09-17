<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <div class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[.18em] text-muted-foreground">
                    <span>Admin</span><span>•</span><span>Customers</span>
                </div>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-foreground">Customer Directory</h1>
                <p class="mt-1 text-sm text-muted-foreground">Identity, balances, portfolio activity and account access in one clean control surface.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.users.create') }}" class="ui-btn ui-btn-primary">
                    <i data-lucide="user-plus" class="h-4 w-4"></i>
                    Add customer
                </a>
            </div>
        </div>
    </x-slot>

    <div class="ui-page max-w-[1500px]">
        @if(session('success'))
            <div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm text-emerald-700 dark:text-emerald-300">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-600 dark:text-red-300">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-600 dark:text-red-300">{{ $errors->first() }}</div>
        @endif

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <article class="ui-metric-card">
                <div class="ui-metric-icon"><i data-lucide="users" class="h-5 w-5"></i></div>
                <div><p class="ui-label">Total users</p><p class="mt-1 text-2xl font-semibold">{{ $users->total() }}</p></div>
            </article>
            <article class="ui-metric-card">
                <div class="ui-metric-icon"><i data-lucide="shield-check" class="h-5 w-5"></i></div>
                <div><p class="ui-label">Admins</p><p class="mt-1 text-2xl font-semibold">{{ $users->where('is_admin', true)->count() }}</p></div>
            </article>
            <article class="ui-metric-card">
                <div class="ui-metric-icon"><i data-lucide="receipt-text" class="h-5 w-5"></i></div>
                <div><p class="ui-label">Revenue</p><p class="mt-1 text-2xl font-semibold">{{ format_currency($totalRevenue) }}</p></div>
            </article>
            <article class="ui-metric-card">
                <div class="ui-metric-icon"><i data-lucide="wallet" class="h-5 w-5"></i></div>
                <div><p class="ui-label">Wallet balance</p><p class="mt-1 text-2xl font-semibold">{{ format_currency($totalWalletBalance) }}</p></div>
            </article>
            <article class="ui-metric-card">
                <div class="ui-metric-icon"><i data-lucide="chart-no-axes-combined" class="h-5 w-5"></i></div>
                <div><p class="ui-label">Investments</p><p class="mt-1 text-2xl font-semibold">{{ format_currency($totalInvestmentValue) }}</p></div>
            </article>
        </section>

        <section class="mt-4 ui-surface overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-border px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="ui-kicker">Directory</p>
                    <h2 class="mt-1 text-lg font-semibold">Customers & access</h2>
                </div>

                <div class="flex w-full flex-col gap-2 sm:flex-row lg:w-auto">
                    <div class="relative min-w-[280px] flex-1 lg:flex-none">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                        <input id="customer-search" class="ui-input w-full pl-9" placeholder="Search customer or email...">
                    </div>
                    <select id="customer-filter" class="ui-input min-w-[160px]">
                        <option value="all">All accounts</option>
                        <option value="active">Active</option>
                        <option value="restricted">Restricted</option>
                        <option value="unverified">Email unverified</option>
                        <option value="investors">Has investments</option>
                    </select>
                </div>
            </div>

            @if($users->count() > 0)
                <div class="grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-3" id="customer-grid">
                    @foreach($users as $user)
                        @php
                            $status = $user->account_status ?? 'active';
                            $isRestricted = in_array($status, ['blocked','banned','suspended'], true);
                            $investmentValue = (float) $user->investmentHoldings->sum('current_value');
                            $stockValue = (float) $user->stockHoldings->sum('current_value');
                            $walletValue = (float) optional($user->wallet)->balance;
                            $initials = collect(explode(' ', trim($user->name)))->filter()->take(2)->map(fn($part) => strtoupper(substr($part,0,1)))->implode('');
                        @endphp

                        <article
                            class="customer-card group rounded-2xl border border-border bg-card p-4 transition hover:-translate-y-0.5 hover:shadow-md"
                            data-search="{{ strtolower($user->name.' '.$user->email) }}"
                            data-status="{{ $status }}"
                            data-verified="{{ $user->email_verified_at ? 'yes' : 'no' }}"
                            data-investor="{{ $investmentValue > 0 ? 'yes' : 'no' }}"
                        >
                            <div class="flex items-start gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-border bg-muted text-sm font-semibold">
                                    @if($user->profile_image)
                                        <img src="{{ asset('storage/' . $user->profile_image) }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                                    @else
                                        {{ $initials ?: 'U' }}
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h3 class="truncate text-sm font-semibold text-foreground">{{ $user->name }}</h3>
                                                @if($user->is_admin)
                                                    <span class="rounded-full border border-violet-500/20 bg-violet-500/10 px-2 py-0.5 text-[9px] font-semibold text-violet-600">ADMIN</span>
                                                @endif
                                            </div>
                                            <p class="mt-0.5 truncate text-xs text-muted-foreground">{{ $user->email }}</p>
                                        </div>

                                        <span class="shrink-0 rounded-full px-2 py-1 text-[9px] font-semibold uppercase {{ $status === 'active' ? 'bg-emerald-500/10 text-emerald-600' : ($status === 'banned' ? 'bg-red-500/10 text-red-600' : 'bg-amber-500/10 text-amber-600') }}">
                                            {{ $status }}
                                        </span>
                                    </div>

                                    <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-[10px] text-muted-foreground">
                                        <span>{{ $user->email_verified_at ? 'Email verified' : 'Email unverified' }}</span>
                                        <span>•</span>
                                        <span>Joined {{ $user->created_at->format('M j, Y') }}</span>
                                        @if($user->date_of_birth)
                                            <span>•</span><span>{{ $user->age }} yrs</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-3 gap-2">
                                <div class="rounded-xl border border-border bg-muted/20 p-3">
                                    <p class="text-[9px] uppercase tracking-[.08em] text-muted-foreground">Wallet</p>
                                    <p class="mt-1 truncate text-sm font-semibold">{{ format_currency($walletValue) }}</p>
                                </div>
                                <div class="rounded-xl border border-border bg-muted/20 p-3">
                                    <p class="text-[9px] uppercase tracking-[.08em] text-muted-foreground">Investments</p>
                                    <p class="mt-1 truncate text-sm font-semibold">{{ format_currency($investmentValue) }}</p>
                                </div>
                                <div class="rounded-xl border border-border bg-muted/20 p-3">
                                    <p class="text-[9px] uppercase tracking-[.08em] text-muted-foreground">Stocks</p>
                                    <p class="mt-1 truncate text-sm font-semibold">{{ format_currency($stockValue) }}</p>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-1.5 text-[10px] text-muted-foreground">
                                @if($user->employment_class)
                                    <span class="rounded-full border border-border bg-muted/20 px-2 py-1">{{ ucwords(str_replace('_',' ',$user->employment_class)) }}</span>
                                @endif
                                @if($user->education_level)
                                    <span class="rounded-full border border-border bg-muted/20 px-2 py-1">{{ ucwords(str_replace('_',' ',$user->education_level)) }}</span>
                                @endif
                                @if($user->currency)
                                    <span class="rounded-full border border-border bg-muted/20 px-2 py-1">{{ $user->currency }}</span>
                                @endif
                                @if(!$user->employment_class && !$user->education_level)
                                    <span class="rounded-full border border-border bg-muted/20 px-2 py-1">Optional profile not supplied</span>
                                @endif
                            </div>

                            @if(!$user->is_admin)
                                <details class="mt-4 rounded-xl border border-border bg-muted/10">
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-3 py-2.5 text-xs font-medium text-foreground">
                                        <span class="flex items-center gap-2"><i data-lucide="shield" class="h-3.5 w-3.5"></i>Account access</span>
                                        <i data-lucide="chevron-down" class="h-3.5 w-3.5 text-muted-foreground"></i>
                                    </summary>
                                    <form method="POST" action="{{ route('admin.users.access.update',$user) }}" class="grid gap-2 border-t border-border p-3 sm:grid-cols-2">
                                        @csrf @method('PATCH')
                                        <select name="account_status" class="ui-input text-xs">
                                            @foreach(['active'=>'Active','blocked'=>'Block','suspended'=>'Suspend','banned'=>'Ban'] as $value=>$label)
                                                <option value="{{ $value }}" @selected($status===$value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input name="status_until" type="datetime-local" class="ui-input text-xs" value="{{ optional($user->status_until)->format('Y-m-d\TH:i') }}" title="Required for suspension">
                                        <input name="status_reason" class="ui-input text-xs sm:col-span-2" value="{{ $user->status_reason }}" placeholder="Reason required for restricted states">
                                        <button class="ui-btn ui-btn-secondary ui-btn-sm justify-center sm:col-span-2">Save access state</button>
                                    </form>
                                </details>
                            @endif

                            <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-border pt-4">
                                <a href="{{ route('admin.users.show',$user) }}" class="ui-btn ui-btn-secondary ui-btn-sm"><i data-lucide="eye" class="h-3.5 w-3.5"></i>View</a>
                                <a href="{{ route('admin.users.edit',$user) }}" class="ui-btn ui-btn-secondary ui-btn-sm"><i data-lucide="pencil" class="h-3.5 w-3.5"></i>Edit</a>

                                @if(!$user->is_admin)
                                    <a href="{{ route('admin.users.alerts.index',$user) }}" class="ui-btn ui-btn-secondary ui-btn-sm"><i data-lucide="megaphone" class="h-3.5 w-3.5"></i>Alert</a>

                                    @if(!$user->email_verified_at)
                                        <form method="POST" action="{{ route('admin.users.verify-email',$user) }}">@csrf<button class="ui-btn ui-btn-secondary ui-btn-sm"><i data-lucide="badge-check" class="h-3.5 w-3.5"></i>Verify email</button></form>
                                    @endif

                                    <a href="{{ route('impersonate',$user->id) }}" class="ui-btn ui-btn-secondary ui-btn-sm"><i data-lucide="log-in" class="h-3.5 w-3.5"></i>Login as</a>

                                    @if($user->purchases->count() === 0)
                                        <form method="POST" action="{{ route('admin.users.destroy',$user) }}" class="ml-auto">
                                            @csrf @method('DELETE')
                                            <button type="submit" onclick="return confirm('Delete this user permanently?')" class="ui-btn ui-btn-ghost ui-btn-sm text-red-600 hover:bg-red-500/10"><i data-lucide="trash-2" class="h-3.5 w-3.5"></i></button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                <div id="customer-empty-filter" class="hidden px-5 py-12 text-center text-sm text-muted-foreground">No customers match the current search/filter.</div>

                @if($users->hasPages())
                    <div class="border-t border-border px-5 py-4">{{ $users->links() }}</div>
                @endif
            @else
                <div class="px-5 py-16 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-border bg-muted"><i data-lucide="users" class="h-5 w-5"></i></div>
                    <h3 class="mt-4 font-semibold">No customers yet</h3>
                    <p class="mt-1 text-sm text-muted-foreground">Create the first customer account to populate this directory.</p>
                    <a href="{{ route('admin.users.create') }}" class="ui-btn ui-btn-primary mt-4">Add customer</a>
                </div>
            @endif
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const search = document.getElementById('customer-search');
            const filter = document.getElementById('customer-filter');
            const cards = [...document.querySelectorAll('.customer-card')];
            const empty = document.getElementById('customer-empty-filter');

            function applyFilters() {
                const term = (search?.value || '').trim().toLowerCase();
                const mode = filter?.value || 'all';
                let visible = 0;

                cards.forEach(card => {
                    const matchesSearch = !term || card.dataset.search.includes(term);
                    let matchesFilter = true;

                    if (mode === 'active') matchesFilter = card.dataset.status === 'active';
                    if (mode === 'restricted') matchesFilter = ['blocked','banned','suspended'].includes(card.dataset.status);
                    if (mode === 'unverified') matchesFilter = card.dataset.verified === 'no';
                    if (mode === 'investors') matchesFilter = card.dataset.investor === 'yes';

                    const show = matchesSearch && matchesFilter;
                    card.classList.toggle('hidden', !show);
                    if (show) visible++;
                });

                empty?.classList.toggle('hidden', visible !== 0);
            }

            search?.addEventListener('input', applyFilters);
            filter?.addEventListener('change', applyFilters);
        });
    </script>
</x-admin-layout>
