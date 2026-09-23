<x-admin-layout>
<div class="ui-page max-w-[1500px]">
    <section class="ui-page-header">
        <div><p class="ui-kicker">Investments · Control Center</p><h1 class="ui-heading">Private Investment Control</h1><p class="ui-lead max-w-3xl">Manage instruments, valuation events, underlying assets, customer-facing presentation and market state from one control surface.</p></div>
        <div class="flex gap-2"><a href="{{ route('admin.investments.instruments.base-assets.index') }}" class="ui-btn ui-btn-secondary">Base Reference Engine</a><a href="{{ route('investments.index') }}" class="ui-btn ui-btn-secondary">View Customer Market</a></div>
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
                        <div><label class="ui-label">Asset Class</label><select class="ui-input w-full" name="category" required><option value="stock_market">Stocks</option><option value="forex">Forex</option><option value="cryptocurrency">Cryptocurrency</option><option value="real_estate">Real Estate</option><option value="bonds">Bonds & Fixed Income</option><option value="hedge_assets">Hedge Assets (Gold / Commodities)</option></select></div>
                        <div><label class="ui-label">Risk Level</label><select class="ui-input w-full" name="risk_level" required><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="very_high">Very High</option></select></div>
                    </div>
                </div>

                <div class="border-t border-border pt-5">
                    <p class="text-[9px] font-semibold uppercase tracking-[.12em] text-muted-foreground">Pricing & Reserve Initialization</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div><label class="ui-label">Opening / Listing Price</label><input class="ui-input w-full" type="number" step="0.000001" min="0.000001" name="opening_price" placeholder="Price per unit" required><p class="mt-1 text-[9px] text-muted-foreground">Initial unit price authority. Reserve backing determines how many units may exist.</p></div>
                        <div class="rounded-xl border border-amber-500/20 bg-amber-500/5 p-3 text-[10px] leading-4 text-muted-foreground"><p class="font-semibold text-foreground">Supply begins at zero.</p><p class="mt-1">New instruments are created paused with no sellable units. Add verified reserve assets from the management page, then activate from reserve-backed capacity.</p></div>
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

    <section class="mt-4">
        <div class="ui-panel flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="ui-kicker">Instrument registry</p>
                <h2 class="mt-1 text-lg font-semibold">Investment Products</h2>
                <p class="mt-1 text-xs text-muted-foreground">{{ $instruments->total() }} private investment instruments · card management view</p>
            </div>
            <a href="{{ route('investments.index') }}" class="ui-btn ui-btn-secondary shrink-0">
                <i data-lucide="external-link" class="h-4 w-4"></i>
                Customer Market
            </a>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
            @forelse($instruments as $instrument)
                @php
                    $adminChange = (float) $instrument->change_percent;
                    $adminStory = app(\App\Services\PrivateInvestmentProjectionService::class)
                        ->forInstrument($instrument, (float)$instrument->minimum_investment);
                @endphp
                <article class="ui-panel group flex min-h-[330px] flex-col overflow-hidden p-5 transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-red-500/10 px-2 py-1 text-[9px] font-semibold uppercase tracking-[.08em] text-red-600">{{ $instrument->symbol }}</span>
                                <span class="rounded-full border border-border px-2 py-1 text-[9px] font-medium text-muted-foreground">{{ ucwords(str_replace('_',' ',$instrument->category)) }}</span>
                                <span class="rounded-full border border-border px-2 py-1 text-[9px] font-medium {{ $instrument->status === 'active' ? 'text-emerald-600' : 'text-amber-600' }}">{{ ucfirst($instrument->status) }}</span>
                            </div>
                            <h3 class="mt-3 truncate text-base font-semibold">{{ $instrument->name }}</h3>
                            <p class="mt-1 text-[10px] text-muted-foreground">{{ ucwords(str_replace('_',' ',$instrument->risk_level)) }} risk</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-lg font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format((float)$instrument->current_price,2) }}</p>
                            <p class="mt-1 text-[10px] font-semibold {{ $adminChange >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ $adminChange >= 0 ? '+' : '' }}{{ number_format($adminChange,2) }}%</p>
                        </div>
                    </div>

                    <p class="mt-4 line-clamp-2 min-h-[40px] text-[11px] leading-5 text-muted-foreground">{{ $instrument->description ?: 'No investment description has been published yet.' }}</p>

                    <div class="mt-4 grid grid-cols-2 border-y border-border">
                        <div class="border-b border-r border-border py-3 pr-3">
                            <p class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">Duration</p>
                            <p class="mt-1 text-xs font-semibold">{{ $adminStory['duration_label'] }}</p>
                        </div>
                        <div class="border-b border-border py-3 pl-3">
                            <p class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">Return cycle</p>
                            <p class="mt-1 text-xs font-semibold">{{ $adminStory['cycle_return_label'] }}</p>
                            <p class="mt-0.5 text-[8px] text-muted-foreground">every {{ $adminStory['return_interval_label'] }}</p>
                        </div>
                        <div class="border-r border-border py-3 pr-3">
                            <p class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">Investors</p>
                            <p class="mt-1 text-xs font-semibold">{{ $instrument->active_holdings_count }}</p>
                            <p class="mt-0.5 text-[8px] text-muted-foreground">{{ $instrument->holdings_count }} holding records</p>
                        </div>
                        <div class="py-3 pl-3">
                            <p class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">Reserve assets</p>
                            <p class="mt-1 text-xs font-semibold">{{ $instrument->assets_count }}</p>
                            <p class="mt-0.5 text-[8px] text-muted-foreground">backing components</p>
                        </div>
                    </div>

                    <div class="mt-auto grid grid-cols-3 gap-3 pt-4">
                        <div>
                            <p class="text-[8px] text-muted-foreground">Minimum</p>
                            <p class="mt-1 text-xs font-semibold">{{ currency_symbol() }}{{ number_format((float)$instrument->minimum_investment,0) }}</p>
                        </div>
                        <div>
                            <p class="text-[8px] text-muted-foreground">Available</p>
                            <p class="mt-1 text-xs font-semibold">{{ number_format((float)$instrument->available_units,2) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[8px] text-muted-foreground">Featured</p>
                            <p class="mt-1 text-xs font-semibold">{{ $instrument->is_featured ? 'Yes' : 'No' }}</p>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2 border-t border-border pt-4">
                        <a href="{{ route('admin.investments.control.show',$instrument) }}" class="ui-btn ui-btn-primary !h-9 flex-1 justify-center">
                            <i data-lucide="settings-2" class="h-3.5 w-3.5"></i>
                            Manage Investment
                        </a>
                        <a href="{{ route('admin.investments.control.preview',$instrument) }}" class="ui-btn ui-btn-secondary !h-9 flex-1 justify-center">
                            <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                            Preview Asset
                        </a>
                    </div>
                </article>
            @empty
                <div class="ui-panel col-span-full border-dashed p-10 text-center text-sm text-muted-foreground">No private investment instruments are registered.</div>
            @endforelse
        </div>

        <div class="mt-4 ui-panel p-4">{{ $instruments->links() }}</div>
    </section>
</div>
</x-admin-layout>