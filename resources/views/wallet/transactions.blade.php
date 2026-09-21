<x-user-layout>
    <x-slot name="header">{{ localize('ui.money.activity', 'Activity') }}</x-slot>

    <div class="ui-page max-w-[1440px]">
        <section class="ui-page-header">
            <div>
                <p class="ui-kicker">{{ localize('ui.nav.money', 'Money') }}</p>
                <h1 class="ui-heading">{{ localize('ui.money.activity_title', 'Money activity') }}</h1>
                <p class="ui-lead">{{ localize('ui.money.activity_lead', 'Track deposits, withdrawals, transfers and other account movement in one place.') }}</p>
            </div>
            <div class="ui-header-actions">
                <a href="{{ route('account.history') }}" class="ui-btn ui-btn-secondary"><i data-lucide="history" class="h-4 w-4"></i>{{ localize('ui.nav.history', 'History') }}</a>
                <a href="{{ route('money.index') }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="wallet" class="h-4 w-4"></i>
                    {{ localize('ui.money.wallet', 'Wallet') }}
                </a>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('money.index') }}" class="ui-metric-card group cursor-pointer transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-ring/30">
                <div class="min-w-0 flex-1">
                    <p class="ui-label">{{ localize('ui.money.available_balance', 'Available balance') }}</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-foreground">{{ format_currency($availableBalance) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ localize('ui.money.spendable_cash', 'Spendable wallet cash.') }}</p>
                </div>
            </a>
            <a href="{{ route('dashboard') }}" class="ui-metric-card group cursor-pointer transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-ring/30">
                <div class="min-w-0 flex-1">
                    <p class="ui-label">{{ localize('ui.money.total_assets', 'Total assets') }}</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-foreground">{{ format_currency($totalAssets) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ localize('ui.money.cash_holdings', 'Cash + current holdings.') }}</p>
                </div>
            </a>
            <a href="{{ route('money.activity', ['direction' => 'credit']) }}" class="ui-metric-card group cursor-pointer transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-ring/30">
                <div class="min-w-0 flex-1">
                    <p class="ui-label">{{ localize('ui.money.completed_credits', 'Completed credits') }}</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-emerald-600 dark:text-emerald-400">+{{ format_currency($totalCredits) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ localize('ui.money.incoming_completed', 'Incoming completed movement.') }}</p>
                </div>
            </a>
            <a href="{{ route('money.activity', ['direction' => 'debit']) }}" class="ui-metric-card group cursor-pointer transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-ring/30">
                <div class="min-w-0 flex-1">
                    <p class="ui-label">{{ localize('ui.money.completed_debits', 'Completed debits') }}</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-red-600 dark:text-red-400">-{{ format_currency($totalDebits) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ localize('ui.money.outgoing_completed', 'Outgoing completed movement.') }}</p>
                </div>
            </a>
        </section>

        <section class="ui-filter-panel mt-4">
            <form method="GET" action="{{ route('money.activity') }}" class="ui-filter-form">
                <div class="ui-field">
                    <label for="direction" class="ui-label">{{ localize('ui.common.direction', 'Direction') }}</label>
                    <select id="direction" name="direction" class="ui-input">
                        <option value="">{{ localize('ui.common.all', 'All') }}</option>
                        <option value="credit" @selected(request('direction') === 'credit')>{{ localize('ui.money.credits', 'Credits') }}</option>
                        <option value="debit" @selected(request('direction') === 'debit')>{{ localize('ui.money.debits', 'Debits') }}</option>
                    </select>
                </div>

                <div class="ui-field">
                    <label for="type" class="ui-label">{{ localize('ui.common.type', 'Type') }}</label>
                    <select id="type" name="type" class="ui-input">
                        <option value="">{{ localize('ui.common.all_types', 'All types') }}</option>
                        @foreach($transactionTypes as $type)
                            <option value="{{ $type }}" @selected(request('type') === $type)>
                                {{ ucfirst(str_replace('_', ' ', $type)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="ui-field">
                    <label for="status" class="ui-label">{{ localize('ui.common.status', 'Status') }}</label>
                    <select id="status" name="status" class="ui-input">
                        <option value="">{{ localize('ui.common.all_statuses', 'All statuses') }}</option>
                        @foreach(['pending', 'completed', 'approved', 'rejected', 'failed'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="ui-field">
                    <label for="date_from" class="ui-label">{{ localize('ui.common.from', 'From') }}</label>
                    <input id="date_from" type="date" name="date_from" value="{{ request('date_from') }}" class="ui-input">
                </div>

                <div class="ui-field">
                    <label for="date_to" class="ui-label">{{ localize('ui.common.to', 'To') }}</label>
                    <input id="date_to" type="date" name="date_to" value="{{ request('date_to') }}" class="ui-input">
                </div>

                <div class="ui-filter-actions">
                    <button type="submit" class="ui-btn ui-btn-primary">{{ localize('ui.common.filter', 'Filter') }}</button>
                    <a href="{{ route('money.activity') }}" class="ui-btn ui-btn-secondary">{{ localize('ui.common.reset', 'Reset') }}</a>
                </div>
            </form>
        </section>

        <section class="ui-panel mt-4 overflow-hidden">
            <div class="flex items-center justify-between gap-4 border-b border-border px-5 py-4 sm:px-6">
                <div>
                    <p class="ui-kicker">{{ localize('ui.money.ledger', 'Ledger') }}</p>
                    <h2 class="text-lg font-semibold text-foreground">{{ localize('ui.money.all_activity', 'All activity') }}</h2>
                </div>
                <p class="text-xs text-muted-foreground">{{ number_format($transactions->total()) }} result{{ $transactions->total() === 1 ? '' : 's' }}</p>
            </div>

            @if($transactions->isEmpty())
                <div class="ui-empty-state">
                    <div class="ui-empty-icon"><i data-lucide="receipt-text" class="h-5 w-5"></i></div>
                    <h3 class="font-medium text-foreground">{{ localize('ui.money.no_matching_transactions', 'No matching transactions') }}</h3>
                    <p class="mt-1 text-sm text-muted-foreground">{{ localize('ui.money.no_matching_help', 'Change the filters or begin using your Money account.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[850px]">
                        <thead class="border-b border-border bg-muted/30">
                            <tr class="text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                <th class="px-5 py-3 sm:px-6">{{ localize('ui.money.transaction', 'Transaction') }}</th>
                                <th class="px-4 py-3">{{ localize('ui.money.reference', 'Reference') }}</th>
                                <th class="px-4 py-3">{{ localize('ui.common.type', 'Type') }}</th>
                                <th class="px-4 py-3">{{ localize('ui.common.status', 'Status') }}</th>
                                <th class="px-4 py-3 text-right">{{ localize('ui.money.amount', 'Amount') }}</th>
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
                                        @if($transaction->reference_id)<a href="{{ route('account.history', ['reference' => $transaction->reference_id]) }}" class="mt-1 inline-flex text-[11px] font-medium text-foreground underline-offset-4 hover:underline">{{ localize('ui.money.view_history', 'View history') }}</a>@endif
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
