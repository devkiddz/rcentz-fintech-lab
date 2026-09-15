<x-admin-layout>
<x-slot name="header">Admin Trading Desk</x-slot>

<div class="ui-page max-w-[1680px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker text-[10px]">Trading Management · Execution Desk</p>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="ui-heading !text-2xl">{{ $stock->symbol }}</h1>
                <span class="rounded-full border border-border bg-muted px-2 py-0.5 text-[8px] font-semibold uppercase tracking-[.11em] text-muted-foreground">{{ $stock->name }}</span>
                <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 text-[8px] font-semibold uppercase tracking-[.11em] text-emerald-600">Live desk</span>
            </div>
            <p class="ui-lead !mt-2 !max-w-3xl !text-[13px]">
                Market analysis and execution controls share one workstation. Every trade routes through StockTradeExecutor.
            </p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('admin.stocks.show',$stock) }}" class="ui-btn ui-btn-secondary">
                <i data-lucide="arrow-left" class="h-4 w-4"></i> Stock
            </a>
            <a href="{{ route('admin.stocks.transactions.index') }}?stock={{ $stock->id }}" class="ui-btn ui-btn-secondary">
                <i data-lucide="receipt-text" class="h-4 w-4"></i> Ledger
            </a>
        </div>
    </section>

    @if($errors->any())
        <section class="mt-4 rounded-xl border border-red-500/20 bg-red-500/5 p-4">
            <p class="text-[11px] font-semibold text-red-600">Execution blocked</p>
            @foreach($errors->all() as $error)
                <p class="mt-1 text-[10px] text-red-600/90">{{ $error }}</p>
            @endforeach
        </section>
    @endif

    <section class="mt-5 grid gap-5 2xl:grid-cols-[minmax(0,1.9fr)_minmax(360px,.75fr)]">
        <div class="min-w-0">
            @include('trading.partials.analysis-chart', [
                'stock'=>$stock,
                'analysis'=>$analysis,
                'chartHeight'=>'h-[420px] lg:h-[520px]',
            ])

            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <div class="ui-panel p-4">
                    <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Market price</p>
                    <p class="mt-2 text-lg font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($stock->current_price,2) }}</p>
                </div>
                <div class="ui-panel p-4">
                    <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Admin available</p>
                    <p class="mt-2 text-lg font-semibold tabular-nums">{{ $adminWallet->formatted_available_balance }}</p>
                </div>
                <div class="ui-panel p-4">
                    <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Admin holding</p>
                    <p class="mt-2 text-lg font-semibold tabular-nums">{{ number_format((float)($adminHolding?->quantity ?? 0),6) }}</p>
                </div>
            </div>
        </div>

        <aside>
            <section class="ui-panel overflow-hidden" x-data="{ tab: 'strategy' }">
                <div class="border-b border-border/70 p-2">
                    <div class="grid grid-cols-3 gap-1 rounded-xl bg-muted/30 p-1">
                        <button
                            type="button"
                            @click="tab = 'strategy'"
                            :class="tab === 'strategy' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                            class="rounded-lg px-2 py-2 text-[10px] font-semibold transition"
                        >
                            Strategy
                        </button>
                        <button
                            type="button"
                            @click="tab = 'admin'"
                            :class="tab === 'admin' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                            class="rounded-lg px-2 py-2 text-[10px] font-semibold transition"
                        >
                            Admin
                        </button>
                        <button
                            type="button"
                            @click="tab = 'user'"
                            :class="tab === 'user' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                            class="rounded-lg px-2 py-2 text-[10px] font-semibold transition"
                        >
                            User
                        </button>
                    </div>
                </div>

                <div x-show="tab === 'strategy'" x-cloak>
                    <div class="border-b border-border/70 px-4 py-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="git-branch" class="h-4 w-4 text-violet-500"></i>
                            <h2 class="text-[12px] font-semibold">Strategy trade</h2>
                        </div>
                        <p class="mt-1 text-[9px] text-muted-foreground">
                            Provider-owned execution. Eligible followers of the exact strategy may mirror it.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.stocks.trade.execute',$stock) }}" class="space-y-3 p-4">
                        @csrf

                        <select name="strategy_id" class="ui-input w-full" required>
                            <option value="">Select strategy</option>
                            @foreach($strategies as $strategy)
                                @php $provider=$strategy->profile?->user; @endphp
                                <option value="{{ $strategy->id }}">
                                    {{ $strategy->name }} · {{ $provider?->name ?? 'No provider' }}
                                </option>
                            @endforeach
                        </select>

                        <div class="grid grid-cols-2 gap-2">
                            <select name="side" class="ui-input" required>
                                <option value="buy">Buy</option>
                                <option value="sell">Sell</option>
                            </select>

                            <input
                                name="quantity"
                                type="number"
                                step="0.000001"
                                min="0.000001"
                                class="ui-input"
                                placeholder="Quantity"
                                required
                            >
                        </div>


                        <div class="grid grid-cols-3 gap-2">
                            <input name="stop_loss_percent" type="number" min="0.01" max="100" step="0.01" class="ui-input" placeholder="SL %">
                            <input name="take_profit_percent" type="number" min="0.01" max="100" step="0.01" class="ui-input" placeholder="TP %">
                            <input name="duration_minutes" type="number" min="1" max="43200" class="ui-input" placeholder="Minutes">
                        </div>
                        <button class="ui-btn ui-btn-primary w-full">
                            Execute strategy trade
                        </button>
                    </form>
                </div>

                <div x-show="tab === 'admin'" x-cloak>
                    <div class="border-b border-border/70 px-4 py-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="shield" class="h-4 w-4 text-sky-500"></i>
                            <h2 class="text-[12px] font-semibold">Direct admin trade</h2>
                        </div>
                        <p class="mt-1 text-[9px] text-muted-foreground">
                            Uses the administrator's own wallet and holding. Never mirrors to customers.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.stocks.trade.direct',$stock) }}" class="space-y-3 p-4">
                        @csrf

                        <div class="grid grid-cols-2 gap-2">
                            <select name="side" class="ui-input" required>
                                <option value="buy">Buy</option>
                                <option value="sell">Sell</option>
                            </select>

                            <input
                                name="quantity"
                                type="number"
                                step="0.000001"
                                min="0.000001"
                                class="ui-input"
                                placeholder="Quantity"
                                required
                            >
                        </div>

                        <div class="rounded-xl border border-border bg-muted/10 p-3">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-[9px] text-muted-foreground">Available</span>
                                <span class="text-[10px] font-semibold tabular-nums">{{ $adminWallet->formatted_available_balance }}</span>
                            </div>
                            <div class="mt-2 flex items-center justify-between gap-3">
                                <span class="text-[9px] text-muted-foreground">{{ $stock->symbol }} holding</span>
                                <span class="text-[10px] font-semibold tabular-nums">{{ number_format((float)($adminHolding?->quantity ?? 0),6) }}</span>
                            </div>
                        </div>


                        <div class="grid grid-cols-3 gap-2">
                            <input name="stop_loss_percent" type="number" min="0.01" max="100" step="0.01" class="ui-input" placeholder="SL %">
                            <input name="take_profit_percent" type="number" min="0.01" max="100" step="0.01" class="ui-input" placeholder="TP %">
                            <input name="duration_minutes" type="number" min="1" max="43200" class="ui-input" placeholder="Minutes">
                        </div>
                        <button class="ui-btn ui-btn-primary w-full">
                            Execute admin trade
                        </button>
                    </form>
                </div>

                <div x-show="tab === 'user'" x-cloak>
                    <div class="border-b border-border/70 px-4 py-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="user-round-cog" class="h-4 w-4 text-amber-500"></i>
                            <h2 class="text-[12px] font-semibold">Trade for user</h2>
                        </div>
                        <p class="mt-1 text-[9px] text-muted-foreground">
                            Executes against the selected customer's real wallet and holdings with admin attribution.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.stocks.trade.user',$stock) }}" class="space-y-3 p-4">
                        @csrf

                        <select name="user_id" class="ui-input w-full" required>
                            <option value="">Select KYC customer</option>
                            @foreach($customers as $customer)
                                @php $holding=$customer->stockHoldings?->first(); @endphp
                                <option value="{{ $customer->id }}">
                                    {{ $customer->name }}
                                    · {{ $customer->wallet?->formatted_available_balance ?? '$0.00' }}
                                    · {{ number_format((float)($holding?->quantity ?? 0),4) }} {{ $stock->symbol }}
                                </option>
                            @endforeach
                        </select>

                        <div class="grid grid-cols-2 gap-2">
                            <select name="side" class="ui-input" required>
                                <option value="buy">Buy</option>
                                <option value="sell">Sell</option>
                            </select>

                            <input
                                name="quantity"
                                type="number"
                                step="0.000001"
                                min="0.000001"
                                class="ui-input"
                                placeholder="Quantity"
                                required
                            >
                        </div>

                        <textarea
                            name="reason"
                            class="ui-input min-h-20 w-full"
                            placeholder="Administrative reason for trading on this account"
                            required
                        ></textarea>


                        <div class="grid grid-cols-3 gap-2">
                            <input name="stop_loss_percent" type="number" min="0.01" max="100" step="0.01" class="ui-input" placeholder="SL %">
                            <input name="take_profit_percent" type="number" min="0.01" max="100" step="0.01" class="ui-input" placeholder="TP %">
                            <input name="duration_minutes" type="number" min="1" max="43200" class="ui-input" placeholder="Minutes">
                        </div>
                        <button class="ui-btn ui-btn-primary w-full">
                            Execute for user
                        </button>
                    </form>
                </div>

                <div class="border-t border-border/70 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[9px] font-semibold">Account operations</p>
                            <p class="mt-0.5 text-[8px] text-muted-foreground">Credits, debits, profit entries and historical events.</p>
                        </div>

                        <a href="{{ route('admin.users.index') }}" class="ui-btn ui-btn-secondary !h-7 !px-2.5 text-[9px]">
                            Open users
                        </a>
                    </div>
                </div>
            </section>
        </aside>
    </section>
</div>
</x-admin-layout>
