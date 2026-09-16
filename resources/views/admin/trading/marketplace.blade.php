<x-admin-layout>
<div class="ui-page max-w-[1500px]" data-market-runtime>
    <section class="ui-page-header">
        <div>
            <p class="ui-kicker">Admin · Trading Command</p>
            <h1 class="ui-heading">Market Operations</h1>
            <p class="ui-lead max-w-3xl">Instrument registry and direct operational actions. Price-source and movement configuration now live in Admin Settings.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.settings.index', ['section' => 'market']) }}" class="ui-btn ui-btn-primary">Market Settings</a>
            <a href="{{ route('admin.trading.index') }}" class="ui-btn ui-btn-secondary">Trading Overview</a>
            <a href="{{ route('admin.trading.manual') }}" class="ui-btn ui-btn-secondary">Trading Desk</a>
        </div>
    </section>

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-xs text-red-600">{{ $errors->first() }}</div>
    @endif
    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-xs text-emerald-600">{{ session('success') }}</div>
    @endif

    <section class="grid gap-4 xl:grid-cols-[.72fr_1.28fr]">
        <div class="ui-panel p-5">
            <p class="ui-kicker">Operational action</p>
            <h2 class="mt-1 text-lg font-semibold">Manual market tick</h2>
            <p class="mt-1 text-xs leading-5 text-muted-foreground">Automatic movement is configured in Settings. Use this only when you deliberately want one immediate engine step.</p>
            <div class="mt-4 rounded-xl border border-border bg-muted/15 p-3 text-[10px] text-muted-foreground">
                Automatic movement interval: <strong class="text-foreground">{{ (int)$environment->controlled_tick_seconds }} seconds</strong>
            </div>
            <form method="POST" action="{{ route('admin.trading.marketplace.tick') }}" class="mt-4">
                @csrf
                <button class="ui-btn ui-btn-primary w-full justify-center"><i data-lucide="step-forward" class="h-4 w-4"></i> Run one tick now</button>
            </form>
        </div>

        <div class="ui-panel p-5">
            <p class="ui-kicker">Instrument registry</p>
            <h2 class="mt-1 text-lg font-semibold">Add instrument</h2>
            <p class="mt-1 text-xs text-muted-foreground">Enter identity and starting price. Precision, minimum tick, minimum price, volatility and neutral bias are derived automatically.</p>

            <form method="POST" action="{{ route('admin.trading.marketplace.instruments.store') }}" class="mt-5 grid gap-3 sm:grid-cols-3">
                @csrf
                <div><label class="text-[9px] uppercase tracking-[.1em] text-muted-foreground">Symbol</label><input class="ui-input mt-2 w-full" name="symbol" value="{{ old('symbol') }}" placeholder="AAPL" required></div>
                <div><label class="text-[9px] uppercase tracking-[.1em] text-muted-foreground">Label</label><input class="ui-input mt-2 w-full" name="label" value="{{ old('label') }}" placeholder="Apple Inc." required></div>
                <div><label class="text-[9px] uppercase tracking-[.1em] text-muted-foreground">Starting price</label><input class="ui-input mt-2 w-full" type="number" step="0.000001" min="0.000001" name="current_price" value="{{ old('current_price') }}" placeholder="248.50" required></div>
                <button class="ui-btn ui-btn-primary justify-center sm:col-span-3"><i data-lucide="plus" class="h-4 w-4"></i> Add instrument</button>
            </form>
        </div>
    </section>

    <section class="ui-panel mt-4 overflow-hidden">
        <div class="border-b border-border px-4 py-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div><p class="ui-kicker">Instrument registry</p><p class="mt-1 text-xs text-muted-foreground">{{ $instruments->total() }} registered instruments</p></div>
                <a href="{{ route('admin.settings.index', ['section' => 'market']) }}" class="text-[10px] font-medium text-muted-foreground hover:text-foreground">Configure automatic movement →</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1080px] text-left">
                <thead class="border-b border-border bg-muted/20">
                    <tr class="text-[9px] uppercase tracking-[.1em] text-muted-foreground">
                        <th class="px-4 py-3">Instrument</th><th class="px-4 py-3">Price</th><th class="px-4 py-3">Precision</th><th class="px-4 py-3">Volatility</th><th class="px-4 py-3">Bias</th><th class="px-4 py-3">State</th><th class="px-4 py-3">Price reset</th><th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($instruments as $instrument)
                        <tr>
                            <td class="px-4 py-3"><p class="text-xs font-semibold">{{ $instrument->symbol }}</p><p class="text-[9px] text-muted-foreground">{{ $instrument->label }} · {{ ucfirst($instrument->asset_class) }}</p></td>
                            <td class="px-4 py-3 text-xs font-semibold tabular-nums"><span data-market-price-symbol="{{ $instrument->symbol }}" data-marketplace="controlled">{{ currency_symbol() }}{{ number_format((float)$instrument->current_price, $instrument->decimal_precision) }}</span></td>
                            <td class="px-4 py-3 text-[10px]">{{ $instrument->decimal_precision }} dp · tick {{ number_format((float)$instrument->minimum_tick, $instrument->decimal_precision) }}</td>
                            <td class="px-4 py-3 text-[10px]">{{ number_format((float)$instrument->volatility_percent, 3) }}%</td>
                            <td class="px-4 py-3 text-[10px]">{{ (float)$instrument->individual_bias === 0.0 ? 'Neutral' : number_format((float)$instrument->individual_bias,2) }}</td>
                            <td class="px-4 py-3 text-[10px]">{{ $instrument->is_active ? 'Active' : 'Paused' }}</td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('admin.trading.marketplace.instruments.price',$instrument) }}" class="flex min-w-[170px] gap-2">@csrf @method('PATCH')<input class="ui-input !h-8 min-w-0" type="number" step="0.000001" min="0.000001" name="current_price" value="{{ $instrument->current_price }}"><button class="ui-btn ui-btn-secondary !h-8 !px-2">Set</button></form>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('admin.trading.marketplace.instruments.toggle',$instrument) }}">@csrf @method('PATCH')<button class="ui-btn ui-btn-secondary !h-8 !px-3">{{ $instrument->is_active ? 'Pause' : 'Activate' }}</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center text-xs text-muted-foreground">No instruments are registered.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-4">{{ $instruments->links() }}</div>
</div>
</x-admin-layout>
