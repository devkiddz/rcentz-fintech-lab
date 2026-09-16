<x-admin-layout>
<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div><p class="ui-kicker">Investments · Control Center</p><h1 class="ui-heading">Private Investment Control</h1><p class="ui-lead max-w-3xl">Manage instruments, valuation events, underlying assets, customer-facing presentation and market state from one control surface.</p></div>
        <div class="flex gap-2"><a href="{{ route('investments.index') }}" class="ui-btn ui-btn-secondary">View Customer Market</a></div>
    </section>

    @if(session('success'))<div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-xs text-emerald-600">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-xs text-red-600">{{ $errors->first() }}</div>@endif

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        @foreach([['Instruments',$stats['instruments']],['Active',$stats['active']],['Assets',$stats['assets']],['Holdings',$stats['holdings']],['Transactions',$stats['transactions']],['Underlying',currency_symbol().number_format($stats['underlying_valuation'],0)]] as [$label,$value])
            <div class="ui-panel p-4"><p class="ui-kicker">{{ $label }}</p><p class="mt-2 text-xl font-semibold">{{ $value }}</p></div>
        @endforeach
    </section>

    <section class="mt-4 grid gap-4 xl:grid-cols-[.9fr_1.1fr]">
        <div class="ui-panel p-5">
            <p class="ui-kicker">Instrument Registry</p><h2 class="mt-1 text-lg font-semibold">Create Investment</h2>
            <form method="POST" action="{{ route('admin.investments.control.instruments.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">@csrf
                <input class="ui-input" name="name" placeholder="Instrument name" required>
                <input class="ui-input" name="symbol" placeholder="Symbol" required>
                <select class="ui-input" name="category" required><option value="stock_market">Stocks</option><option value="cryptocurrency">Cryptocurrency</option><option value="real_estate">Real Estate</option><option value="bonds">Bonds & Fixed Income</option></select>
                <select class="ui-input" name="risk_level"><option>low</option><option selected>medium</option><option>high</option><option>very_high</option></select>
                <input class="ui-input" type="number" step="0.000001" min="0.000001" name="opening_price" placeholder="Opening price" required>
                <input class="ui-input" type="number" step="0.000001" min="0" name="unit_supply" placeholder="Unit supply" required>
                <input class="ui-input" type="number" step="0.01" min="0" name="minimum_investment" placeholder="Minimum investment" required>
                <input class="ui-input" type="number" step="0.01" min="0" name="maximum_investment" placeholder="Maximum investment">
                <input class="ui-input" type="number" step="0.0001" min="0" name="management_fee_percent" value="1.0000" required>
                <input class="ui-input" type="number" min="0" name="lock_period_days" value="0" required>
                <textarea class="ui-input sm:col-span-2" name="description" rows="3" placeholder="Description"></textarea>
                <label class="text-xs"><input type="checkbox" name="is_featured" value="1"> Featured</label>
                <label class="text-xs"><input type="checkbox" name="is_visible" value="1" checked> Visible</label>
                <button class="ui-btn ui-btn-primary justify-center sm:col-span-2">Create Instrument</button>
            </form>
        </div>

        <div class="ui-panel p-5">
            <p class="ui-kicker">Market Presentation</p><h2 class="mt-1 text-lg font-semibold">Pricing Authority Message</h2>
            <form method="POST" action="{{ route('admin.investments.control.presentation.update') }}" class="mt-4 space-y-3">@csrf @method('PATCH')
                <input class="ui-input w-full" name="title" value="{{ $presentation['title'] }}" required>
                <textarea class="ui-input w-full" rows="5" name="message" required>{{ $presentation['message'] }}</textarea>
                <button class="ui-btn ui-btn-primary">Save Market Copy</button>
            </form>
        </div>
    </section>

    <section class="ui-panel mt-4 overflow-hidden">
        <div class="border-b border-border px-4 py-3"><p class="ui-kicker">Instrument registry</p><p class="mt-1 text-xs text-muted-foreground">{{ $instruments->total() }} private investment instruments</p></div>
        <div class="overflow-x-auto"><table class="w-full min-w-[1000px] text-left">
            <thead class="border-b border-border bg-muted/20"><tr class="text-[9px] uppercase tracking-[.1em] text-muted-foreground"><th class="px-4 py-3">Instrument</th><th>Class</th><th>Price</th><th>Assets</th><th>Events</th><th>Status</th><th></th></tr></thead>
            <tbody class="divide-y divide-border">
            @foreach($instruments as $instrument)<tr>
                <td class="px-4 py-3"><p class="text-xs font-semibold">{{ $instrument->name }}</p><p class="text-[9px] text-muted-foreground">{{ $instrument->symbol }}</p></td>
                <td class="text-xs">{{ ucwords(str_replace('_',' ',$instrument->category)) }}</td>
                <td class="text-xs font-semibold">{{ currency_symbol() }}{{ number_format((float)$instrument->current_price,2) }}</td>
                <td class="text-xs">{{ $instrument->assets_count }}</td><td class="text-xs">{{ $instrument->events_count }}</td><td class="text-xs">{{ ucfirst($instrument->status) }}</td>
                <td class="px-4 py-3 text-right"><a class="ui-btn ui-btn-secondary !h-8" href="{{ route('admin.investments.control.show',$instrument) }}">Manage</a></td>
            </tr>@endforeach
            </tbody>
        </table></div>
        <div class="p-4">{{ $instruments->links() }}</div>
    </section>
</div>
</x-admin-layout>