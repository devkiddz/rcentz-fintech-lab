<x-user-layout>
<x-slot name="header">Copy Trading</x-slot>
<div class="ui-page max-w-[1440px]">
<section class="ui-page-header"><div><p class="ui-kicker">Copy Trading</p><h1 class="ui-heading">Strategy Marketplace</h1><p class="ui-lead">Approved strategies with transparent allocation, trade count and current performance labels.</p></div><div class="ui-header-actions"><a href="{{ route('copy-trading.my-copies') }}" class="ui-btn ui-btn-secondary">My Copies</a><a href="{{ route('copy-trading.apply') }}" class="ui-btn ui-btn-secondary">Become a Provider</a></div></section>
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
@forelse($strategies as $strategy)
@php($m=$strategy->performance_metrics)
<div class="ui-panel p-5">
<div class="flex justify-between gap-4"><div><p class="text-xs text-muted-foreground">{{ $strategy->profile->user->name }}</p><h2 class="mt-1 text-lg font-semibold">{{ $strategy->name }}</h2></div><span class="ui-status">{{ ucfirst($strategy->risk_level) }} risk</span></div>
<p class="mt-3 text-sm text-muted-foreground">{{ $strategy->description }}</p>
<div class="mt-5 grid grid-cols-2 gap-3">
<div class="rounded-xl border border-border p-3"><p class="text-[11px] uppercase text-muted-foreground">{{ $m['is_manual_performance'] ? 'Preview P/L' : 'Profit / Loss' }}</p><p class="mt-1 font-semibold">{{ $m['profit_loss']>0?'+':'' }}{{ format_currency($m['profit_loss']) }}</p></div>
<div class="rounded-xl border border-border p-3"><p class="text-[11px] uppercase text-muted-foreground">{{ $m['is_manual_performance'] ? 'Preview Return' : 'Return' }}</p><p class="mt-1 font-semibold">{{ $m['return_percent']>0?'+':'' }}{{ number_format($m['return_percent'],2) }}%</p></div>
<div class="rounded-xl border border-border p-3"><p class="text-[11px] uppercase text-muted-foreground">Executed Trades</p><p class="mt-1 font-semibold">{{ $m['completed_count'] }}</p></div>
<div class="rounded-xl border border-border p-3"><p class="text-[11px] uppercase text-muted-foreground">Win Rate</p><p class="mt-1 font-semibold">{{ number_format($m['win_rate'],1) }}%</p></div>
<div class="rounded-xl border border-border p-3"><p class="text-[11px] uppercase text-muted-foreground">Minimum Amount</p><p class="mt-1 font-semibold">{{ format_currency($strategy->minimum_allocation) }}</p></div>
<div class="rounded-xl border border-border p-3"><p class="text-[11px] uppercase text-muted-foreground">Active Copiers</p><p class="mt-1 font-semibold">{{ $strategy->copier_count }}</p></div>
</div>
<form action="{{ route('copy-trading.follow',$strategy) }}" method="POST" class="mt-5 space-y-3 border-t border-border pt-5">@csrf<div><label class="ui-label">Allocation Amount</label><input name="allocation_limit" class="ui-input" type="number" min="{{ $strategy->minimum_allocation }}" value="{{ $strategy->recommended_allocation ?: $strategy->minimum_allocation }}"></div><div><label class="ui-label">Maximum Per Trade</label><input name="max_trade_amount" class="ui-input" type="number" min="10" value="{{ $strategy->minimum_allocation }}"></div><div><label class="ui-label">Copy Percentage</label><input name="copy_ratio_percent" class="ui-input" type="number" min="1" max="200" value="100"></div><button class="ui-btn ui-btn-primary w-full">Copy strategy</button></form>
</div>
@empty
<div class="ui-panel p-8 md:col-span-2 xl:col-span-3 text-center text-muted-foreground">No approved strategies are available yet.</div>
@endforelse
</div>
</div>
</x-user-layout>