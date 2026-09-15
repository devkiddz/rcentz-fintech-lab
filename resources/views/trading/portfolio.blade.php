<x-user-layout>
<x-slot name="header">Stock Portfolio</x-slot>

<div class="ui-page max-w-[1440px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker text-[10px]">Trading</p>
            <h1 class="ui-heading !text-2xl">Stock Portfolio</h1>
            <p class="ui-lead !text-[13px]">Positions, current performance and timed trade plans.</p>
        </div>
        <a href="{{ route('stocks.index') }}" class="ui-btn ui-btn-primary"><i data-lucide="plus" class="h-4 w-4"></i> Browse Stocks</a>
    </section>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Total Value',currency_symbol().number_format($totalCurrentValue,2),'wallet-cards'],
            ['Invested Capital',currency_symbol().number_format($totalInvested,2),'landmark'],
            ['Open P/L',($totalGainLoss>=0?'+':'').currency_symbol().number_format($totalGainLoss,2),'trending-up'],
            ['Holdings',$holdings->count(),'layers-3'],
        ] as [$label,$value,$icon])
            <div class="ui-panel p-3.5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">{{ $label }}</p>
                        <p class="mt-1 text-lg font-semibold tabular-nums">{{ $value }}</p>
                    </div>
                    <i data-lucide="{{ $icon }}" class="h-4 w-4 text-muted-foreground"></i>
                </div>
            </div>
        @endforeach
    </div>

    @if(($tradePlans ?? collect())->count())
    <section class="mt-5">
        <div class="mb-2 flex items-end justify-between">
            <div>
                <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Trade timing</p>
                <h2 class="mt-1 text-sm font-semibold">Active Trade Plans</h2>
            </div>
            <p class="text-[10px] text-muted-foreground">Scheduler checks due plans every minute.</p>
        </div>

        <div class="grid gap-3 lg:grid-cols-2">
            @foreach($tradePlans as $plan)
                <article class="ui-panel p-4" data-trade-plan data-due-at="{{ optional($plan->due_at)->toIso8601String() }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full border border-sky-500/20 bg-sky-500/10 px-2 py-1 text-[9px] font-semibold text-sky-600">{{ $plan->stock?->symbol }}</span>
                                <span class="rounded-full border border-border bg-muted px-2 py-1 text-[9px] font-semibold text-muted-foreground">{{ ucfirst($plan->mode) }}</span>
                                <span class="rounded-full border {{ $plan->status==='due'?'border-amber-500/20 bg-amber-500/10 text-amber-600':'border-emerald-500/20 bg-emerald-500/10 text-emerald-600' }} px-2 py-1 text-[9px] font-semibold">{{ ucfirst($plan->status) }}</span>
                            </div>
                            <h3 class="mt-2 text-sm font-semibold">{{ $plan->planned_action === 'sell' ? 'Planned Sell' : 'Planned Buy Back' }}</h3>
                            <p class="mt-1 text-[10px] text-muted-foreground">{{ number_format((float)$plan->quantity,6) }} shares · {{ $plan->duration_minutes }} minute horizon</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[9px] uppercase tracking-[.11em] text-muted-foreground">Time remaining</p>
                            <p class="mt-1 text-sm font-semibold tabular-nums" data-countdown>--:--:--</p>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between border-t border-border pt-3">
                        <p class="text-[10px] text-muted-foreground">
                            Due {{ optional($plan->due_at)->format('M d, Y · H:i') }}
                        </p>
                        <div class="flex gap-2">
                            @if($plan->status === 'due' && $plan->mode === 'reminder')
                                <a href="{{ $plan->planned_action === 'sell' ? route('trading.sell',$plan->stock) : route('trading.buy',$plan->stock) }}"
                                   class="ui-btn ui-btn-primary !h-8 !px-3 !text-[10px]">
                                    {{ $plan->planned_action === 'sell' ? 'Review & Sell' : 'Review & Buy Back' }}
                                </a>
                            @endif
                            <form method="POST" action="{{ route('trading.plans.cancel',$plan) }}">
                                @csrf @method('DELETE')
                                <button class="ui-btn ui-btn-secondary !h-8 !px-3 !text-[10px]">Cancel</button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
    @endif

    <section class="ui-panel mt-5 overflow-hidden">
        <div class="border-b border-border px-4 py-3">
            <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Positions</p>
            <h2 class="mt-1 text-sm font-semibold">Your Holdings</h2>
        </div>

        @if($holdings->count())
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px]">
                    <thead class="bg-muted/20">
                        <tr class="text-left text-[9px] uppercase tracking-[.11em] text-muted-foreground">
                            <th class="px-4 py-3 font-medium">Stock</th>
                            <th class="px-4 py-3 font-medium">Shares</th>
                            <th class="px-4 py-3 font-medium">Avg. Entry</th>
                            <th class="px-4 py-3 font-medium">Current</th>
                            <th class="px-4 py-3 font-medium">Value</th>
                            <th class="px-4 py-3 font-medium">Open P/L</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($holdings as $holding)
                            <tr class="hover:bg-muted/10">
                                <td class="px-4 py-3"><p class="text-xs font-semibold">{{ $holding->stock->symbol }}</p><p class="mt-0.5 text-[9px] text-muted-foreground">{{ $holding->stock->company_name }}</p></td>
                                <td class="px-4 py-3 text-xs tabular-nums">{{ number_format((float)$holding->quantity,6) }}</td>
                                <td class="px-4 py-3 text-xs tabular-nums">{{ currency_symbol() }}{{ number_format((float)$holding->average_buy_price,2) }}</td>
                                <td class="px-4 py-3 text-xs tabular-nums">{{ currency_symbol() }}{{ number_format((float)$holding->stock->current_price,2) }}</td>
                                <td class="px-4 py-3 text-xs font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format((float)$holding->current_value,2) }}</td>
                                <td class="px-4 py-3"><p class="text-xs font-semibold {{ $holding->unrealized_gain_loss>=0?'text-emerald-600':'text-red-600' }}">{{ $holding->unrealized_gain_loss>=0?'+':'' }}{{ currency_symbol() }}{{ number_format((float)$holding->unrealized_gain_loss,2) }}</p><p class="text-[9px] {{ $holding->unrealized_gain_loss_percentage>=0?'text-emerald-600':'text-red-600' }}">{{ number_format((float)$holding->unrealized_gain_loss_percentage,2) }}%</p></td>
                                <td class="px-4 py-3"><div class="flex justify-end gap-2"><a href="{{ route('trading.buy',$holding->stock) }}" class="ui-btn ui-btn-secondary !h-8 !px-3 !text-[10px]">Buy More</a><a href="{{ route('trading.sell',$holding->stock) }}" class="ui-btn ui-btn-primary !h-8 !px-3 !text-[10px]">Sell</a></div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-10 text-center"><p class="text-sm font-medium">No holdings yet</p><p class="mt-1 text-xs text-muted-foreground">Buy your first stock to start building this portfolio.</p></div>
        @endif
    </section>

    @if($recentTransactions->count())
    <section class="ui-panel mt-5 overflow-hidden">
        <div class="border-b border-border px-4 py-3">
            <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">History</p>
            <h2 class="mt-1 text-sm font-semibold">Recent Transactions</h2>
        </div>
        <div class="divide-y divide-border">
            @foreach($recentTransactions as $transaction)
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                    <div>
                        <p class="text-xs font-semibold">{{ $transaction->stock->symbol }} · {{ ucfirst($transaction->type) }}</p>
                        <p class="mt-1 text-[9px] text-muted-foreground">{{ number_format((float)$transaction->quantity,6) }} shares · {{ optional($transaction->executed_at)->format('M d · H:i') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format((float)$transaction->total_amount,2) }}</p>
                        <p class="mt-1 text-[9px] text-muted-foreground">@ {{ currency_symbol() }}{{ number_format((float)$transaction->price_per_share,2) }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const plans = document.querySelectorAll('[data-trade-plan]');
    function tick() {
        const now = Date.now();
        plans.forEach(function(plan){
            const due = new Date(plan.dataset.dueAt).getTime();
            const out = plan.querySelector('[data-countdown]');
            if (!out || Number.isNaN(due)) return;
            let seconds = Math.max(0, Math.floor((due-now)/1000));
            if (seconds <= 0) {
                out.textContent = 'DUE';
                out.classList.add('text-amber-600');
                return;
            }
            const days = Math.floor(seconds/86400); seconds %= 86400;
            const hours = Math.floor(seconds/3600); seconds %= 3600;
            const mins = Math.floor(seconds/60); const secs = seconds%60;
            out.textContent = (days ? days+'d ' : '') + String(hours).padStart(2,'0') + ':' + String(mins).padStart(2,'0') + ':' + String(secs).padStart(2,'0');
        });
    }
    tick();
    setInterval(tick,1000);
});
</script>
</x-user-layout>
