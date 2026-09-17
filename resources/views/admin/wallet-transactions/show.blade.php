<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="ui-kicker">Customers & Finance</p>
            <h1 class="text-xl font-semibold">Transaction Details</h1>
            <p class="mt-1 text-xs text-muted-foreground">Review the financial record, customer, payment method and security trail.</p>
        </div>
    </x-slot>

    @php
        $customer = $transaction->wallet->user;
        $isWithdrawal = $transaction->type === 'withdrawal';
        $statusClass = match ($transaction->status) {
            'completed' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
            'pending' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
            'rejected' => 'bg-red-500/10 text-red-600 dark:text-red-400',
            default => 'bg-muted text-muted-foreground',
        };
    @endphp

    <div class="ui-page max-w-7xl">
        <section class="ui-page-header">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.wallet-transactions.index') }}" class="ui-btn ui-btn-secondary ui-btn-sm">
                    <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>Transactions
                </a>
                @if($isWithdrawal)
                    <a href="{{ route('admin.withdrawal-token-requests.index', ['user' => $customer->id]) }}" class="ui-btn ui-btn-secondary ui-btn-sm">
                        <i data-lucide="shield-check" class="h-3.5 w-3.5"></i>Withdrawal Requests
                    </a>
                @endif
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <article class="ui-metric-card">
                <div><p class="ui-label">Amount</p><p class="mt-1 text-2xl font-semibold">{{ currency_symbol() }}{{ number_format($transaction->amount, 2) }}</p></div>
            </article>
            <article class="ui-metric-card">
                <div><p class="ui-label">Fee</p><p class="mt-1 text-2xl font-semibold">{{ currency_symbol() }}{{ number_format($transaction->fee, 2) }}</p></div>
            </article>
            <article class="ui-metric-card">
                <div><p class="ui-label">Status</p><span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[.08em] {{ $statusClass }}">{{ $transaction->status }}</span></div>
            </article>
            <article class="ui-metric-card">
                <div><p class="ui-label">Reference</p><p class="mt-1 break-all text-sm font-semibold">{{ $transaction->reference_id ?: 'Not assigned' }}</p></div>
            </article>
        </section>

        <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,.65fr)]">
            <main class="space-y-4">
                @if($isWithdrawal)
                    <section class="ui-surface overflow-hidden">
                        <div class="flex flex-col gap-3 border-b border-border px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="ui-kicker">Withdrawal security</p>
                                <h2 class="mt-1 text-base font-semibold">Assessment & verification</h2>
                                <p class="mt-1 text-xs text-muted-foreground">Security request and verification evidence attached to this payout.</p>
                            </div>
                            @if($withdrawalRequest)
                                <span class="w-fit rounded-full bg-emerald-500/10 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[.08em] text-emerald-600 dark:text-emerald-400">
                                    Gate verified
                                </span>
                            @else
                                <span class="w-fit rounded-full bg-amber-500/10 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[.08em] text-amber-600 dark:text-amber-400">
                                    Legacy / unlinked
                                </span>
                            @endif
                        </div>

                        @if($withdrawalRequest)
                            <div class="grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3">
                                <div><p class="ui-label">Request ID</p><p class="mt-1 text-sm font-semibold">#{{ $withdrawalRequest->id }}</p></div>
                                <div><p class="ui-label">Request status</p><p class="mt-1 text-sm font-semibold capitalize">{{ str_replace('_',' ',$withdrawalRequest->status) }}</p></div>
                                <div><p class="ui-label">Requested amount</p><p class="mt-1 text-sm font-semibold">{{ format_currency($withdrawalRequest->amount,'USD',$customer->currency,$customer) }}</p></div>
                                <div><p class="ui-label">Requested</p><p class="mt-1 text-sm font-semibold">{{ $withdrawalRequest->created_at->format('M j, Y · h:i A') }}</p></div>
                                <div><p class="ui-label">Code issued</p><p class="mt-1 text-sm font-semibold">{{ $withdrawalRequest->token_generated_at?->format('M j, Y · h:i A') ?? 'Not recorded' }}</p></div>
                                <div><p class="ui-label">Verified</p><p class="mt-1 text-sm font-semibold">{{ $withdrawalRequest->token_verified_at?->format('M j, Y · h:i A') ?? 'Not recorded' }}</p></div>
                                <div><p class="ui-label">Issued by</p><p class="mt-1 text-sm font-semibold">{{ $withdrawalRequest->generator?->name ?? 'Not recorded' }}</p></div>
                                <div><p class="ui-label">Verification code</p><p class="mt-1 text-sm font-semibold">{{ $withdrawalRequest->token_last_four ? '••••'.$withdrawalRequest->token_last_four : 'Consumed / unavailable' }}</p></div>
                                <div><p class="ui-label">Payout link</p><p class="mt-1 text-sm font-semibold">Transaction #{{ $transaction->id }}</p></div>
                                @if($withdrawalRequest->note)
                                    <div class="sm:col-span-2 lg:col-span-3"><p class="ui-label">Customer note</p><p class="mt-1 text-sm text-muted-foreground">{{ $withdrawalRequest->note }}</p></div>
                                @endif
                            </div>
                        @else
                            <div class="p-5">
                                <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-4">
                                    <p class="text-sm font-semibold text-amber-700 dark:text-amber-300">No security request is linked to this transaction.</p>
                                    <p class="mt-1 text-xs text-muted-foreground">This may be a legacy withdrawal created before the verification-gate workflow. Do not treat it as proof of a completed token assessment.</p>
                                </div>
                            </div>
                        @endif
                    </section>
                @endif

                <section class="ui-surface overflow-hidden">
                    <div class="border-b border-border px-5 py-4">
                        <p class="ui-kicker">Financial record</p>
                        <h2 class="mt-1 text-base font-semibold">Transaction information</h2>
                    </div>
                    <div class="grid gap-4 p-5 sm:grid-cols-2">
                        <div><p class="ui-label">Transaction ID</p><p class="mt-1 text-sm font-semibold">#{{ $transaction->id }}</p></div>
                        <div><p class="ui-label">Type</p><p class="mt-1 text-sm font-semibold capitalize">{{ $transaction->type }}</p></div>
                        <div><p class="ui-label">Direction</p><p class="mt-1 text-sm font-semibold capitalize">{{ $transaction->direction ?? '—' }}</p></div>
                        <div><p class="ui-label">Payment method</p><p class="mt-1 text-sm font-semibold">{{ $transaction->paymentMethod?->name ?? 'Internal / unspecified' }}</p></div>
                        <div><p class="ui-label">Created</p><p class="mt-1 text-sm font-semibold">{{ $transaction->created_at->format('M j, Y · h:i A') }}</p></div>
                        <div><p class="ui-label">Updated</p><p class="mt-1 text-sm font-semibold">{{ $transaction->updated_at->format('M j, Y · h:i A') }}</p></div>
                        @if($transaction->description)
                            <div class="sm:col-span-2"><p class="ui-label">Description</p><p class="mt-1 text-sm text-muted-foreground">{{ $transaction->description }}</p></div>
                        @endif
                    </div>
                </section>

                @if($isWithdrawal)
                    <section class="ui-surface overflow-hidden">
                        <div class="border-b border-border px-5 py-4">
                            <p class="ui-kicker">Payout destination</p>
                            <h2 class="mt-1 text-base font-semibold">Withdrawal details</h2>
                        </div>
                        <div class="grid gap-4 p-5 sm:grid-cols-2">
                            @php $details = $transaction->user_crypto_details ?? []; @endphp
                            <div class="sm:col-span-2">
                                <p class="ui-label">Destination</p>
                                <p class="mt-1 break-all rounded-xl bg-muted/30 p-3 font-mono text-sm">
                                    {{ $details['wallet_address'] ?? $details['destination'] ?? 'No destination stored' }}
                                </p>
                            </div>
                            @if(isset($details['crypto_symbol']))
                                <div><p class="ui-label">Asset</p><p class="mt-1 text-sm font-semibold">{{ strtoupper($details['crypto_symbol']) }}</p></div>
                            @endif
                            @if(isset($details['payment_method']))
                                <div><p class="ui-label">Method</p><p class="mt-1 text-sm font-semibold">{{ $details['payment_method'] }}</p></div>
                            @endif
                            @if($transaction->withdrawal_purpose)
                                <div class="sm:col-span-2"><p class="ui-label">Purpose / note</p><p class="mt-1 text-sm text-muted-foreground">{{ $transaction->withdrawal_purpose }}</p></div>
                            @endif
                        </div>
                    </section>
                @endif

                @if($transaction->status === 'pending')
                    <section class="ui-panel p-5">
                        <p class="ui-kicker">Decision</p>
                        <h2 class="mt-1 text-base font-semibold">{{ $isWithdrawal ? 'Review payout' : 'Review transaction' }}</h2>
                        @if($isWithdrawal && !$withdrawalRequest)
                            <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">This withdrawal has no linked verification assessment. Review carefully before approval.</p>
                        @endif
                        <div class="mt-4 flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('admin.wallet-transactions.approve',$transaction) }}">
                                @csrf
                                <button class="ui-btn ui-btn-primary"><i data-lucide="check" class="h-4 w-4"></i>Approve</button>
                            </form>
                            <button type="button" onclick="openRejectModal()" class="ui-btn border border-red-500/20 bg-red-500/10 text-red-600 dark:text-red-400">
                                <i data-lucide="x" class="h-4 w-4"></i>Reject
                            </button>
                        </div>
                    </section>
                @endif
            </main>

            <aside class="space-y-4">
                <section class="ui-panel p-5">
                    <p class="ui-kicker">Customer</p>
                    <div class="mt-3 flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-muted text-xs font-semibold">{{ strtoupper(substr($customer->name,0,2)) }}</div>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">{{ $customer->name }}</p>
                            <p class="truncate text-xs text-muted-foreground">{{ $customer->email }}</p>
                        </div>
                    </div>
                    <div class="mt-4 grid gap-3 text-sm">
                        <div><p class="ui-label">Wallet balance</p><p class="mt-1 font-semibold">{{ currency_symbol() }}{{ number_format($transaction->wallet->balance,2) }}</p></div>
                        <div><p class="ui-label">Reserved</p><p class="mt-1 font-semibold">{{ currency_symbol() }}{{ number_format($transaction->wallet->reserved_balance,2) }}</p></div>
                    </div>
                    <a href="{{ route('admin.users.show',$customer) }}" class="ui-btn ui-btn-secondary mt-4 w-full justify-center">View customer</a>
                </section>

                <section class="ui-panel p-5">
                    <p class="ui-kicker">Payment method</p>
                    <p class="mt-2 text-sm font-semibold">{{ $transaction->paymentMethod?->name ?? 'Internal / unspecified' }}</p>
                    @if($transaction->paymentMethod)
                        <p class="mt-1 text-xs text-muted-foreground">{{ $transaction->paymentMethod->type }}</p>
                    @endif
                </section>

                @if($isWithdrawal)
                    <section class="ui-panel p-5">
                        <p class="ui-kicker">Withdrawal controls</p>
                        <p class="mt-2 text-sm font-semibold">Protected financial record</p>
                        <p class="mt-1 text-xs leading-5 text-muted-foreground">Withdrawal records cannot be deleted. Pending payouts must be approved or rejected so reserved funds remain consistent and the audit trail is preserved.</p>
                    </section>
                @elseif($transaction->status !== 'completed')
                    <section class="ui-panel p-5">
                        <p class="ui-kicker">Record management</p>
                        <form method="POST" action="{{ route('admin.wallet-transactions.destroy',$transaction) }}" class="mt-3">
                            @csrf
                            @method('DELETE')
                            <button class="ui-btn w-full justify-center border border-red-500/20 bg-red-500/10 text-red-600 dark:text-red-400" onclick="return confirm('Delete this transaction?')">Delete transaction</button>
                        </form>
                    </section>
                @endif
            </aside>
        </div>
    </div>

    <div id="rejectModal" class="fixed inset-0 z-[100] hidden bg-black/60 p-4 backdrop-blur-sm">
        <div class="mx-auto mt-24 w-full max-w-md rounded-2xl border border-border bg-card p-5 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div><p class="ui-kicker">Payout decision</p><h3 class="mt-1 text-lg font-semibold">Reject {{ $isWithdrawal ? 'withdrawal' : 'transaction' }}</h3></div>
                <button type="button" onclick="closeRejectModal()" class="rounded-lg p-2 hover:bg-muted"><i data-lucide="x" class="h-4 w-4"></i></button>
            </div>
            <form method="POST" action="{{ route('admin.wallet-transactions.reject',$transaction) }}" class="mt-5 space-y-4">
                @csrf
                <textarea id="rejection_reason" name="rejection_reason" rows="4" class="ui-input w-full" placeholder="Reason for rejection..." required></textarea>
                <div class="flex gap-2">
                    <button type="button" onclick="closeRejectModal()" class="ui-btn ui-btn-secondary flex-1 justify-center">Cancel</button>
                    <button class="ui-btn flex-1 justify-center border border-red-500/20 bg-red-500/10 text-red-600 dark:text-red-400">Reject</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRejectModal() {
            document.getElementById('rejectModal').classList.remove('hidden');
        }
        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
            document.getElementById('rejection_reason').value = '';
        }
    </script>
</x-admin-layout>
