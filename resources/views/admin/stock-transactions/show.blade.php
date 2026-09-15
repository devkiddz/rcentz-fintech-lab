<x-admin-layout>
<x-slot name="header">Stock Transaction Details</x-slot>

<div class="ui-page max-w-[1600px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker text-[10px]">Trading Management · Transaction</p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="ui-heading !text-2xl">{{ $transaction->stock->symbol }} {{ ucfirst($transaction->type) }}</h1>
                <span class="inline-flex rounded-full px-2 py-0.5 text-[8px] font-semibold uppercase tracking-[.11em]
                    {{ $transaction->type === 'buy' ? 'border border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : 'border border-red-500/20 bg-red-500/10 text-red-600' }}">
                    {{ ucfirst($transaction->type) }}
                </span>
                <span class="inline-flex rounded-full px-2 py-0.5 text-[8px] font-semibold uppercase tracking-[.11em]
                    {{ $transaction->status === 'completed' ? 'border border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : 'border border-amber-500/20 bg-amber-500/10 text-amber-600' }}">
                    {{ ucfirst($transaction->status) }}
                </span>
            </div>
            <p class="ui-lead !mt-2 !text-[13px]">Execution #{{ $transaction->id }} · {{ $transaction->created_at->format('M d, Y · H:i') }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.stocks.transactions.index') }}" class="ui-btn ui-btn-secondary">
                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                Transactions
            </a>
            <a href="{{ route('admin.stocks.show', $transaction->stock) }}" class="ui-btn ui-btn-secondary">
                <i data-lucide="candlestick-chart" class="h-4 w-4"></i>
                Stock
            </a>
            <a href="{{ route('admin.users.show', $transaction->user) }}" class="ui-btn ui-btn-primary">
                <i data-lucide="user-round" class="h-4 w-4"></i>
                Customer
            </a>
        </div>
    </section>

    <section class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="ui-panel p-4">
            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Shares</p>
            <p class="mt-2 text-2xl font-semibold tabular-nums">{{ number_format($transaction->quantity, 2) }}</p>
        </div>
        <div class="ui-panel p-4">
            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Execution price</p>
            <p class="mt-2 text-2xl font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($transaction->price_per_share, 2) }}</p>
        </div>
        <div class="ui-panel p-4">
            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Total amount</p>
            <p class="mt-2 text-2xl font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($transaction->total_amount, 2) }}</p>
        </div>
        <div class="ui-panel p-4">
            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Executed</p>
            <p class="mt-2 text-[13px] font-semibold">{{ $transaction->created_at->format('M d, Y') }}</p>
            <p class="mt-1 text-[9px] text-muted-foreground">{{ $transaction->created_at->format('H:i:s') }}</p>
        </div>
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-[1.35fr_.65fr]">
        <div class="space-y-5">
            <div class="ui-panel overflow-hidden">
                <div class="border-b border-border/70 px-4 py-4">
                    <div class="flex items-center gap-2">
                        <i data-lucide="candlestick-chart" class="h-4 w-4 text-emerald-500"></i>
                        <h2 class="text-[13px] font-semibold">Stock context</h2>
                    </div>
                    <p class="mt-1 text-[10px] text-muted-foreground">Market state at the time this execution is being reviewed.</p>
                </div>

                <div class="p-4">
                    <div class="flex items-start gap-4">
                        @if($transaction->stock->logo_url)
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-border">
                                <img src="{{ $transaction->stock->logo_url }}" alt="{{ $transaction->stock->symbol }} logo" class="h-7 w-7 object-contain" loading="lazy">
                            </div>
                        @else
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-border bg-muted text-[10px] font-semibold">{{ $transaction->stock->symbol }}</div>
                        @endif
                        <div class="min-w-0">
                            <p class="text-[13px] font-semibold">{{ $transaction->stock->name }}</p>
                            <p class="mt-1 text-[9px] text-muted-foreground">{{ $transaction->stock->symbol }} · {{ $transaction->stock->sector ?: 'Unclassified sector' }}</p>
                            <p class="mt-2 text-[10px] leading-5 text-muted-foreground">{{ $transaction->stock->description ?: 'No description available.' }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-px border-t border-border/70 bg-border/70 lg:grid-cols-4">
                    @foreach([
                        ['Current price',currency_symbol().number_format($transaction->stock->current_price,2)],
                        ['Previous close',currency_symbol().number_format($transaction->stock->previous_close,2)],
                        ['Change',($transaction->stock->price_change >= 0 ? '+' : '').currency_symbol().number_format($transaction->stock->price_change,2)],
                        ['Change %',($transaction->stock->price_change_percentage >= 0 ? '+' : '').number_format($transaction->stock->price_change_percentage,2).'%'],
                    ] as [$label,$value])
                        <div class="bg-background p-4">
                            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">{{ $label }}</p>
                            <p class="mt-1.5 text-[11px] font-semibold tabular-nums">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="ui-panel overflow-hidden">
                <div class="border-b border-border/70 px-4 py-4">
                    <div class="flex items-center gap-2">
                        <i data-lucide="fingerprint" class="h-4 w-4 text-sky-500"></i>
                        <h2 class="text-[13px] font-semibold">Execution record</h2>
                    </div>
                </div>

                <div class="grid gap-px bg-border/70 sm:grid-cols-2">
                    @foreach([
                        ['Transaction ID','#'.$transaction->id],
                        ['Type',ucfirst($transaction->type)],
                        ['Status',ucfirst($transaction->status)],
                        ['Created',$transaction->created_at->format('M d, Y · H:i:s')],
                        ['Updated',$transaction->updated_at->format('M d, Y · H:i:s')],
                        ['Stock',$transaction->stock->symbol],
                        ['Execution source',$transaction->execution_source ? str_replace('_',' ',ucwords($transaction->execution_source,'_')) : 'Legacy / unattributed'],
                        ['Strategy',$transaction->strategy?->name ?? '—'],
                        ['Initiated by',$transaction->initiatedBy?->name ?? 'System'],
                    ] as [$label,$value])
                        <div class="bg-background p-4">
                            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">{{ $label }}</p>
                            <p class="mt-1.5 text-[10px] font-semibold">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <aside class="space-y-5">
            <div class="ui-panel overflow-hidden">
                <div class="border-b border-border/70 px-4 py-4">
                    <div class="flex items-center gap-2">
                        <i data-lucide="user-round" class="h-4 w-4 text-violet-500"></i>
                        <h2 class="text-[13px] font-semibold">Customer</h2>
                    </div>
                </div>

                <div class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-border bg-muted text-[10px] font-semibold">
                            {{ strtoupper(substr($transaction->user->name,0,2)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-[11px] font-semibold">{{ $transaction->user->name }}</p>
                            <p class="mt-0.5 truncate text-[9px] text-muted-foreground">{{ $transaction->user->email }}</p>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3 border-t border-border/70 pt-4">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-[9px] text-muted-foreground">Member since</span>
                            <span class="text-[10px] font-semibold">{{ $transaction->user->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-[9px] text-muted-foreground">Email</span>
                            <span class="text-[10px] font-semibold {{ $transaction->user->email_verified_at ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $transaction->user->email_verified_at ? 'Verified' : 'Unverified' }}
                            </span>
                        </div>
                    </div>

                    <a href="{{ route('admin.users.show', $transaction->user) }}" class="ui-btn ui-btn-secondary mt-4 w-full">
                        View customer profile
                    </a>
                </div>
            </div>

            @if($transaction->walletTransaction)
                <div class="ui-panel overflow-hidden">
                    <div class="border-b border-border/70 px-4 py-4">
                        <div class="flex items-center gap-2">
                            <i data-lucide="wallet-cards" class="h-4 w-4 text-amber-500"></i>
                            <h2 class="text-[13px] font-semibold">Wallet linkage</h2>
                        </div>
                    </div>

                    <div class="divide-y divide-border/70">
                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                            <span class="text-[9px] text-muted-foreground">Payment method</span>
                            <span class="text-right text-[10px] font-semibold">{{ $transaction->walletTransaction->paymentMethod->name ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                            <span class="text-[9px] text-muted-foreground">Reference</span>
                            <span class="max-w-[180px] truncate text-right text-[10px] font-semibold">{{ $transaction->walletTransaction->reference_id ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                            <span class="text-[9px] text-muted-foreground">Status</span>
                            <span class="text-[10px] font-semibold">{{ ucfirst($transaction->walletTransaction->status) }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </aside>
    </section>
</div>
</x-admin-layout>
