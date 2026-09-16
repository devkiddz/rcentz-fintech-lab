<x-admin-layout>
<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Admin · Trading Command</p>
            <h1 class="ui-heading">Marketplace Control</h1>
            <p class="ui-lead max-w-3xl">One trading contract engine, two independent price environments. Live keeps the external feed; Controlled uses its own instruments, history and market driver.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.trading.index') }}" class="ui-btn ui-btn-secondary">Trading Overview</a>
            <a href="{{ route('admin.trading.manual') }}" class="ui-btn ui-btn-secondary">Trading Desk</a>
        </div>
    </section>

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-xs text-red-600">
            {{ $errors->first() }}
        </div>
    @endif

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-xs text-emerald-600">
            {{ session('success') }}
        </div>
    @endif

    <section class="grid gap-4 xl:grid-cols-2">
        <div class="ui-panel p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kicker">Price authority</p>
                    <h2 class="mt-1 text-lg font-semibold">Active marketplace</h2>
                    <p class="mt-1 text-xs text-muted-foreground">The selected side owns displayed market prices and all new execution prices.</p>
                </div>
                <span class="rounded-full border border-border bg-muted px-3 py-1 text-[10px] font-semibold uppercase tracking-[.1em]">
                    {{ $environment->active_marketplace }}
                </span>
            </div>

            <form method="POST" action="{{ route('admin.trading.marketplace.mode') }}" class="mt-5 grid gap-3 sm:grid-cols-2">
                @csrf
                <button name="active_marketplace" value="live" class="rounded-xl border p-4 text-left transition {{ $environment->active_marketplace === 'live' ? 'border-emerald-500 bg-emerald-500/10' : 'border-border hover:bg-muted/30' }}">
                    <div class="flex items-center gap-2 text-sm font-semibold"><i data-lucide="radio-tower" class="h-4 w-4"></i> Live Market</div>
                    <p class="mt-2 text-[10px] text-muted-foreground">Persisted Finnhub/API quotes. Regular U.S. market execution rules remain active.</p>
                </button>
                <button name="active_marketplace" value="controlled" class="rounded-xl border p-4 text-left transition {{ $environment->active_marketplace === 'controlled' ? 'border-violet-500 bg-violet-500/10' : 'border-border hover:bg-muted/30' }}">
                    <div class="flex items-center gap-2 text-sm font-semibold"><i data-lucide="sliders-horizontal" class="h-4 w-4"></i> Controlled Market</div>
                    <p class="mt-2 text-[10px] text-muted-foreground">Independent simulated prices. Execution, P/L and risk controls consume the controlled price authority.</p>
                </button>
            </form>

            <div class="mt-4 rounded-xl border border-border bg-muted/20 p-3 text-[10px] text-muted-foreground">
                Marketplace isolation is active. Switching changes the active desk and new executions only; existing holdings and contracts stay bound to their origin market.<br>
                Live: {{ $exposure['live_positions'] }} open positions / {{ $exposure['live_holdings'] }} holdings · Controlled: {{ $exposure['controlled_positions'] }} open positions / {{ $exposure['controlled_holdings'] }} holdings.
            </div>
        </div>

        <div class="ui-panel p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ui-kicker">Live feed health</p>
                    <h2 class="mt-1 text-lg font-semibold">External market feed</h2>
                </div>
                <span class="rounded-full border px-3 py-1 text-[10px] font-semibold {{ $liveHealth['healthy'] ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600' : 'border-amber-500/20 bg-amber-500/10 text-amber-600' }}">
                    {{ $liveHealth['healthy'] ? 'Detected' : 'Needs data' }}
                </span>
            </div>
            <div class="mt-5 grid grid-cols-2 gap-px overflow-hidden rounded-xl bg-border sm:grid-cols-4">
                @foreach([
                    ['Finnhub', $liveHealth['finnhub_available'] ? 'Configured' : 'Unavailable'],
                    ['Yahoo', $liveHealth['yahoo_available'] ? 'Available' : 'Unavailable'],
                    ['Fresh ≤15m', $liveHealth['fresh_stocks'].' / '.$liveHealth['active_stocks']],
                    ['Latest quote', $liveHealth['latest_quote_symbol'] ?: 'None'],
                ] as [$label,$value])
                    <div class="bg-background p-3">
                        <p class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">{{ $label }}</p>
                        <p class="mt-1 text-[11px] font-semibold">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
            <p class="mt-3 text-[10px] text-muted-foreground">
                @if($liveHealth['latest_quote_at']) Latest persisted quote: {{ $liveHealth['latest_quote_at']->format('M j, Y g:i A') }}. @else No persisted quote has been detected yet. @endif
            </p>
        </div>
    </section>

    <section class="mt-4 grid gap-4 xl:grid-cols-[.8fr_1.2fr]">
        <div class="ui-panel p-5">
            <p class="ui-kicker">Controlled Market</p>
            <h2 class="mt-1 text-lg font-semibold">Market driver</h2>
            <p class="mt-1 text-xs text-muted-foreground">The driver moves every active controlled instrument with realistic noise instead of forcing every tick in one direction.</p>

            <form method="POST" action="{{ route('admin.trading.marketplace.drive') }}" class="mt-5 space-y-4">
                @csrf
                <div class="grid grid-cols-3 gap-2">
                    @foreach(['up'=>'Drive Up','down'=>'Drive Down','range'=>'Consolidate'] as $value=>$label)
                        <label class="cursor-pointer rounded-xl border p-3 text-center text-[10px] font-semibold {{ $environment->controlled_drive_mode === $value ? 'border-violet-500 bg-violet-500/10' : 'border-border' }}">
                            <input type="radio" class="sr-only" name="controlled_drive_mode" value="{{ $value }}" {{ $environment->controlled_drive_mode === $value ? 'checked' : '' }}>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <div>
                    <label class="text-[9px] uppercase tracking-[.1em] text-muted-foreground">Drive strength</label>
                    <input class="ui-input mt-2 w-full" type="number" step="0.1" min="0.1" max="3" name="controlled_drive_strength" value="{{ (float)$environment->controlled_drive_strength }}">
                </div>
                <button class="ui-btn ui-btn-primary w-full justify-center">Save market drive</button>
            </form>

            <form method="POST" action="{{ route('admin.trading.marketplace.tick') }}" class="mt-2">
                @csrf
                <button class="ui-btn ui-btn-secondary w-full justify-center"><i data-lucide="step-forward" class="h-4 w-4"></i> Run one tick now</button>
            </form>
        </div>

        <div class="ui-panel p-5">
            <p class="ui-kicker">Instrument registry</p>
            <h2 class="mt-1 text-lg font-semibold">Add controlled instrument</h2>
            <p class="mt-1 text-xs text-muted-foreground">Enter only identity and starting price. Precision, minimum tick, minimum price, volatility and neutral bias are derived automatically.</p>

            <form method="POST" action="{{ route('admin.trading.marketplace.instruments.store') }}" class="mt-5 grid gap-3 sm:grid-cols-3">
                @csrf
                <div>
                    <label class="text-[9px] uppercase tracking-[.1em] text-muted-foreground">Instrument symbol</label>
                    <input class="ui-input mt-2 w-full" name="symbol" value="{{ old('symbol') }}" placeholder="AAPL" required>
                </div>
                <div>
                    <label class="text-[9px] uppercase tracking-[.1em] text-muted-foreground">Instrument label</label>
                    <input class="ui-input mt-2 w-full" name="label" value="{{ old('label') }}" placeholder="Apple Inc." required>
                </div>
                <div>
                    <label class="text-[9px] uppercase tracking-[.1em] text-muted-foreground">Current market price</label>
                    <input class="ui-input mt-2 w-full" type="number" step="0.000001" min="0.000001" name="current_price" value="{{ old('current_price') }}" placeholder="248.50" required>
                </div>
                <button class="ui-btn ui-btn-primary justify-center sm:col-span-3"><i data-lucide="plus" class="h-4 w-4"></i> Add to Controlled Market</button>
            </form>
        </div>
    </section>

    <section class="ui-panel mt-4 overflow-hidden">
        <div class="border-b border-border px-4 py-3">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="ui-kicker">Controlled instruments</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ $instruments->total() }} registered instruments</p>
                </div>
                <span class="text-[10px] text-muted-foreground">Automatic tick: every minute while Controlled Market is active</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="border-b border-border bg-muted/20">
                    <tr class="text-[9px] uppercase tracking-[.1em] text-muted-foreground">
                        <th class="px-4 py-3">Instrument</th><th class="px-4 py-3">Price</th><th class="px-4 py-3">Precision</th><th class="px-4 py-3">Volatility</th><th class="px-4 py-3">Bias</th><th class="px-4 py-3">State</th><th class="px-4 py-3">Price reset</th><th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($instruments as $instrument)
                        <tr>
                            <td class="px-4 py-3"><p class="text-xs font-semibold">{{ $instrument->symbol }}</p><p class="text-[9px] text-muted-foreground">{{ $instrument->label }} · {{ ucfirst($instrument->asset_class) }}</p></td>
                            <td class="px-4 py-3 text-xs font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format((float)$instrument->current_price, $instrument->decimal_precision) }}</td>
                            <td class="px-4 py-3 text-[10px]">{{ $instrument->decimal_precision }} dp · tick {{ number_format((float)$instrument->minimum_tick, $instrument->decimal_precision) }}</td>
                            <td class="px-4 py-3 text-[10px]">{{ number_format((float)$instrument->volatility_percent, 3) }}%</td>
                            <td class="px-4 py-3 text-[10px]">{{ (float)$instrument->individual_bias === 0.0 ? 'Neutral' : number_format((float)$instrument->individual_bias,2) }}</td>
                            <td class="px-4 py-3 text-[10px]">{{ $instrument->is_active ? 'Active' : 'Paused' }}</td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('admin.trading.marketplace.instruments.price',$instrument) }}" class="flex min-w-[170px] gap-2">
                                    @csrf @method('PATCH')
                                    <input class="ui-input !h-8 min-w-0" type="number" step="0.000001" min="0.000001" name="current_price" value="{{ $instrument->current_price }}">
                                    <button class="ui-btn ui-btn-secondary !h-8 !px-2">Set</button>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('admin.trading.marketplace.instruments.toggle',$instrument) }}">
                                    @csrf @method('PATCH')
                                    <button class="ui-btn ui-btn-secondary !h-8 !px-3">{{ $instrument->is_active ? 'Pause' : 'Activate' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center text-xs text-muted-foreground">No controlled instruments are registered.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-4">{{ $instruments->links() }}</div>
</div>
</x-admin-layout>
