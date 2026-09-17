<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="ui-kicker">Customers & Finance</p>
            <h1 class="text-xl font-semibold">Transactions</h1>
            <p class="mt-1 text-xs text-muted-foreground">Review deposits, withdrawals and customer wallet activity from one workspace.</p>
        </div>
    </x-slot>

    @php
        $activeType = request('type');
        $activeStatus = request('status');
        $statusClass = fn($status) => match($status) {
            'completed' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
            'pending' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
            'rejected' => 'bg-red-500/10 text-red-600 dark:text-red-400',
            default => 'bg-muted text-muted-foreground',
        };
    @endphp

    <div class="ui-page max-w-7xl">
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['Transactions',$stats['total_transactions'],'receipt-text','All wallet activity'],
                ['Deposits',$stats['total_deposits'],'arrow-down-to-line',$stats['pending_deposits'].' pending'],
                ['Withdrawals',$stats['total_withdrawals'],'arrow-up-from-line',$stats['pending_withdrawals'].' pending approval'],
                ['Completed Volume',currency_symbol().number_format($stats['completed_volume'],0),'circle-dollar-sign','of '.currency_symbol().number_format($stats['total_volume'],0).' total'],
            ] as [$label,$value,$icon,$hint])
                <article class="ui-metric-card">
                    <div class="ui-metric-icon"><i data-lucide="{{ $icon }}" class="h-5 w-5"></i></div>
                    <div><p class="ui-label">{{ $label }}</p><p class="mt-1 text-2xl font-semibold">{{ $value }}</p><p class="mt-1 text-xs text-muted-foreground">{{ $hint }}</p></div>
                </article>
            @endforeach
        </section>

        <section class="ui-panel mt-4 p-4">
            <div class="flex flex-wrap gap-2">
                @foreach([
                    [route('admin.wallet-transactions.index'),'All',!$activeType&&!$activeStatus],
                    [route('admin.wallet-transactions.index',['type'=>'deposit']),'Deposits',$activeType==='deposit'],
                    [route('admin.wallet-transactions.index',['type'=>'withdrawal']),'Withdrawals',$activeType==='withdrawal'],
                    [route('admin.wallet-transactions.index',['status'=>'pending']),'Pending',$activeStatus==='pending'],
                    [route('admin.wallet-transactions.index',['status'=>'completed']),'Completed',$activeStatus==='completed'],
                    [route('admin.wallet-transactions.index',['status'=>'rejected']),'Rejected',$activeStatus==='rejected'],
                ] as [$href,$label,$active])
                    <a href="{{ $href }}" class="ui-btn ui-btn-sm {{ $active ? 'ui-btn-primary' : 'ui-btn-secondary' }}">{{ $label }}</a>
                @endforeach
            </div>
        </section>

        <section class="ui-surface mt-4 overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-border px-5 py-4">
                <div><p class="ui-kicker">Ledger</p><h2 class="mt-1 text-base font-semibold">Customer transactions</h2></div>
                <span class="rounded-full border border-border bg-muted/20 px-3 py-1.5 text-xs text-muted-foreground">{{ $transactions->total() }} results</span>
            </div>

            @forelse($transactions as $transaction)
                @php $customer=$transaction->wallet->user; @endphp
                <article class="border-b border-border px-5 py-4 last:border-b-0 hover:bg-muted/20">
                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,.8fr)_auto] lg:items-center">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-muted text-xs font-semibold">{{ strtoupper(substr($customer->name,0,2)) }}</div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate text-sm font-semibold">{{ $customer->name }}</p>
                                    <span class="rounded-full bg-muted px-2 py-1 text-[9px] font-semibold uppercase">{{ $transaction->type }}</span>
                                    <span class="rounded-full px-2 py-1 text-[9px] font-semibold uppercase {{ $statusClass($transaction->status) }}">{{ $transaction->status }}</span>
                                </div>
                                <p class="mt-1 truncate text-xs text-muted-foreground">{{ $transaction->paymentMethod->name ?? 'Internal / unspecified' }} @if($transaction->reference_id) · {{ $transaction->reference_id }} @endif</p>
                                <p class="mt-1 text-[10px] text-muted-foreground">{{ $transaction->created_at->format('M j, Y · h:i A') }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 lg:text-right">
                            <div><p class="ui-label">Amount</p><p class="mt-1 text-sm font-semibold">{{ currency_symbol() }}{{ number_format($transaction->amount,2) }}</p></div>
                            <div><p class="ui-label">Balance</p><p class="mt-1 text-sm font-semibold">{{ currency_symbol() }}{{ number_format($transaction->wallet->balance,2) }}</p></div>
                        </div>

                        <div class="flex flex-wrap gap-2 lg:justify-end">
                            <a href="{{ route('admin.wallet-transactions.show',$transaction) }}" class="ui-btn ui-btn-secondary ui-btn-sm">View</a>
                            @if($transaction->status === 'pending')
                                <form method="POST" action="{{ route('admin.wallet-transactions.approve',$transaction) }}">@csrf<button class="ui-btn ui-btn-primary ui-btn-sm">Approve</button></form>
                                <button type="button" onclick="openRejectModal('{{ $transaction->id }}')" class="ui-btn ui-btn-sm border border-red-500/20 bg-red-500/10 text-red-600 dark:text-red-400">Reject</button>
                            @endif
                            @if($transaction->status !== 'completed' && $transaction->type !== 'withdrawal')
                                <form method="POST" action="{{ route('admin.wallet-transactions.destroy',$transaction) }}">@csrf @method('DELETE')<button class="ui-btn ui-btn-secondary ui-btn-sm" onclick="return confirm('Delete this transaction?')">Delete</button></form>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-sm text-muted-foreground">No transactions found.</div>
            @endforelse

            @if($transactions->hasPages())<div class="border-t border-border px-5 py-4">{{ $transactions->withQueryString()->links() }}</div>@endif
        </section>
    </div>

    <div id="rejectModal" class="fixed inset-0 z-[100] hidden bg-black/60 p-4 backdrop-blur-sm">
        <div class="mx-auto mt-24 w-full max-w-md rounded-2xl border border-border bg-card p-5 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div><p class="ui-kicker">Transaction review</p><h3 class="mt-1 text-lg font-semibold">Reject transaction</h3></div>
                <button type="button" onclick="closeRejectModal()" class="rounded-lg p-2 hover:bg-muted"><i data-lucide="x" class="h-4 w-4"></i></button>
            </div>
            <form id="rejectForm" method="POST" class="mt-5 space-y-4">
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
        function openRejectModal(transactionId) {
            document.getElementById('rejectForm').action = `/admin/wallet-transactions/${transactionId}/reject`;
            document.getElementById('rejectModal').classList.remove('hidden');
        }
        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
            document.getElementById('rejection_reason').value = '';
        }
    </script>
</x-admin-layout>
