<x-user-layout>
<x-slot name="header">Provider Dashboard</x-slot>
<div class="ui-page max-w-[1440px]">
<section class="ui-page-header">
    <div>
        <p class="ui-kicker text-[10px]">Copy Trading</p>
        <h1 class="ui-heading !text-xl">Provider Dashboard</h1>
        <p class="ui-lead !text-[13px]">Published strategies, copier activity and mirrored execution performance.</p>
    </div>
</section>

<div class="grid gap-4 xl:grid-cols-2">
@foreach($profile->strategies as $strategy)
    @php($m = $strategy->performance_metrics)
    <article class="ui-panel p-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="flex gap-2">
                    <span class="rounded-full border border-amber-500/20 bg-amber-500/10 px-2 py-1 text-[10px] font-semibold text-amber-600">{{ ucfirst($strategy->risk_level) }} risk</span>
                    <span class="rounded-full border border-border bg-muted px-2 py-1 text-[10px] font-semibold text-muted-foreground">{{ $strategy->relationships->where('status','active')->count() }} active copiers</span>
                </div>
                <h2 class="mt-2 text-base font-semibold">{{ $strategy->name }}</h2>
                <p class="mt-1 text-xs text-muted-foreground">{{ $strategy->description }}</p>
            </div>
            <form method="POST" action="{{ route('copy-trading.provider.strategy.toggle',$strategy) }}">
                @csrf @method('PATCH')
                <button class="ui-btn ui-btn-secondary !h-8 !px-3 !text-[11px]">{{ $strategy->is_active ? 'Pause' : 'Activate' }}</button>
            </form>
        </div>

        <div class="mt-4 grid grid-cols-4 gap-2">
            @foreach([
                ['P/L', ($m['profit_loss'] > 0 ? '+' : '').format_currency($m['profit_loss'])],
                ['Return', ($m['return_percent'] > 0 ? '+' : '').number_format($m['return_percent'],2).'%'],
                ['Executions', $m['completed_count']],
                ['Positive Rate', number_format($m['positive_execution_rate'] ?? $m['win_rate'],1).'%'],
            ] as [$label,$value])
                <div class="rounded-lg border border-border bg-muted/10 p-2.5">
                    <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p>
                    <p class="mt-1 text-[11px] font-semibold">{{ $value }}</p>
                </div>
            @endforeach
        </div>
    </article>
@endforeach
</div>

<section class="mt-5">
    <div class="mb-2">
        <p class="text-[10px] uppercase tracking-[.13em] text-muted-foreground">Provider activity</p>
        <h2 class="mt-1 text-sm font-semibold">Recent Copied Executions</h2>
    </div>
    <div class="space-y-2">
        @forelse($executions as $e)
            <div class="ui-panel flex items-center justify-between gap-3 p-3.5">
                <div>
                    <p class="text-xs font-semibold">{{ $e->display_symbol }} · {{ $e->asset_class }} · {{ ucfirst($e->status) }}</p>
                    <p class="mt-1 text-[10px] text-muted-foreground">Follower {{ $e->relationship?->follower?->name }} · {{ optional($e->executed_at)->format('M d · H:i') }}</p>
                </div>
                <p class="text-xs font-semibold">{{ format_currency($e->executed_amount) }}</p>
            </div>
        @empty
            <div class="ui-panel p-6 text-center text-xs text-muted-foreground">No copied executions yet.</div>
        @endforelse
    </div>
</section>
</div>
</x-user-layout>
