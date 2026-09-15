<x-user-layout>
<x-slot name="header">Copy Execution</x-slot>
<div class="ui-page max-w-3xl">
<section class="ui-page-header">
    <div><p class="ui-kicker">Copy Trading History</p><h1 class="ui-heading">{{ $execution->relationship?->strategy?->name }}</h1><p class="ui-lead">Concise mirrored-trade record and current mark-to-market preview.</p></div>
    <a href="{{ route('copy-trading.executions') }}" class="ui-btn ui-btn-secondary">Back to History</a>
</section>

<div class="ui-panel p-6">
    <div class="flex items-start justify-between gap-4">
        <div><p class="text-xs text-muted-foreground">Execution #{{ $execution->id }}</p><h2 class="mt-1 text-xl font-semibold">{{ strtoupper($execution->action) }} {{ $execution->followerTrade?->stock?->symbol ?? $execution->providerTrade?->stock?->symbol }}</h2><p class="mt-1 text-sm text-muted-foreground">Provider: {{ $execution->relationship?->provider?->name }}</p></div>
        <span class="ui-status">{{ ucfirst($execution->status) }}</span>
    </div>

    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-xl border border-border p-4"><p class="text-xs text-muted-foreground">Requested Amount</p><p class="mt-1 font-semibold">{{ format_currency($execution->requested_amount) }}</p></div>
        <div class="rounded-xl border border-border p-4"><p class="text-xs text-muted-foreground">Executed Amount</p><p class="mt-1 font-semibold">{{ format_currency($execution->executed_amount) }}</p></div>
        <div class="rounded-xl border border-border p-4"><p class="text-xs text-muted-foreground">Copy Percentage</p><p class="mt-1 font-semibold">{{ number_format((float)$execution->relationship?->copy_ratio_percent,0) }}%</p></div>
        <div class="rounded-xl border border-border p-4"><p class="text-xs text-muted-foreground">Current Price</p><p class="mt-1 font-semibold">{{ format_currency($currentPrice) }}</p></div>
        <div class="rounded-xl border border-border p-4"><p class="text-xs text-muted-foreground">Current P/L</p><p class="mt-1 font-semibold {{ $profitLoss<0?'text-red-600':($profitLoss>0?'text-green-600':'') }}">{{ $profitLoss>0?'+':'' }}{{ format_currency($profitLoss) }}</p></div>
        <div class="rounded-xl border border-border p-4"><p class="text-xs text-muted-foreground">Current Return</p><p class="mt-1 font-semibold">{{ $returnPercent>0?'+':'' }}{{ number_format($returnPercent,2) }}%</p></div>
    </div>

    @if($execution->failure_reason)
        <div class="mt-5 rounded-xl border border-border bg-muted/30 p-4 text-sm text-muted-foreground">{{ $execution->failure_reason }}</div>
    @endif

    <div class="mt-5 border-t border-border pt-4 text-xs text-muted-foreground">
        Executed {{ optional($execution->executed_at)->format('M d, Y · h:i A') ?? '—' }}
    </div>
</div>
</div>
</x-user-layout>