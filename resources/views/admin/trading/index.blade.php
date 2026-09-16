<x-admin-layout>
<div class="ui-page max-w-[1500px]">
    {{-- V5.9.1 trade terminology cleanup --}}
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Admin · Trading Command</p>
            <h1 class="ui-heading">Trading operations</h1>
            <p class="ui-lead">Performance, position lifecycle, instrument context and execution history from one command surface.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.trading.marketplace') }}" class="ui-btn ui-btn-primary">Marketplace Control</a>
            <a href="{{ route('admin.trading.manual') }}" class="ui-btn ui-btn-secondary">Trading Desk</a>
            <a href="{{ route('admin.trading.history') }}" class="ui-btn ui-btn-secondary">Trade History</a>
        </div>
    </section>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        @php
            $metricCards = [
                ['Active stocks', $activeStocks, 'activity', 'plain'],
                ['Open positions', $openPositions, 'target', 'plain'],
                ['Closed positions', $closedPositions, 'circle-check', 'plain'],
                ['Realized P/L', currency_symbol().number_format(abs($totalRealizedProfitLoss), 2), 'badge-dollar-sign', $totalRealizedProfitLoss >= 0 ? 'positive' : 'negative'],
                ['Open P/L', currency_symbol().number_format(abs($openProfitLoss), 2), 'chart-no-axes-combined', $openProfitLoss >= 0 ? 'positive' : 'negative'],
                ['Win rate', number_format($winRate, 1).'%', 'percent', 'plain'],
            ];
        @endphp

        @foreach($metricCards as [$label,$value,$icon,$tone])
            <div class="ui-panel p-4">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">{{ $label }}</p>
                    <i data-lucide="{{ $icon }}" class="h-4 w-4 text-muted-foreground"></i>
                </div>
                <p class="mt-3 text-xl font-semibold tabular-nums
                    {{ $tone === 'positive' ? 'text-emerald-600' : ($tone === 'negative' ? 'text-red-600' : '') }}">
                    @if(in_array($label, ['Realized P/L','Open P/L'], true))
                        {{ $tone === 'positive' ? '+' : '-' }}{{ $value }}
                    @else
                        {{ is_numeric($value) ? number_format($value) : $value }}
                    @endif
                </p>
            </div>
        @endforeach
    </div>

    @if($focusPosition && $focusPosition->stock)
        @php
            $focusIsOpen = $focusPosition->is_open;
            $focusExitPrice = $focusIsOpen
                ? app(\App\Services\MarketPriceRouter::class)->price(
                    $focusPosition->stock,
                    $focusPosition->marketplace ?: 'live'
                )
                : (float)($focusPosition->average_exit_price ?: $focusPosition->lastExitTransaction?->price_per_share ?: 0);
            $focusPnl = $focusIsOpen
                ? (float)$focusPosition->current_profit_loss
                : (float)$focusPosition->realized_profit_loss;
            $focusReturn = $focusIsOpen
                ? (float)$focusPosition->current_return_percent
                : (float)$focusPosition->realized_return_percent;
        @endphp

        <section class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1.45fr)_minmax(340px,.55fr)]">
            <div class="min-w-0">
                @include('trading.partials.analysis-chart', [
                    'stock' => $focusPosition->stock,
                    'analysis' => $focusAnalysis,
                    'chartHeight' => 'h-[330px] md:h-[390px]',
                ])
            </div>

            <aside class="ui-panel overflow-hidden">
                <div class="border-b border-border px-4 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="ui-kicker">Latest trade focus</p>
                            <h2 class="mt-1 text-base font-semibold">{{ $focusPosition->stock->symbol }} · Position #{{ $focusPosition->id }}</h2>
                            <p class="mt-1 text-[10px] text-muted-foreground">
                                {{ $focusPosition->user?->name ?? 'Unknown user' }} · {{ strtoupper($focusPosition->marketplace ?: 'live') }} Market
                            </p>
                        </div>
                        <span class="rounded-full border px-2 py-1 text-[9px] font-semibold
                            {{ $focusIsOpen ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : 'border-border bg-muted text-muted-foreground' }}">
                            {{ $focusIsOpen ? 'Open' : ucfirst(str_replace('_',' ',$focusPosition->status)) }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-px bg-border">
                    @foreach([
                        ['Entry price', currency_symbol().number_format((float)$focusPosition->entry_price,2)],
                        [$focusIsOpen ? 'Current price' : 'Exit price', currency_symbol().number_format($focusExitPrice,2)],
                        ['Profit / loss', ($focusPnl >= 0 ? '+' : '-').currency_symbol().number_format(abs($focusPnl),2)],
                        ['Return', ($focusReturn >= 0 ? '+' : '').number_format($focusReturn,2).'%'],
                        ['Initial qty', number_format((float)$focusPosition->initial_quantity,6)],
                        ['Open qty', number_format((float)$focusPosition->open_quantity,6)],
                    ] as [$label,$value])
                        <div class="bg-background p-3.5">
                            <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p>
                            <p class="mt-1.5 text-[11px] font-semibold tabular-nums
                                {{ $label === 'Profit / loss' ? ($focusPnl >= 0 ? 'text-emerald-600' : 'text-red-600') : '' }}">
                                {{ $value }}
                            </p>
                        </div>
                    @endforeach
                </div>

                <div class="space-y-2.5 border-t border-border p-4 text-[10px]">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-muted-foreground">Opened</span>
                        <span class="font-medium">{{ optional($focusPosition->opened_at)->format('M d, Y · H:i') }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-muted-foreground">{{ $focusIsOpen ? 'Effective end' : 'Closed' }}</span>
                        <span class="font-medium">
                            {{ optional($focusIsOpen ? $focusPosition->expires_at : $focusPosition->closed_at)->format('M d, Y · H:i') ?? '—' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-muted-foreground">Stop / Target</span>
                        <span class="font-medium tabular-nums">
                            {{ $focusPosition->stop_loss_price ? currency_symbol().number_format((float)$focusPosition->stop_loss_price,2) : '—' }}
                            /
                            {{ $focusPosition->take_profit_price ? currency_symbol().number_format((float)$focusPosition->take_profit_price,2) : '—' }}
                        </span>
                    </div>
                </div>

                <div class="border-t border-border p-4">
                    <a href="{{ route('admin.trading.positions.show',$focusPosition) }}" class="ui-btn ui-btn-primary w-full justify-center">
                        Open trade record
                    </a>
                </div>
            </aside>
        </section>
    @endif

    <section class="ui-panel mt-4 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-4 py-3">
            <div>
                <p class="ui-kicker">Position lifecycle</p>
                <h2 class="text-sm font-semibold">Recent trades</h2>
                <p class="mt-1 text-[9px] text-muted-foreground">Entry, exit/current price and profit/loss at a glance.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.trading.positions') }}" class="ui-btn ui-btn-secondary !h-8 !px-3">Positions</a>
                <a href="{{ route('admin.trading.history') }}" class="ui-btn ui-btn-secondary !h-8 !px-3">Full history</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-xs">
                <thead class="border-b border-border bg-muted/20 text-[9px] uppercase tracking-[.11em] text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3">Trade</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Entry</th>
                        <th class="px-4 py-3">Exit / CMP</th>
                        <th class="px-4 py-3">P/L</th>
                        <th class="px-4 py-3">Return</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Record</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($recentPositions as $position)
                        @php
                            $isOpen = $position->is_open;
                            $exit = $isOpen
                                ? (float)($position->stock?->current_price ?? 0)
                                : (float)($position->average_exit_price ?: $position->lastExitTransaction?->price_per_share ?: 0);
                            $pnl = $isOpen ? (float)$position->current_profit_loss : (float)$position->realized_profit_loss;
                            $ret = $isOpen ? (float)$position->current_return_percent : (float)$position->realized_return_percent;
                        @endphp
                        <tr class="hover:bg-muted/20">
                            <td class="px-4 py-3">
                                <p class="font-semibold">{{ $position->stock?->symbol ?? '—' }} · #{{ $position->id }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $position->user?->name ?? '—' }}</td>
                            <td class="px-4 py-3 font-medium tabular-nums">{{ currency_symbol() }}{{ number_format((float)$position->entry_price,2) }}</td>
                            <td class="px-4 py-3 font-medium tabular-nums">{{ currency_symbol() }}{{ number_format($exit,2) }}</td>
                            <td class="px-4 py-3 font-semibold tabular-nums {{ $pnl >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $pnl >= 0 ? '+' : '-' }}{{ currency_symbol() }}{{ number_format(abs($pnl),2) }}
                            </td>
                            <td class="px-4 py-3 font-medium tabular-nums {{ $ret >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $ret >= 0 ? '+' : '' }}{{ number_format($ret,2) }}%
                            </td>
                            <td class="px-4 py-3">{{ $isOpen ? 'Open' : ucfirst(str_replace('_',' ',$position->status)) }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.trading.positions.show',$position) }}" class="ui-btn ui-btn-secondary !h-8 !px-3">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center text-muted-foreground">No trade positions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="ui-panel mt-4 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="ui-kicker">Trading capabilities</p>
                <h2 class="text-sm font-semibold">Command shortcuts</h2>
            </div>
            <p class="text-[9px] text-muted-foreground">{{ number_format($transactions) }} raw stock executions recorded.</p>
        </div>

        <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
            @foreach([
                [route('admin.trading.manual'),'mouse-pointer-click','Strategy / Admin / User trade'],
                [route('admin.stocks.holdings.index'),'briefcase-business','Inspect holdings'],
                [route('admin.stocks.transactions.index'),'receipt-text','Execution ledger'],
                [route('admin.copy-trading.strategies'),'workflow','Copy strategies'],
                [route('admin.ai-bots.executions'),'bot','Bot executions'],
            ] as [$href,$icon,$label])
                <a href="{{ $href }}" class="flex items-center justify-between rounded-xl border border-border p-3 hover:bg-muted/50">
                    <span class="flex items-center gap-2.5 text-[10px] font-medium"><i data-lucide="{{ $icon }}" class="h-4 w-4"></i>{{ $label }}</span>
                    <i data-lucide="arrow-right" class="h-4 w-4 text-muted-foreground"></i>
                </a>
            @endforeach
        </div>
    </section>
</div>
</x-admin-layout>
