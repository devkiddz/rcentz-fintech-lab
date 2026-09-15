<x-user-layout>
<x-slot name="header">Bot Performance</x-slot>
<div class="ui-page max-w-[1440px]">
<section class="ui-page-header"><div><p class="ui-kicker">AI Trading Bots</p><h1 class="ui-heading">Performance & Executions</h1><p class="ui-lead">Execution statistics calculated from your bot activity.</p></div></section>
<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
<div class="ui-panel p-4"><p class="text-xs text-muted-foreground">Total Trades</p><p class="mt-1 text-2xl font-semibold">{{ $summary['trade_count'] }}</p></div>
<div class="ui-panel p-4"><p class="text-xs text-muted-foreground">Executed Volume</p><p class="mt-1 text-2xl font-semibold">{{ format_currency($summary['volume']) }}</p></div>
<div class="ui-panel p-4"><p class="text-xs text-muted-foreground">Profit / Loss</p><p class="mt-1 text-2xl font-semibold">{{ $summary['profit_loss']>0?'+':'' }}{{ format_currency($summary['profit_loss']) }}</p></div>
<div class="ui-panel p-4"><p class="text-xs text-muted-foreground">Return</p><p class="mt-1 text-2xl font-semibold">{{ $summary['return_percent']>0?'+':'' }}{{ number_format($summary['return_percent'],2) }}%</p></div>
<div class="ui-panel p-4"><p class="text-xs text-muted-foreground">Win Rate</p><p class="mt-1 text-2xl font-semibold">{{ number_format($summary['win_rate'],1) }}%</p></div>
</div>
<div class="ui-table-shell mt-6 overflow-x-auto"><table class="w-full text-sm"><thead class="ui-table-head"><tr><th class="p-4 text-left">Bot</th><th>Asset</th><th>Action</th><th>Entry Price</th><th>Amount</th><th>Status</th><th>Executed</th><th class="p-4"></th></tr></thead><tbody>@foreach($executions as $e)<tr class="ui-table-row"><td class="p-4 font-medium">{{ $e->subscription?->product?->name ?? $e->bot?->name }}</td><td>{{ $e->bot?->stock?->symbol }}</td><td>{{ ucfirst($e->action) }}</td><td>{{ format_currency($e->price) }}</td><td>{{ format_currency($e->amount) }}</td><td>{{ ucfirst($e->status) }}</td><td class="p-4">{{ optional($e->executed_at)->format('M d, Y H:i') }}</td><td class="p-4 text-right"><a href="{{ route('ai-bots.executions.show',$e) }}" class="ui-btn ui-btn-secondary">View</a></td></tr>@endforeach</tbody></table></div>
{{ $executions->links() }}
</div>
</x-user-layout>