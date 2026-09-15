<x-admin-layout>
<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Trading Command · Positions</p>
            <h1 class="ui-heading">Position management</h1>
            <p class="ui-lead">Platform-wide view of customer, strategy and bot-attributed trade contracts.</p>
        </div>
        <a href="{{ route('admin.trading.index') }}" class="ui-btn ui-btn-secondary">Trading Overview</a>
    </section>

    <div class="ui-panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-border bg-muted/30 text-[9px] uppercase tracking-[.12em] text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3">Position</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Asset</th>
                        <th class="px-4 py-3">Context</th>
                        <th class="px-4 py-3">EMP</th>
                        <th class="px-4 py-3">Open Qty</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Opened</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($positions as $position)
                        <tr class="hover:bg-muted/20">
                            <td class="px-4 py-3 font-medium">#{{ $position->id }}</td>
                            <td class="px-4 py-3">{{ $position->user?->name ?? '—' }}</td>
                            <td class="px-4 py-3 font-medium">{{ $position->stock?->symbol ?? '—' }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ ucfirst(str_replace('_',' ',$position->context_type)) }}</td>
                            <td class="px-4 py-3">{{ currency_symbol() }}{{ number_format((float)$position->entry_price,2) }}</td>
                            <td class="px-4 py-3">{{ number_format((float)$position->open_quantity,6) }}</td>
                            <td class="px-4 py-3">{{ ucfirst(str_replace('_',' ',$position->status)) }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ optional($position->opened_at)->format('M d, Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center text-muted-foreground">No positions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $positions->links() }}</div>
</div>
</x-admin-layout>
