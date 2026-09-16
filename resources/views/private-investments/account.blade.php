<x-user-layout>
<x-slot name="header">My Investments</x-slot>

<div class="mx-auto max-w-[1280px] space-y-5 px-3 py-5 sm:px-5 lg:px-6">
    <section class="overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <div class="grid gap-6 p-6 lg:grid-cols-[1.2fr_.8fr] lg:p-8">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[.18em] text-red-600">Investment account</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950 dark:text-white">Your private investment account.</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-500">
                    Holdings, cost basis, current valuation and performance from the Rcentz Investment Price Authority.
                </p>
                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="{{ route('investments.index') }}" class="rounded-xl bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">Browse Investments</a>
                    <a href="{{ route('account.investments.portfolio') }}" class="rounded-xl border border-zinc-200 px-4 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-900">View Portfolio</a>
                </div>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-[10px] uppercase tracking-[.14em] text-zinc-500">Current value</p>
                <p class="mt-1 text-3xl font-semibold tabular-nums text-zinc-950 dark:text-white">{{ currency_symbol() }}{{ number_format($summary['current_value'],2) }}</p>
                <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                    <div><p class="text-zinc-500">Invested</p><p class="mt-1 font-semibold">{{ currency_symbol() }}{{ number_format($summary['cost_basis'],2) }}</p></div>
                    <div><p class="text-zinc-500">Gain / Loss</p><p class="mt-1 font-semibold {{ $summary['profit_loss'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $summary['profit_loss'] >= 0 ? '+' : '' }}{{ currency_symbol() }}{{ number_format($summary['profit_loss'],2) }}</p></div>
                </div>
            </div>
        </div>
    </section>

    @if($isAdmin)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-800">
            <strong>Admin audit mode.</strong> This route is the customer account surface. Customer-specific data is intentionally not impersonated into the admin identity. Use impersonation to audit one user's exact account or use the Admin control plane for platform-wide records.
        </div>
    @endif

    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([
            ['Holdings',$summary['holdings'],'layers-3'],
            ['Cost basis',currency_symbol().number_format($summary['cost_basis'],2),'landmark'],
            ['Current value',currency_symbol().number_format($summary['current_value'],2),'wallet-cards'],
            ['Return',($summary['return_percent'] >= 0 ? '+' : '').number_format($summary['return_percent'],2).'%','chart-no-axes-combined'],
        ] as [$label,$value,$icon])
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div class="flex items-center justify-between"><p class="text-[10px] uppercase tracking-[.12em] text-zinc-400">{{ $label }}</p><i data-lucide="{{ $icon }}" class="h-4 w-4 text-red-600"></i></div>
                <p class="mt-2 text-xl font-semibold tabular-nums text-zinc-950 dark:text-white">{{ $value }}</p>
            </div>
        @endforeach
    </section>

    <div class="grid gap-5 lg:grid-cols-[1.1fr_.9fr]">
        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
            <div class="flex items-center justify-between">
                <div><p class="text-[10px] font-semibold uppercase tracking-[.14em] text-red-600">Holdings</p><h2 class="mt-1 text-lg font-semibold">Investment positions</h2></div>
                <a href="{{ route('account.investments.portfolio') }}" class="text-xs font-semibold text-red-600">View all</a>
            </div>
            @if($holdings->isEmpty())
                <div class="mt-4 rounded-xl border border-dashed border-zinc-300 p-8 text-center text-xs text-zinc-500 dark:border-zinc-800">No private investment holdings yet.</div>
            @else
                <div class="mt-4 space-y-2">
                    @foreach($holdings->take(5) as $holding)
                        <a href="{{ route('investments.show',$holding->instrument->slug) }}" class="flex items-center justify-between rounded-xl border border-zinc-100 p-3 hover:border-red-200 dark:border-zinc-900">
                            <div><p class="text-xs font-semibold">{{ $holding->instrument->name }}</p><p class="mt-1 text-[10px] text-zinc-500">{{ number_format((float)$holding->units,4) }} units</p></div>
                            <div class="text-right"><p class="text-xs font-semibold">{{ currency_symbol() }}{{ number_format((float)$holding->current_value,2) }}</p><p class="mt-1 text-[10px] {{ (float)$holding->unrealized_profit_loss >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ (float)$holding->unrealized_profit_loss >= 0 ? '+' : '' }}{{ currency_symbol() }}{{ number_format((float)$holding->unrealized_profit_loss,2) }}</p></div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
            <p class="text-[10px] font-semibold uppercase tracking-[.14em] text-red-600">Recent activity</p>
            <h2 class="mt-1 text-lg font-semibold">Investment ledger</h2>
            @if($transactions->isEmpty())
                <div class="mt-4 rounded-xl border border-dashed border-zinc-300 p-8 text-center text-xs text-zinc-500 dark:border-zinc-800">Transactions will appear after subscription/redemption is enabled.</div>
            @else
                <div class="mt-4 space-y-2">
                    @foreach($transactions as $transaction)
                        <div class="flex items-center justify-between rounded-xl border border-zinc-100 p-3 dark:border-zinc-900">
                            <div><p class="text-xs font-semibold">{{ $transaction->instrument->symbol }} · {{ ucwords(str_replace('_',' ',$transaction->type)) }}</p><p class="mt-1 text-[10px] text-zinc-500">{{ optional($transaction->executed_at)->format('M j, Y H:i') }}</p></div>
                            <p class="text-xs font-semibold">{{ currency_symbol() }}{{ number_format((float)$transaction->net_amount,2) }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</div>
</x-user-layout>
