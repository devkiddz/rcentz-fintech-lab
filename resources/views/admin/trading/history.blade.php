<x-admin-layout>
<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Trading Command · History</p>
            <h1 class="ui-heading">Trade history</h1>
            <p class="ui-lead">One row per trade contract with entry, exit/current price, profit/loss and direct access to the full trade record.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.stocks.transactions.index') }}" class="ui-btn ui-btn-secondary">Execution Ledger</a>
            <a href="{{ route('admin.trading.index') }}" class="ui-btn ui-btn-secondary">Trading Overview</a>
        </div>
    </section>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Total trades',$historyStats['total'],'layers-3'],
            ['Open',$historyStats['open'],'target'],
            ['Closed',$historyStats['closed'],'circle-check'],
            ['Realized P/L',($historyStats['realized_profit_loss'] >= 0 ? '+' : '-').currency_symbol().number_format(abs($historyStats['realized_profit_loss']),2),'badge-dollar-sign'],
        ] as [$label,$value,$icon])
            <div class="ui-panel p-4">
                <div class="flex items-center justify-between">
                    <p class="text-[9px] uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p>
                    <i data-lucide="{{ $icon }}" class="h-4 w-4 text-muted-foreground"></i>
                </div>
                <p class="mt-3 text-xl font-semibold tabular-nums
                    {{ $label === 'Realized P/L' ? ($historyStats['realized_profit_loss'] >= 0 ? 'text-emerald-600' : 'text-red-600') : '' }}">
                    {{ is_numeric($value) ? number_format($value) : $value }}
                </p>
            </div>
        @endforeach
    </div>

    <div class="ui-panel mt-4 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1220px] text-left text-xs">
                <thead class="border-b border-border bg-muted/30 text-[9px] uppercase tracking-[.12em] text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3">Trade</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Asset</th>
                        <th class="px-4 py-3">Entry</th>
                        <th class="px-4 py-3">Exit / CMP</th>
                        <th class="px-4 py-3">Qty</th>
                        <th class="px-4 py-3">Profit / Loss</th>
                        <th class="px-4 py-3">Return</th>
                        <th class="px-4 py-3">Source</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Opened</th>
                        <th class="px-4 py-3 text-right">Record</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($positions as $position)
                        @php
                            $isOpen = $position->is_open;
                            $exit = $isOpen
                                ? (float)($position->stock?->current_price ?? 0)
                                : (float)($position->average_exit_price ?: $position->lastExitTransaction?->price_per_share ?: 0);
                            $pnl = $isOpen ? (float)$position->current_profit_loss : (float)$position->realized_profit_loss;
                            $ret = $isOpen ? (float)$position->current_return_percent : (float)$position->realized_return_percent;
                        @endphp
                        <tr class="hover:bg-muted/20">
                            <td class="px-4 py-3 font-semibold">
                                <a href="{{ route('admin.trading.positions.show',$position) }}" class="hover:underline">#{{ $position->id }}</a>
                            </td>
                            <td class="px-4 py-3">{{ $position->user?->name ?? '—' }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $position->stock?->symbol ?? '—' }}</td>
                            <td class="px-4 py-3 font-medium tabular-nums">{{ currency_symbol() }}{{ number_format((float)$position->entry_price,2) }}</td>
                            <td class="px-4 py-3 font-medium tabular-nums">{{ currency_symbol() }}{{ number_format($exit,2) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ number_format((float)$position->initial_quantity,6) }}</td>
                            <td class="px-4 py-3 font-semibold tabular-nums {{ $pnl >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $pnl >= 0 ? '+' : '-' }}{{ currency_symbol() }}{{ number_format(abs($pnl),2) }}
                            </td>
                            <td class="px-4 py-3 font-medium tabular-nums {{ $ret >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $ret >= 0 ? '+' : '' }}{{ number_format($ret,2) }}%
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">{{ ucfirst(str_replace('_',' ',$position->context_type)) }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full border px-2 py-1 text-[9px] font-semibold
                                    {{ $isOpen ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : 'border-border bg-muted text-muted-foreground' }}">
                                    {{ $isOpen ? 'Open' : ucfirst(str_replace('_',' ',$position->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">{{ optional($position->opened_at)->format('M d, Y H:i') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.trading.positions.show',$position) }}" class="ui-btn ui-btn-secondary !h-8 !px-3">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="px-4 py-10 text-center text-muted-foreground">No trade contracts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $positions->links() }}</div>
</div>
</x-admin-layout>
