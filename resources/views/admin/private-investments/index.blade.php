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
            <form method="POST" action="{{ route('admin.investments.control.instruments.store') }}" class="mt-4 space-y-5">@csrf
                <div>
                    <p class="text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">Identity & Classification</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div><label class="ui-label">Instrument Name</label><input class="ui-input w-full" name="name" placeholder="e.g. Lekki Income Property Fund" required></div>
                        <div><label class="ui-label">Symbol</label><input class="ui-input w-full" name="symbol" placeholder="e.g. LIPF" required></div>
                        <div><label class="ui-label">Asset Class</label><select class="ui-input w-full" name="category" required><option value="stock_market">Stocks</option><option value="cryptocurrency">Cryptocurrency</option><option value="real_estate">Real Estate</option><option value="bonds">Bonds & Fixed Income</option></select></div>
                        <div><label class="ui-label">Risk Level</label><select class="ui-input w-full" name="risk_level" required><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="very_high">Very High</option></select></div>
                    </div>
                </div>

                <div class="border-t border-border pt-5">
                    <p class="text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">Pricing & Supply</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div><label class="ui-label">Opening / Listing Price</label><input class="ui-input w-full" type="number" step="0.000001" min="0.000001" name="opening_price" placeholder="Price per unit" required><p class="mt-1 text-[9px] text-muted-foreground">Authoritative opening price per investment unit.</p></div>
                        <div><label class="ui-label">Total Unit Supply</label><input class="ui-input w-full" type="number" step="0.000001" min="0" name="unit_supply" placeholder="Total units available" required></div>
                        <div><label class="ui-label">Minimum Investment</label><input class="ui-input w-full" type="number" step="0.01" min="0" name="minimum_investment" placeholder="Minimum subscription amount" required></div>
                        <div><label class="ui-label">Maximum Investment</label><input class="ui-input w-full" type="number" step="0.01" min="0" name="maximum_investment" placeholder="Optional maximum subscription"></div>
                    </div>
                </div>

                <div class="border-t border-border pt-5">
                    <p class="text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">Duration & Return Model</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div><label class="ui-label">Investment Duration (Days)</label><input class="ui-input w-full" type="number" min="1" max="3650" name="duration_days" value="90" required><p class="mt-1 text-[9px] text-muted-foreground">Full lifespan of the investment product.</p></div>
                        <div><label class="ui-label">Return Cycle (Days)</label><input class="ui-input w-full" type="number" min="1" max="3650" name="return_interval_days" value="30" required><p class="mt-1 text-[9px] text-muted-foreground">How often the configured return range is evaluated.</p></div>
                        <div><label class="ui-label">Projected Minimum Return / Cycle (%)</label><input class="ui-input w-full" type="number" step="0.0001" min="0" max="100" name="projected_return_min_percent" value="0.5000" required></div>
                        <div><label class="ui-label">Projected Maximum Return / Cycle (%)</label><input class="ui-input w-full" type="number" step="0.0001" min="0" max="100" name="projected_return_max_percent" value="1.0000" required></div>
                        <div><label class="ui-label">Lock Period (Days)</label><input class="ui-input w-full" type="number" min="0" max="3650" name="lock_period_days" value="0" required><p class="mt-1 text-[9px] text-muted-foreground">Redemption is restricted until this period expires.</p></div>
                        <div class="rounded-xl border border-border bg-muted/20 p-3 text-[10px] leading-4 text-muted-foreground">Return percentages are configured per cycle, not automatically treated as daily returns.</div>
                    </div>
                </div>

                <div class="border-t border-border pt-5">
                    <p class="text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">Fees</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        <div><label class="ui-label">Subscription Fee (%)</label><input class="ui-input w-full" type="number" step="0.0001" min="0" max="100" name="subscription_fee_percent" value="0.5000" required></div>
                        <div><label class="ui-label">Management Fee (%)</label><input class="ui-input w-full" type="number" step="0.0001" min="0" max="100" name="management_fee_percent" value="1.0000" required></div>
                        <div><label class="ui-label">Redemption Fee (%)</label><input class="ui-input w-full" type="number" step="0.0001" min="0" max="100" name="redemption_fee_percent" value="0.5000" required></div>
                    </div>
                </div>

                <div class="border-t border-border pt-5">
                    <label class="ui-label">Investment Description</label>
                    <textarea class="ui-input mt-1 w-full" name="description" rows="4" placeholder="Explain the investment thesis, backing assets and intended value proposition."></textarea>
                </div>

                <div class="flex flex-wrap gap-5 border-t border-border pt-5">
                    <label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="is_featured" value="1"><span>Feature on marketplace</span></label>
                    <label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="is_visible" value="1" checked><span>Visible to customers</span></label>
                </div>

                <button class="ui-btn ui-btn-primary w-full justify-center">Create Investment</button>
            </form>
        </div>

        <div class="ui-panel p-5">
            <p class="ui-kicker">Market Presentation</p><h2 class="mt-1 text-lg font-semibold">Pricing Authority Message</h2>
            <form method="POST" action="{{ route('admin.investments.control.presentation.update') }}" class="mt-4 space-y-3">@csrf @method('PATCH')
                <div><label class="ui-label">Pricing Authority Title</label><input class="ui-input mt-1 w-full" name="title" value="{{ $presentation['title'] }}" required></div>
                <div><label class="ui-label">Customer-Facing Pricing Message</label><textarea class="ui-input mt-1 w-full" rows="5" name="message" required>{{ $presentation['message'] }}</textarea></div>
                <button class="ui-btn ui-btn-primary">Save Market Copy</button>
            </form>
        </div>
    </section>

    <section class="ui-panel mt-4 overflow-hidden">
        <div class="border-b border-border px-4 py-3"><p class="ui-kicker">Instrument registry</p><p class="mt-1 text-xs text-muted-foreground">{{ $instruments->total() }} private investment instruments</p></div>
        <div class="overflow-x-auto"><table class="w-full min-w-[1000px] text-left">
            <thead class="border-b border-border bg-muted/20"><tr class="text-[9px] uppercase tracking-[.1em] text-muted-foreground"><th class="px-4 py-3">Instrument</th><th>Class</th><th>Price</th><th>Duration</th><th>Return Cycle</th><th>Assets</th><th>Status</th><th></th></tr></thead>
            <tbody class="divide-y divide-border">
            @foreach($instruments as $instrument)<tr>
                <td class="px-4 py-3"><p class="text-xs font-semibold">{{ $instrument->name }}</p><p class="text-[9px] text-muted-foreground">{{ $instrument->symbol }}</p></td>
                <td class="text-xs">{{ ucwords(str_replace('_',' ',$instrument->category)) }}</td>
                <td class="text-xs font-semibold">{{ currency_symbol() }}{{ number_format((float)$instrument->current_price,2) }}</td>
                <td class="text-xs">{{ $instrument->duration_days }} days</td>
                <td class="text-xs"><span class="font-semibold">{{ number_format((float)$instrument->projected_return_min_percent,2) }}%–{{ number_format((float)$instrument->projected_return_max_percent,2) }}%</span><p class="mt-0.5 text-[9px] text-muted-foreground">every {{ $instrument->return_interval_days }} days</p></td>
                <td class="text-xs">{{ $instrument->assets_count }}</td><td class="text-xs">{{ ucfirst($instrument->status) }}</td>
                <td class="px-4 py-3 text-right"><a class="ui-btn ui-btn-secondary !h-8" href="{{ route('admin.investments.control.show',$instrument) }}">Manage</a></td>
            </tr>@endforeach
            </tbody>
        </table></div>
        <div class="p-4">{{ $instruments->links() }}</div>
    </section>
</div>
</x-admin-layout>