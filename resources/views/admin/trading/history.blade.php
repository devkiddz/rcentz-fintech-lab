<x-admin-layout>
<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Trading Command · History</p>
            <h1 class="ui-heading">Trade history</h1>
            <p class="ui-lead">Chronological execution ledger across manual, strategy, user-directed, copy and bot stock trades.</p>
        </div>
        <a href="{{ route('admin.trading.index') }}" class="ui-btn ui-btn-secondary">Trading Overview</a>
    </section>

    <div class="ui-panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-border bg-muted/30 text-[9px] uppercase tracking-[.12em] text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3">Trade</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Asset</th>
                        <th class="px-4 py-3">Side</th>
                        <th class="px-4 py-3">Qty</th>
                        <th class="px-4 py-3">Price</th>
                        <th class="px-4 py-3">Source</th>
                        <th class="px-4 py-3">Executed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-muted/20">
                            <td class="px-4 py-3 font-medium">#{{ $tx->id }}</td>
                            <td class="px-4 py-3">{{ $tx->user?->name ?? '—' }}</td>
                            <td class="px-4 py-3 font-medium">{{ $tx->stock?->symbol ?? '—' }}</td>
                            <td class="px-4 py-3">{{ strtoupper($tx->type) }}</td>
                            <td class="px-4 py-3">{{ number_format((float)$tx->quantity,6) }}</td>
                            <td class="px-4 py-3">{{ currency_symbol() }}{{ number_format((float)$tx->price_per_share,2) }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ ucfirst(str_replace('_',' ',(string)$tx->source)) }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ optional($tx->executed_at)->format('M d, Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center text-muted-foreground">No executions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $transactions->links() }}</div>
</div>
</x-admin-layout>
