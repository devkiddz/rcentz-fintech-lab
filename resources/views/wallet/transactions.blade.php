<x-user-layout>
    <x-slot name="header">Transactions</x-slot>

    <div class="ui-page max-w-[1440px]">
        <section class="ui-page-header">
            <div>
                <p class="ui-kicker">Account ledger</p>
                <h1 class="ui-heading">Transaction history</h1>
                <p class="ui-lead">Credits, debits, investments, transfers and wallet activity from one ledger.</p>
            </div>
            <div class="ui-header-actions">
                <a href="{{ route('account.history') }}" class="ui-btn ui-btn-secondary"><i data-lucide="history" class="h-4 w-4"></i>History</a>
                <a href="{{ route('wallet.index') }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="wallet" class="h-4 w-4"></i>
                    Wallet
                </a>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('wallet.index') }}" class="ui-metric-card group cursor-pointer transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-ring/30">
                <div class="min-w-0 flex-1">
                    <p class="ui-label">Available balance</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-foreground">{{ format_currency($availableBalance) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">Spendable wallet cash.</p>
                </div>
            </a>
            <a href="{{ route('dashboard') }}" class="ui-metric-card group cursor-pointer transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-ring/30">
                <div class="min-w-0 flex-1">
                    <p class="ui-label">Total assets</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-foreground">{{ format_currency($totalAssets) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">Cash + current holdings.</p>
                </div>
            </a>
            <a href="{{ route('wallet.transactions', ['direction' => 'credit']) }}" class="ui-metric-card group cursor-pointer transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-ring/30">
                <div class="min-w-0 flex-1">
                    <p class="ui-label">Completed credits</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-emerald-600 dark:text-emerald-400">+{{ format_currency($totalCredits) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">Incoming completed movement.</p>
                </div>
            </a>
            <a href="{{ route('wallet.transactions', ['direction' => 'debit']) }}" class="ui-metric-card group cursor-pointer transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-ring/30">
                <div class="min-w-0 flex-1">
                    <p class="ui-label">Completed debits</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-red-600 dark:text-red-400">-{{ format_currency($totalDebits) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">Outgoing completed movement.</p>
                </div>
            </a>
        </section>

        <section class="ui-filter-panel mt-4">
            <form method="GET" action="{{ route('wallet.transactions') }}" class="ui-filter-form">
                <div class="ui-field">
                    <label for="direction" class="ui-label">Direction</label>
                    <select id="direction" name="direction" class="ui-input">
                        <option value="">All</option>
                        <option value="credit" @selected(request('direction') === 'credit')>Credits</option>
                        <option value="debit" @selected(request('direction') === 'debit')>Debits</option>
                    </select>
                </div>

                <div class="ui-field">
                    <label for="type" class="ui-label">Type</label>
                    <select id="type" name="type" class="ui-input">
                        <option value="">All types</option>
                        @foreach($transactionTypes as $type)
                            <option value="{{ $type }}" @selected(request('type') === $type)>
                                {{ ucfirst(str_replace('_', ' ', $type)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="ui-field">
                    <label for="status" class="ui-label">Status</label>
                    <select id="status" name="status" class="ui-input">
                        <option value="">All statuses</option>
                        @foreach(['pending', 'completed', 'approved', 'rejected', 'failed'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="ui-field">
                    <label for="date_from" class="ui-label">From</label>
                    <input id="date_from" type="date" name="date_from" value="{{ request('date_from') }}" class="ui-input">
                </div>

                <div class="ui-field">
                    <label for="date_to" class="ui-label">To</label>
                    <input id="date_to" type="date" name="date_to" value="{{ request('date_to') }}" class="ui-input">
                </div>

                <div class="ui-filter-actions">
                    <button type="submit" class="ui-btn ui-btn-primary">Filter</button>
                    <a href="{{ route('wallet.transactions') }}" class="ui-btn ui-btn-secondary">Reset</a>
                </div>
            </form>
        </section>

        <section class="ui-panel mt-4 overflow-hidden">
            <div class="flex items-center justify-between gap-4 border-b border-border px-5 py-4 sm:px-6">
                <div>
                    <p class="ui-kicker">Ledger</p>
                    <h2 class="text-lg font-semibold text-foreground">All activity</h2>
                </div>
                <p class="text-xs text-muted-foreground">{{ number_format($transactions->total()) }} result{{ $transactions->total() === 1 ? '' : 's' }}</p>
            </div>

            @if($transactions->isEmpty())
                <div class="ui-empty-state">
                    <div class="ui-empty-icon"><i data-lucide="receipt-text" class="h-5 w-5"></i></div>
                    <h3 class="font-medium text-foreground">No matching transactions</h3>
                    <p class="mt-1 text-sm text-muted-foreground">Change the filters or begin using your wallet.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[850px]">
                        <thead class="border-b border-border bg-muted/30">
                            <tr class="text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                <th class="px-5 py-3 sm:px-6">Transaction</th>
                                <th class="px-4 py-3">Reference</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach($transactions as $transaction)
                                <tr class="transition-colors hover:bg-muted/20">
                                    <td class="px-5 py-4 sm:px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border bg-muted/40">
                                                <i data-lucide="{{ $transaction->is_credit ? 'arrow-down-left' : 'arrow-up-right' }}" class="h-4 w-4 {{ $transaction->is_credit ? 'text-emerald-500' : 'text-red-500' }}"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="max-w-[28rem] truncate text-sm font-medium text-foreground">{{ $transaction->activity_label }}</p>
                                                <p class="mt-1 text-xs text-muted-foreground">{{ $transaction->created_at->format('M d, Y · h:i A') }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-muted-foreground">
                                        {{ $transaction->reference_id ?: '—' }}
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-md border border-border bg-muted/30 px-2 py-1 text-xs font-medium capitalize text-foreground">
                                            {{ str_replace('_', ' ', $transaction->type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-md border border-border px-2 py-1 text-xs font-medium capitalize text-foreground">
                                            {{ $transaction->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <p class="text-sm font-semibold {{ $transaction->is_credit ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $transaction->signed_formatted_amount }}
                                        </p>
                                        <p class="mt-1 text-[11px] text-muted-foreground">{{ $transaction->direction_label }}</p>
                                        @if($transaction->reference_id)<a href="{{ route('account.history', ['reference' => $transaction->reference_id]) }}" class="mt-1 inline-flex text-[11px] font-medium text-foreground underline-offset-4 hover:underline">View history</a>@endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($transactions->hasPages())
                    <div class="border-t border-border px-5 py-4 sm:px-6">
                        {{ $transactions->links() }}
                    </div>
                @endif
            @endif
        </section>
    </div>
</x-user-layout>
