<x-user-layout>
<x-slot name="header">My AI Bots</x-slot>

<div class="ui-page max-w-[1440px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">AI Trading Bots</p>
            <h1 class="ui-heading">My Bots</h1>
            <p class="ui-lead">Your subscribed automation, runtime controls and live execution performance.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a class="ui-btn ui-btn-secondary" href="{{ route('ai-bots.performance') }}">Execution History</a>
            <a class="ui-btn ui-btn-secondary" href="{{ route('ai-bots.marketplace') }}">Bot Marketplace</a>
        </div>
    </section>

    <div class="space-y-5">
        @forelse($subscriptions as $subscription)
            @php
                $m = $subscription->performance_metrics;
                $bot = $subscription->bot;
                $product = $subscription->product;
                $completed = max(0, (int)$m['completed_count']);
                $wins = max(0, (int)$m['winning_trades']);
                $losses = max(0, (int)$m['losing_trades']);
                $neutral = max(0, $completed - $wins - $losses);
                $allocation = (float)($bot?->max_total_spend ?? 0);
                $spent = (float)($bot?->spent_total ?? 0);
                $allocationPct = $allocation > 0 ? min(100, ($spent / $allocation) * 100) : 0;
            @endphp

            <article class="ui-panel overflow-hidden">
                <div class="p-5 sm:p-6">
                    <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full border border-border bg-muted/40 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">{{ $product->stock->symbol }}</span>
                                <span class="rounded-full border border-border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">{{ strtoupper(str_replace('_',' ',$product->strategy)) }}</span>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $bot?->status === 'active' ? 'bg-green-500/10 text-green-600' : 'bg-muted text-muted-foreground' }}">
                                    {{ $bot?->status === 'active' ? 'Running' : 'Paused' }}
                                </span>
                            </div>

                            <h2 class="mt-3 text-xl font-semibold tracking-tight">{{ $product->name }}</h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Subscription {{ ucfirst($subscription->status) }}
                                @if($subscription->ends_at) · Renews/expires {{ $subscription->ends_at->format('M d, Y') }} @endif
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('ai-bots.configure',$subscription) }}" class="ui-btn ui-btn-primary">Configure</a>
                            <form method="POST" action="{{ route('ai-bots.toggle',$subscription) }}">
                                @csrf
                                <button class="ui-btn ui-btn-secondary">{{ $bot?->status === 'active' ? 'Pause Bot' : 'Activate Bot' }}</button>
                            </form>
                            <form method="POST" action="{{ route('ai-bots.run',$subscription) }}">
                                @csrf
                                <button class="ui-btn ui-btn-secondary">Run Now</button>
                            </form>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-6 lg:grid-cols-[1.05fr_.95fr]">
                        <div class="rounded-2xl bg-muted/25 p-5">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                                        {{ $m['is_manual_performance'] ? 'Preview Profit / Loss' : 'Current Profit / Loss' }}
                                    </p>
                                    <p class="mt-2 text-3xl font-semibold tracking-tight {{ $m['profit_loss'] < 0 ? 'text-red-600' : ($m['profit_loss'] > 0 ? 'text-green-600' : 'text-foreground') }}">
                                        {{ $m['profit_loss'] > 0 ? '+' : '' }}{{ format_currency($m['profit_loss']) }}
                                    </p>
                                </div>

                                <div class="sm:text-right">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                                        {{ $m['is_manual_performance'] ? 'Preview Return' : 'Current Return' }}
                                    </p>
                                    <p class="mt-2 text-2xl font-semibold {{ $m['return_percent'] < 0 ? 'text-red-600' : ($m['return_percent'] > 0 ? 'text-green-600' : '') }}">
                                        {{ $m['return_percent'] > 0 ? '+' : '' }}{{ number_format($m['return_percent'],2) }}%
                                    </p>
                                </div>
                            </div>

                            <div class="mt-6 border-t border-border pt-5">
                                <div class="flex items-end justify-between gap-4">
                                    <div>
                                        <p class="text-xs text-muted-foreground">Trading activity</p>
                                        <p class="mt-1 text-2xl font-semibold">{{ $completed }} <span class="text-sm font-normal text-muted-foreground">executions</span></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-muted-foreground">Positive execution rate</p>
                                        <p class="mt-1 text-lg font-semibold">{{ number_format($m['win_rate'],1) }}%</p>
                                    </div>
                                </div>

                                <div class="mt-4 flex h-2.5 overflow-hidden rounded-full bg-muted">
                                    @if($completed > 0)
                                        <div class="bg-green-500" style="width: {{ ($wins / $completed) * 100 }}%"></div>
                                        <div class="bg-red-500" style="width: {{ ($losses / $completed) * 100 }}%"></div>
                                        <div class="bg-muted-foreground/30" style="width: {{ ($neutral / $completed) * 100 }}%"></div>
                                    @endif
                                </div>
                                <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-xs text-muted-foreground">
                                    <span><strong class="text-foreground">{{ $wins }}</strong> positive</span>
                                    <span><strong class="text-foreground">{{ $losses }}</strong> negative</span>
                                    <span><strong class="text-foreground">{{ $neutral }}</strong> neutral</span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-x-6 gap-y-5 sm:grid-cols-3 lg:grid-cols-2 xl:grid-cols-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Per Trade</p>
                                <p class="mt-1.5 text-lg font-semibold">{{ format_currency($bot?->amount_per_trade ?? 0) }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Daily Limit</p>
                                <p class="mt-1.5 text-lg font-semibold">{{ $bot?->max_daily_trades ?? 0 }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">{{ $product->strategy === 'dca' ? 'Run Every' : 'Check Every' }}</p>
                                <p class="mt-1.5 text-lg font-semibold">{{ $bot?->interval_minutes ?? 0 }} <span class="text-xs font-normal text-muted-foreground">min</span></p>
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Allocation Cap</p>
                                <p class="mt-1.5 text-lg font-semibold">{{ $allocation > 0 ? format_currency($allocation) : 'Open' }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Deployed</p>
                                <p class="mt-1.5 text-lg font-semibold">{{ format_currency($spent) }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Next Run</p>
                                <p class="mt-1.5 text-sm font-semibold">{{ optional($bot?->next_run_at)->format('M d · H:i') ?? '—' }}</p>
                            </div>

                            <div class="col-span-2 sm:col-span-3 lg:col-span-2 xl:col-span-3">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-muted-foreground">Allocation used</span>
                                    <span class="font-medium">{{ number_format($allocationPct,1) }}%</span>
                                </div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-muted">
                                    <div class="h-full rounded-full bg-foreground transition-all" style="width: {{ $allocationPct }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="ui-panel p-10 text-center">
                <p class="font-medium">No subscribed bots yet.</p>
                <p class="mt-1 text-sm text-muted-foreground">Choose a bot from the marketplace to create your first runtime instance.</p>
                <a href="{{ route('ai-bots.marketplace') }}" class="ui-btn ui-btn-primary mt-5">Explore Bot Marketplace</a>
            </div>
        @endforelse
    </div>
</div>
</x-user-layout>