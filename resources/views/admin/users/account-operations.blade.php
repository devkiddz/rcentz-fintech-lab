<x-admin-layout>
<x-slot name="header">Account Operations</x-slot>

<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker text-[10px]">Admin · Customer Account</p>
            <h1 class="ui-heading !text-2xl">{{ $user->name }}</h1>
            <p class="ui-lead !text-[13px]">{{ $user->email }} · controlled adjustments and historical account records.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.users.show',$user) }}" class="ui-btn ui-btn-secondary">
                <i data-lucide="user-round" class="h-4 w-4"></i> User
            </a>
            <a href="{{ route('admin.stocks.index') }}" class="ui-btn ui-btn-secondary">
                <i data-lucide="candlestick-chart" class="h-4 w-4"></i> Trading
            </a>
        </div>
    </section>

    @if($errors->any())
        <section class="mt-4 rounded-xl border border-red-500/20 bg-red-500/5 p-4">
            @foreach($errors->all() as $error)<p class="text-[10px] text-red-600">{{ $error }}</p>@endforeach
        </section>
    @endif

    <section class="mt-5 grid gap-3 sm:grid-cols-3">
        <div class="ui-panel p-4">
            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Wallet balance</p>
            <p class="mt-2 text-xl font-semibold">{{ $user->wallet?->formatted_balance ?? '$0.00' }}</p>
        </div>
        <div class="ui-panel p-4">
            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Available balance</p>
            <p class="mt-2 text-xl font-semibold">{{ $user->wallet?->formatted_available_balance ?? '$0.00' }}</p>
        </div>
        <div class="ui-panel p-4">
            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Stock positions</p>
            <p class="mt-2 text-xl font-semibold">{{ number_format($user->stockHoldings->count()) }}</p>
        </div>
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-[.9fr_1.1fr]">
        <div class="space-y-4">
            <section class="ui-panel overflow-hidden">
                <div class="border-b border-border/70 px-4 py-4">
                    <div class="flex items-center gap-2">
                        <i data-lucide="wallet-cards" class="h-4 w-4 text-emerald-500"></i>
                        <h2 class="text-[13px] font-semibold">New account operation</h2>
                    </div>
                    <p class="mt-1 text-[10px] text-muted-foreground">Every action records the administrator, reason, reference and actual recording time.</p>
                </div>

                <form method="POST" action="{{ route('admin.users.account-operations.store',$user) }}" class="space-y-4 p-4">
                    @csrf

                    <div>
                        <label class="ui-label">Operation</label>
                        <select name="operation_type" class="ui-input w-full" required>
                            <option value="wallet_credit">Wallet credit</option>
                            <option value="wallet_debit">Wallet debit</option>
                            <option value="profit_credit">Profit credit</option>
                            <option value="history_event">Historical event only — no balance impact</option>
                        </select>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="ui-label">Amount</label>
                            <input name="amount" type="number" step="0.01" min="0.01" class="ui-input w-full" placeholder="Optional for history-only event">
                        </div>
                        <div>
                            <label class="ui-label">Effective date</label>
                            <input name="effective_at" type="datetime-local" class="ui-input w-full">
                        </div>
                    </div>

                    <div>
                        <label class="ui-label">History direction</label>
                        <select name="history_direction" class="ui-input w-full">
                            <option value="">Not applicable</option>
                            <option value="credit">Credit</option>
                            <option value="debit">Debit</option>
                        </select>
                        <p class="mt-1 text-[9px] text-muted-foreground">Used as context for a history-only event. It does not move money.</p>
                    </div>

                    <div>
                        <label class="ui-label">Label</label>
                        <input name="label" class="ui-input w-full" placeholder="e.g. Legacy profit settlement" required>
                    </div>

                    <div>
                        <label class="ui-label">Administrative reason</label>
                        <textarea name="reason" class="ui-input min-h-24 w-full" placeholder="Why this operation is being recorded" required></textarea>
                    </div>

                    <div class="rounded-xl border border-border bg-muted/10 p-4">
                        <p class="text-[10px] font-semibold">History integrity</p>
                        <p class="mt-1 text-[9px] leading-4 text-muted-foreground">
                            Effective date describes when an imported event belongs historically. The system still keeps the real admin recording timestamp separately.
                        </p>
                    </div>

                    <button class="ui-btn ui-btn-primary w-full">Record operation</button>
                </form>
            </section>
        </div>

        <section class="ui-panel overflow-hidden">
            <div class="border-b border-border/70 px-4 py-4">
                <div class="flex items-center gap-2">
                    <i data-lucide="history" class="h-4 w-4 text-sky-500"></i>
                    <h2 class="text-[13px] font-semibold">Administrative audit trail</h2>
                </div>
                <p class="mt-1 text-[10px] text-muted-foreground">Newest administrative account operations first.</p>
            </div>

            <div class="divide-y divide-border/70">
                @forelse($operations as $operation)
                    <article class="p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[11px] font-semibold">{{ $operation->label }}</p>
                                <p class="mt-1 text-[9px] text-muted-foreground">
                                    {{ str_replace('_',' ',ucfirst($operation->operation_type)) }} · {{ $operation->reference }}
                                </p>
                            </div>
                            @if($operation->amount !== null)
                                <p class="text-[11px] font-semibold tabular-nums {{ $operation->direction==='credit'?'text-emerald-600':($operation->direction==='debit'?'text-red-600':'') }}">
                                    {{ $operation->direction==='credit'?'+':($operation->direction==='debit'?'-':'') }}{{ format_currency($operation->amount) }}
                                </p>
                            @endif
                        </div>

                        <p class="mt-3 text-[10px] leading-5 text-muted-foreground">{{ $operation->reason }}</p>

                        <div class="mt-3 flex flex-wrap gap-2 text-[9px] text-muted-foreground">
                            <span>Recorded {{ $operation->created_at->format('M d, Y · H:i') }}</span>
                            <span>· by {{ $operation->admin?->name ?? 'Admin' }}</span>
                            @if($operation->effective_at)
                                <span class="rounded-md border border-border px-2 py-0.5">Effective {{ $operation->effective_at->format('M d, Y · H:i') }}</span>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="p-10 text-center text-[10px] text-muted-foreground">No administrative operations recorded for this account.</div>
                @endforelse
            </div>

            @if($operations->hasPages())
                <div class="border-t border-border px-4 py-3">{{ $operations->links() }}</div>
            @endif
        </section>
    </section>
</div>
</x-admin-layout>
