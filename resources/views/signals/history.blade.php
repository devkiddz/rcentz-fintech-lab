<x-user-layout>
    <x-slot name="header">Signal History</x-slot>

    <div class="mx-auto max-w-7xl space-y-4">
        <section class="ui-panel overflow-hidden">
            <div class="flex flex-col gap-4 px-5 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="ui-kicker">Signal intelligence</p>
                    <h1 class="mt-1 text-xl font-semibold text-foreground">Signal history</h1>
                    <p class="mt-1 max-w-2xl text-sm text-muted-foreground">Completed, stopped, expired, invalidated and cancelled Signals previously delivered to your account.</p>
                </div>
                @include('signals._nav')
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-3">
            <article class="ui-metric-card"><div class="ui-metric-icon"><i data-lucide="history" class="h-5 w-5"></i></div><div><p class="ui-label">History</p><p class="mt-2 text-2xl font-semibold">{{ number_format($summary['history']) }}</p></div></article>
            <article class="ui-metric-card"><div class="ui-metric-icon"><i data-lucide="radio-tower" class="h-5 w-5"></i></div><div><p class="ui-label">Current</p><p class="mt-2 text-2xl font-semibold">{{ number_format($summary['current']) }}</p></div></article>
            <article class="ui-metric-card"><div class="ui-metric-icon"><i data-lucide="mail" class="h-5 w-5"></i></div><div><p class="ui-label">Unread deliveries</p><p class="mt-2 text-2xl font-semibold">{{ number_format($summary['unread_deliveries']) }}</p></div></article>
        </section>

        <section class="ui-panel overflow-hidden">
            @forelse($deliveries as $delivery)
                @php $signal=$delivery->signal; $precision=$signal?->price_precision ?? 2; @endphp
                <a href="{{ route('signals.show',$signal) }}" class="grid gap-3 border-b border-border px-5 py-4 transition last:border-b-0 hover:bg-muted/25 sm:grid-cols-[minmax(0,1.3fr)_repeat(4,minmax(0,.7fr))_auto] sm:items-center sm:px-6">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2"><span class="font-semibold">{{ $signal?->instrument_symbol ?? '—' }}</span><span class="text-[10px] font-semibold {{ strtoupper((string)$signal->direction)==='BUY' ? 'text-emerald-600' : 'text-red-600' }}">{{ strtoupper((string)$signal->direction) }}</span></div>
                        <p class="mt-1 truncate text-[11px] text-muted-foreground">{{ $signal?->instrument_name }}</p>
                    </div>
                    <div><p class="ui-label">Status</p><p class="mt-1 text-xs font-semibold">{{ strtoupper((string)$signal->status) }}</p></div>
                    <div><p class="ui-label">Timeframe</p><p class="mt-1 text-xs font-semibold">{{ strtoupper((string)$signal->timeframe) }}</p></div>
                    <div><p class="ui-label">Entry</p><p class="mt-1 text-xs font-semibold tabular-nums">{{ number_format((float)$signal->entry_min,$precision) }} – {{ number_format((float)$signal->entry_max,$precision) }}</p></div>
                    <div><p class="ui-label">Delivered</p><p class="mt-1 text-xs font-semibold">{{ optional($delivery->delivered_at)->format('M d, Y') }}</p></div>
                    <i data-lucide="chevron-right" class="h-4 w-4 text-muted-foreground"></i>
                </a>
            @empty
                <div class="ui-empty-state py-16"><div class="ui-empty-icon"><i data-lucide="history" class="h-5 w-5"></i></div><h2 class="font-medium">No Signal history yet</h2><p class="mt-1 text-sm text-muted-foreground">Terminal Signals delivered to you will remain available here.</p></div>
            @endforelse
        </section>

        @if($deliveries->hasPages())<div>{{ $deliveries->links() }}</div>@endif
    </div>
</x-user-layout>
