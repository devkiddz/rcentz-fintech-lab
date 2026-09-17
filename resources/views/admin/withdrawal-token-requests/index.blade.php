<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="ui-kicker">Customers & Finance</p>
            <h1 class="text-xl font-semibold">Withdrawal Requests</h1>
            <p class="mt-1 text-xs text-muted-foreground">Review a request, issue a one-time code, then send it manually through the customer's private dashboard notice.</p>
        </div>
    </x-slot>

    <div class="ui-page max-w-7xl">
        @if(session('generated_withdrawal_token'))
            @php $generated = session('generated_withdrawal_token'); @endphp
            <section class="mb-4 overflow-hidden rounded-2xl border border-red-500/20 bg-red-500/[.06]">
                <div class="flex flex-col gap-5 p-5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="ui-kicker">One-time verification code</p>
                        <h2 class="mt-1 text-lg font-semibold">{{ $generated['customer_name'] }}</h2>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ $generated['customer_email'] }} · {{ $generated['amount'] }} · expires {{ $generated['expires_at'] }}
                        </p>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="rounded-xl border border-border bg-background px-5 py-3 text-center font-mono text-2xl font-bold tracking-[.28em]" id="generatedWithdrawalCode">{{ $generated['token'] }}</div>
                        <button type="button" onclick="copyWithdrawalCode()" class="ui-btn ui-btn-primary">
                            <i data-lucide="copy" class="h-4 w-4"></i>Copy code
                        </button>
                        <a href="{{ route('admin.users.alerts.index', ['user' => $generated['user_id'], 'withdrawal_request' => $generated['request_id']]) }}" class="ui-btn ui-btn-secondary">
                            <i data-lucide="message-square-text" class="h-4 w-4"></i>Open dashboard notice
                        </a>
                    </div>
                </div>
                <div class="border-t border-red-500/10 px-5 py-3 text-xs text-muted-foreground">
                    The code is shown in full only on this response. Copy it before leaving this page. If it is lost, issue a new code.
                </div>
            </section>
        @endif

        <section class="ui-panel p-4">
            <form method="GET" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_auto] lg:items-end">
                <div>
                    <label class="ui-label">Customer</label>
                    <input class="ui-input mt-1 w-full" name="q" value="{{ request('q') }}" placeholder="Search name or email">
                </div>
                <div>
                    <label class="ui-label">Status</label>
                    <select class="ui-input mt-1 w-full" name="status">
                        <option value="">All statuses</option>
                        @foreach(['pending','token_issued','used','expired','cancelled'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_',' ',$status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button class="ui-btn ui-btn-primary">Apply</button>
                    @if(request()->filled('q') || request()->filled('status') || request()->filled('user'))
                        <a href="{{ route('admin.withdrawal-token-requests.index') }}" class="ui-btn ui-btn-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="mt-4">
            <div class="mb-3 flex items-end justify-between gap-3">
                <div>
                    <p class="ui-kicker">Security queue</p>
                    <h2 class="mt-1 text-lg font-semibold">{{ $requests->total() }} request{{ $requests->total() === 1 ? '' : 's' }}</h2>
                </div>
            </div>

            <div class="grid gap-3">
                @forelse($requests as $requestItem)
                    @php
                        $transaction = $requestItem->walletTransaction;
                        $isExpired = $requestItem->status === 'token_issued' && $requestItem->token_expires_at && now()->gt($requestItem->token_expires_at);
                        $displayStatus = $isExpired ? 'expired' : $requestItem->status;
                        $statusClass = match($displayStatus) {
                            'pending' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
                            'token_issued' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
                            'used' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
                            'expired','cancelled' => 'bg-muted text-muted-foreground',
                            default => 'bg-muted text-muted-foreground',
                        };
                    @endphp

                    <article class="ui-surface overflow-hidden">
                        <div class="grid gap-5 p-5 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,.9fr)_auto] xl:items-center">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-muted text-xs font-semibold">{{ strtoupper(substr($requestItem->user->name,0,2)) }}</div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold">{{ $requestItem->user->name }}</p>
                                        <p class="truncate text-xs text-muted-foreground">{{ $requestItem->user->email }}</p>
                                    </div>
                                    <span class="rounded-full px-2.5 py-1 text-[9px] font-semibold uppercase tracking-[.08em] {{ $statusClass }}">{{ str_replace('_',' ',$displayStatus) }}</span>
                                </div>
                                @if($requestItem->note)
                                    <p class="mt-3 text-xs leading-5 text-muted-foreground">{{ $requestItem->note }}</p>
                                @endif
                            </div>

                            <div class="grid grid-cols-2 gap-x-5 gap-y-3 sm:grid-cols-4 xl:grid-cols-2">
                                <div><p class="ui-label">Amount</p><p class="mt-1 text-sm font-semibold">{{ format_currency($requestItem->amount,'USD',$requestItem->user->currency,$requestItem->user) }}</p></div>
                                <div><p class="ui-label">Payout</p><p class="mt-1 text-sm font-semibold">{{ $transaction ? ucfirst($transaction->status) : 'Not created' }}</p></div>
                                <div><p class="ui-label">Requested</p><p class="mt-1 text-xs font-medium">{{ $requestItem->created_at->format('M j · H:i') }}</p></div>
                                <div><p class="ui-label">Verification</p><p class="mt-1 text-xs font-medium">{{ $requestItem->token_last_four ? 'Code ••'.$requestItem->token_last_four : 'Not issued' }}</p></div>
                            </div>

                            <div class="flex flex-wrap gap-2 xl:justify-end">
                                @if(in_array($requestItem->status,['pending','token_issued'],true))
                                    <form method="POST" action="{{ route('admin.withdrawal-token-requests.generate',$requestItem) }}">
                                        @csrf
                                        <button class="ui-btn ui-btn-primary ui-btn-sm">
                                            <i data-lucide="key-round" class="h-3.5 w-3.5"></i>{{ $requestItem->status === 'token_issued' ? 'Issue new code' : 'Generate code' }}
                                        </button>
                                    </form>
                                @endif

                                @if($requestItem->status === 'token_issued')
                                    <a href="{{ route('admin.users.alerts.index',['user'=>$requestItem->user_id,'withdrawal_request'=>$requestItem->id]) }}" class="ui-btn ui-btn-secondary ui-btn-sm">
                                        <i data-lucide="message-square-text" class="h-3.5 w-3.5"></i>Dashboard notice
                                    </a>
                                @endif

                                @if(in_array($requestItem->status,['pending','token_issued'],true))
                                    <form method="POST" action="{{ route('admin.withdrawal-token-requests.destroy-token',$requestItem) }}">
                                        @csrf @method('DELETE')
                                        <button class="ui-btn ui-btn-secondary ui-btn-sm" onclick="return confirm('Cancel this withdrawal request and invalidate its code?')">Cancel</button>
                                    </form>
                                @endif

                                @if($transaction)
                                    <a href="{{ route('admin.wallet-transactions.show',$transaction) }}" class="ui-btn ui-btn-secondary ui-btn-sm">View payout</a>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="ui-surface px-6 py-14 text-center">
                        <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-muted"><i data-lucide="shield-check" class="h-5 w-5 text-muted-foreground"></i></div>
                        <p class="mt-3 text-sm font-semibold">No withdrawal requests</p>
                        <p class="mt-1 text-xs text-muted-foreground">New customer withdrawal requests will appear here for security review.</p>
                    </div>
                @endforelse
            </div>

            @if($requests->hasPages())
                <div class="mt-4">{{ $requests->withQueryString()->links() }}</div>
            @endif
        </section>
    </div>

    <script>
        function copyWithdrawalCode() {
            const value = document.getElementById('generatedWithdrawalCode')?.textContent?.trim();
            if (!value) return;
            navigator.clipboard.writeText(value);
        }
    </script>
</x-admin-layout>
