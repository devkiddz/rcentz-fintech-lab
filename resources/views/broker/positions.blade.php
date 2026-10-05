<x-user-layout>
<x-slot name="header">Positions</x-slot>
<div data-account-async-feedback role="status" aria-live="polite" hidden class="mx-4 my-3 rounded-xl border border-border p-3 text-sm"></div>
<div data-account-async="broker/positions">
<div class="ui-page max-w-[1450px]">
    <section class="ui-page-header">
        <div><p class="ui-kicker text-[10px]">Broker · Positions</p><h1 class="ui-heading !text-2xl">Managed Positions</h1><p class="ui-lead !text-[13px]">Manage long and short positions across Stocks, Forex and Crypto. Reduce exposure or close an exact position at a validated market quote.</p></div>
        <div class="flex gap-2"><a href="{{ route('broker.portfolio') }}" class="ui-btn ui-btn-secondary">Portfolio</a><a href="{{ route('broker.orders') }}" class="ui-btn ui-btn-secondary">Orders</a><a href="{{ route('instruments.index') }}" class="ui-btn ui-btn-primary">New Trade</a></div>
    </section>
    @if(session('success'))<div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-700 dark:text-emerald-400">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-xs text-red-700 dark:text-red-400">{{ $errors->first() }}</div>@endif
    <div class="space-y-4">
        @forelse($positions as $position)
            @php
                $instrument=$position->marketInstrument ?? $position->stock?->marketInstrument;
                $asset=$instrument?->asset_class ?? 'stock';
                $mark=$position->runtime_mark_price;
                $entry=(float)$position->entry_price;
                $open=(float)$position->open_quantity;
                $valuation=$position->runtime_valuation;
                $pnl=$valuation['pnl'];
                $ret=$valuation['return_percent'];
                $isOpen=$position->is_open;
            @endphp
            <article class="ui-panel overflow-hidden">
                <div class="grid gap-5 p-5 xl:grid-cols-[1fr_auto] xl:items-start">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full border border-border bg-muted/20 px-2 py-1 text-[9px] font-semibold uppercase">{{ strtoupper($asset) }}</span>
                            <span class="rounded-full border border-border px-2 py-1 text-[9px] font-semibold uppercase">{{ ucfirst($position->direction ?: 'long') }}</span>
                            <span class="text-sm font-semibold">{{ \App\Services\BasketDisplay::recordSymbol($instrument, $position->marketplace, $position->created_at) }}</span>
                            <span class="rounded-full border px-2 py-1 text-[9px] font-semibold uppercase {{ $isOpen?'border-emerald-500/25 bg-emerald-500/10 text-emerald-600':'border-border bg-muted text-muted-foreground' }}">{{ strtoupper(str_replace('_',' ',$position->status)) }}</span>
                        </div>
                        <p class="mt-1 text-[10px] text-muted-foreground">Position #{{ $position->id }} · Opened {{ $position->opened_at?->format('M j, Y · H:i') }}</p>
                        <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-7">
                            @foreach([
                                ['Open qty',number_format($open,8)],
                                ['Entry',number_format($entry,8)],
                                ['Mark',$valuation['formatted_cmp']],
                                ['Stop',$position->stop_loss_price?number_format((float)$position->stop_loss_price,8):'—'],
                                ['Target',$position->take_profit_price?number_format((float)$position->take_profit_price,8):'—'],
                                ['Trade P/L',$valuation['formatted_pnl']],
                                ['Return',$valuation['formatted_return']],
                            ] as [$label,$value])<div class="rounded-lg border border-border bg-muted/10 p-2.5"><p class="text-[8px] uppercase tracking-[.1em] text-muted-foreground">{{ $label }}</p><p class="mt-1 text-[10px] font-semibold" @if($label==='Trade P/L') data-market-position-pnl="{{ $position->id }}" @elseif($label==='Return') data-market-position-return="{{ $position->id }}" @elseif($label==='Mark') data-market-position-cmp="{{ $position->id }}" @endif>{{ $value }}</p></div>@endforeach
                        </div>
                    </div>
                    @if($isOpen)
                    <div class="flex flex-wrap gap-2 xl:max-w-[460px] xl:justify-end">
                        <button type="button" class="ui-btn ui-btn-secondary" onclick="document.getElementById('manage-{{ $position->id }}').showModal()">Manage</button>
                        <form data-account-action method="POST" action="{{ route('broker.positions.close',$position) }}" onsubmit="return confirm('Close the full remaining position at the next validated executable market quote?');">
                            @csrf
                            <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                            <button class="ui-btn border border-red-500/30 bg-red-500/10 text-red-600 hover:bg-red-500/15">Close Position</button>
                        </form>
                    </div>
                    @endif
                </div>

                @if($isOpen)
                <dialog id="manage-{{ $position->id }}" class="w-[min(96vw,760px)] rounded-2xl border border-border bg-background p-0 text-foreground shadow-2xl backdrop:bg-black/70">
                    <div class="flex items-start justify-between gap-4 border-b border-border px-5 py-4">
                        <div><p class="ui-kicker">Position Management</p><h3 class="mt-1 text-base font-semibold">{{ $instrument?->display_symbol }} · Position #{{ $position->id }}</h3></div>
                        <button type="button" class="rounded-lg border border-border p-2" onclick="document.getElementById('manage-{{ $position->id }}').close()"><i data-lucide="x" class="h-4 w-4"></i></button>
                    </div>
                    <div class="grid gap-4 p-5 md:grid-cols-2">
                        <form data-account-action method="POST" action="{{ route('broker.positions.risk',$position) }}" class="space-y-3 rounded-xl border border-border p-4">
                            @csrf @method('PATCH')
                            <p class="text-xs font-semibold">Risk controls</p>
                            <div><label class="ui-label">Stop loss %</label><input class="ui-input w-full" name="stop_loss_percent" type="number" step="0.01" min="0.01" max="99.99" value="{{ $position->stop_loss_percent }}"></div>
                            <div><label class="ui-label">Take profit %</label><input class="ui-input w-full" name="take_profit_percent" type="number" step="0.01" min="0.01" max="99.99" value="{{ $position->take_profit_percent }}"></div>
                            <div><label class="ui-label">Duration minutes</label><input class="ui-input w-full" name="duration_minutes" type="number" min="1" max="43200" value="{{ $position->duration_minutes }}"></div>
                            <button class="ui-btn ui-btn-secondary w-full">Update Risk</button>
                        </form>
                        <form data-account-action method="POST" action="{{ route('broker.positions.close',$position) }}" class="space-y-3 rounded-xl border border-border p-4">
                            @csrf
                            <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                            <p class="text-xs font-semibold">Reduce exposure</p>
                            <div><label class="ui-label">Units to close</label><input class="ui-input w-full" name="quantity" type="number" step="0.00000001" min="0.00000001" max="{{ $open }}" placeholder="Up to {{ number_format($open,8) }}" required></div>
                            <p class="text-[10px] leading-5 text-muted-foreground">A partial close reduces this exact position. Long positions close with a Sell; short positions close with a Buy.</p>
                            <button class="ui-btn ui-btn-primary w-full">Submit Partial Close</button>
                        </form>
                    </div>
                </dialog>
                @endif
            </article>
        @empty
            <div class="ui-panel p-10 text-center text-sm text-muted-foreground">No positions yet.</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $positions->links() }}</div>
</div>
</div>
</x-user-layout>
