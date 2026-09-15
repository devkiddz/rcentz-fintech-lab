<x-user-layout>
<x-slot name="header">AI Trading Bots</x-slot>
<div class="ui-page max-w-[1440px]">
<section class="ui-page-header">
    <div><p class="ui-kicker">AI Trading Bots</p><h1 class="ui-heading">Bot Marketplace</h1><p class="ui-lead">Admin-curated automation with transparent limits, trade counts and current performance.</p></div>
    <a href="{{ route('ai-bots.my-bots') }}" class="ui-btn ui-btn-secondary">My Bots</a>
</section>
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
@forelse($products as $product)
@php($m=$product->performance_metrics)
<a href="{{ route('ai-bots.show',$product) }}" class="ui-panel block p-5 transition hover:-translate-y-0.5">
    <div class="flex justify-between gap-3"><div><p class="text-xs text-muted-foreground">{{ $product->stock->symbol }} · {{ strtoupper(str_replace('_',' ',$product->strategy)) }}</p><h2 class="mt-1 text-lg font-semibold">{{ $product->name }}</h2></div><span class="ui-status">{{ ucfirst($product->risk_level) }} risk</span></div>
    <p class="mt-3 text-sm text-muted-foreground">{{ $product->description }}</p>
    <div class="mt-5 grid grid-cols-2 gap-3">
        <div class="rounded-xl border border-border p-3"><p class="text-[11px] uppercase text-muted-foreground">{{ $m['is_manual_performance'] ? 'Preview P/L' : 'Profit / Loss' }}</p><p class="mt-1 font-semibold {{ $m['profit_loss']<0?'text-red-600':($m['profit_loss']>0?'text-green-600':'') }}">{{ $m['profit_loss']>0?'+':'' }}{{ format_currency($m['profit_loss']) }}</p></div>
        <div class="rounded-xl border border-border p-3"><p class="text-[11px] uppercase text-muted-foreground">{{ $m['is_manual_performance'] ? 'Preview Return' : 'Return' }}</p><p class="mt-1 font-semibold">{{ $m['return_percent']>0?'+':'' }}{{ number_format($m['return_percent'],2) }}%</p></div>
        <div class="rounded-xl border border-border p-3"><p class="text-[11px] uppercase text-muted-foreground">Executed Trades</p><p class="mt-1 font-semibold">{{ $m['completed_count'] }}</p></div>
        <div class="rounded-xl border border-border p-3"><p class="text-[11px] uppercase text-muted-foreground">Win Rate</p><p class="mt-1 font-semibold">{{ number_format($m['win_rate'],1) }}%</p></div>
    </div>
    <div class="mt-4 flex justify-between border-t border-border pt-4"><div><p class="text-[11px] uppercase text-muted-foreground">Access Price</p><p class="font-semibold">{{ $product->price>0?format_currency($product->price):'Free' }}</p></div><div class="text-right"><p class="text-[11px] uppercase text-muted-foreground">Minimum Balance</p><p class="font-semibold">{{ format_currency($product->minimum_balance) }}</p></div></div>
</a>
@empty
<div class="ui-panel p-8 md:col-span-2 xl:col-span-3 text-center text-muted-foreground">No bot products are currently available.</div>
@endforelse
</div>
</div>
</x-user-layout>