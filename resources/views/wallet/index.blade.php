<x-user-layout>
    <x-slot name="header">Money</x-slot>

    <div class="ui-page max-w-7xl space-y-5 sm:space-y-6">
        <section class="wallet-shell-card">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-muted-foreground">Available balance</p>
                    <div class="mt-1 flex flex-wrap items-end gap-x-3 gap-y-1">
                        <h1 class="text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">{{ format_currency($wallet->available_balance) }}</h1>
                        <span class="pb-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ $wallet->currency }}</span>
                    </div>
                    <div class="mt-3 flex items-center gap-2 text-xs text-muted-foreground">
                        <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                        Money account ready
                    </div>
                    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                        <span>Balance <strong class="font-medium text-foreground">{{ format_currency($wallet->balance) }}</strong></span>
                        <span>Reserved <strong class="font-medium text-foreground">{{ format_currency($wallet->reserved_balance) }}</strong></span>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 sm:flex sm:items-center">
                    <a href="{{ route('money.add') }}" class="wallet-action-button">
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        <span>Add money</span>
                    </a>
                    <a href="{{ route('money.withdraw') }}" class="wallet-action-button">
                        <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
                        <span>Withdraw</span>
                    </a>
                    <a href="{{ route('money.send') }}" class="wallet-action-button">
                        <i data-lucide="arrow-right-left" class="h-4 w-4"></i>
                        <span>Send</span>
                    </a>
                    <a href="{{ route('account.history') }}" class="wallet-action-button">
                        <i data-lucide="history" class="h-4 w-4"></i>
                        <span>History</span>
                    </a>
                </div>
            </div>
        </section>

        <section class="wallet-metrics-grid">
            <div class="wallet-metric-card">
                <div class="wallet-metric-icon"><i data-lucide="arrow-down-to-line" class="h-4 w-4"></i></div>
                <div class="min-w-0">
                    <span>Total deposits</span>
                    <strong>{{ format_currency($totalDeposits) }}</strong>
                    <small>Completed funding</small>
                </div>
            </div>
            <div class="wallet-metric-card">
                <div class="wallet-metric-icon"><i data-lucide="arrow-up-from-line" class="h-4 w-4"></i></div>
                <div class="min-w-0">
                    <span>Total withdrawals</span>
                    <strong>{{ format_currency($totalWithdrawals) }}</strong>
                    <small>Completed settlements</small>
                </div>
            </div>
            <div class="wallet-metric-card">
                <div class="wallet-metric-icon"><i data-lucide="chart-no-axes-combined" class="h-4 w-4"></i></div>
                <div class="min-w-0">
                    <span>Total invested</span>
                    <strong>{{ format_currency($totalInvestments) }}</strong>
                    <small>Capital currently allocated</small>
                </div>
            </div>
        </section>

        <section class="ui-panel overflow-hidden shadow-none">
            <div class="flex items-center justify-between gap-4 px-4 py-4 sm:px-5">
                <div>
                    <h2 class="text-base font-semibold tracking-tight text-foreground">Recent activity</h2>
                    <p class="mt-0.5 text-xs text-muted-foreground">Your latest money activity.</p>
                </div>
                <a href="{{ route('money.activity') }}" class="ui-button-secondary !h-9 !px-3 !py-0 text-xs">
                    View all
                    <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                </a>
            </div>

            <div class="border-t border-border">
                @forelse($recentTransactions as $transaction)
                    @php($incoming = $transaction->is_credit)
                    <div class="wallet-activity-row">
                        <div class="wallet-activity-icon {{ $incoming ? 'wallet-activity-icon-in' : '' }}">
                            <i data-lucide="{{ $incoming ? 'arrow-down-left' : 'arrow-up-right' }}" class="h-4 w-4"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium text-foreground">{{ $transaction->description ?: ucfirst($transaction->type) }}</div>
                            <div class="mt-0.5 truncate text-xs text-muted-foreground">{{ $transaction->paymentMethod->name ?? 'Wallet' }} · {{ $transaction->created_at->format('M j, Y · H:i') }}</div>
                        </div>
                        <div class="shrink-0 text-right">
                            <div class="text-sm font-semibold {{ $incoming ? 'text-emerald-500' : 'text-foreground' }}">{{ $incoming ? '+' : '-' }}{{ format_currency($transaction->amount) }}</div>
                            <span class="wallet-status-badge wallet-status-{{ strtolower($transaction->status) }}">{{ ucfirst($transaction->status) }}</span>
                        </div>
                    </div>
                @empty
                    <div class="m-4 app-empty-state">
                        <i data-lucide="wallet-cards" class="h-6 w-6"></i>
                        <p>No money activity yet.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-user-layout>
