{{-- Rcentz V5.7 shared admin trade contract controls --}}
<div
    class="admin-trade-contract rounded-xl border border-border bg-muted/10 p-3"
    data-admin-trade-contract
    data-entry-price="{{ number_format((float)$entryPrice, 8, '.', '') }}"
>
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-[8px] uppercase tracking-[.12em] text-muted-foreground">Trade contract</p>
            <h3 class="mt-1 text-[11px] font-semibold">EMP · Risk · Exit horizon</h3>
        </div>
        <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-1 text-[8px] font-semibold text-emerald-600">
            First trigger wins
        </span>
    </div>

    <div data-role="buy-fields">
        <div class="mt-3 flex items-center justify-between rounded-lg border border-border bg-background/60 px-3 py-2.5">
            <div>
                <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">Entry Market Price (EMP)</p>
                <p class="mt-1 text-sm font-semibold tabular-nums">
                    {{ currency_symbol() }}{{ number_format((float)$entryPrice, 2) }}
                </p>
            </div>
            <span class="text-[8px] text-muted-foreground">Fixed at execution</span>
        </div>

        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl border border-border bg-background/50 p-3">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[10px] font-semibold">Stop loss</span>
                    <span class="text-[8px] text-muted-foreground">Below EMP</span>
                </div>

                <div class="mt-2 grid grid-cols-1 gap-2">
                    <div>
                        <label class="mb-1 block text-[8px] uppercase tracking-[.1em] text-muted-foreground">Percent</label>
                        <div class="relative">
                            <input
                                name="stop_loss_percent"
                                data-role="sl-percent"
                                type="number"
                                min="0.01"
                                max="100"
                                step="0.01"
                                class="ui-input w-full pr-8"
                                placeholder="e.g. 2"
                            >
                            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-muted-foreground">%</span>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-[8px] uppercase tracking-[.1em] text-muted-foreground">Market price</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-muted-foreground">{{ currency_symbol() }}</span>
                            <input
                                data-role="sl-price"
                                type="number"
                                min="0"
                                step="0.01"
                                class="ui-input w-full pl-7"
                                placeholder="Price"
                            >
                        </div>
                    </div>
                </div>

                <p data-role="sl-summary" class="mt-2 text-[8px] leading-4 text-muted-foreground">Enter % or market price</p>
            </div>

            <div class="rounded-xl border border-border bg-background/50 p-3">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[10px] font-semibold">Take profit</span>
                    <span class="text-[8px] text-muted-foreground">Above EMP</span>
                </div>

                <div class="mt-2 grid grid-cols-1 gap-2">
                    <div>
                        <label class="mb-1 block text-[8px] uppercase tracking-[.1em] text-muted-foreground">Percent</label>
                        <div class="relative">
                            <input
                                name="take_profit_percent"
                                data-role="tp-percent"
                                type="number"
                                min="0.01"
                                max="100"
                                step="0.01"
                                class="ui-input w-full pr-8"
                                placeholder="e.g. 5"
                            >
                            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-muted-foreground">%</span>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-[8px] uppercase tracking-[.1em] text-muted-foreground">Market price</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[10px] text-muted-foreground">{{ currency_symbol() }}</span>
                            <input
                                data-role="tp-price"
                                type="number"
                                min="0"
                                step="0.01"
                                class="ui-input w-full pl-7"
                                placeholder="Price"
                            >
                        </div>
                    </div>
                </div>

                <p data-role="tp-summary" class="mt-2 text-[8px] leading-4 text-muted-foreground">Enter % or market price</p>
            </div>
        </div>

        <div class="mt-3 rounded-xl border border-border bg-background/50 p-3">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">Trade horizon</p>
                    <p class="mt-1 text-[10px] font-semibold">Maximum contract duration</p>
                </div>
                <div class="text-right">
                    <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">Effective choice</p>
                    <p data-role="duration-label" class="mt-1 text-[9px] font-semibold">Regular market close</p>
                </div>
            </div>

            <div class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-6">
                @foreach([[15,'15m'],[30,'30m'],[60,'1h'],[240,'4h'],[1440,'1d'],[10080,'1w']] as [$minutes,$label])
                    <button
                        type="button"
                        data-duration-minutes="{{ $minutes }}"
                        class="ui-btn ui-btn-secondary !h-8 !px-2 !text-[9px]"
                    >{{ $label }}</button>
                @endforeach
            </div>

            <div class="mt-3 grid gap-2 sm:grid-cols-[1fr_auto]">
                <input
                    data-role="custom-duration"
                    type="number"
                    min="1"
                    max="43200"
                    class="ui-input"
                    placeholder="Custom minutes"
                >
                <button type="button" data-clear-duration class="ui-btn ui-btn-secondary !px-3 !text-[9px]">
                    Until market close
                </button>
            </div>

            <input data-role="duration-minutes" name="duration_minutes" type="hidden" value="">

            <p class="mt-2 text-[8px] leading-4 text-muted-foreground">
                The position can end earlier through Stop Loss or Take Profit. Regular U.S. market close remains the hard session boundary.
            </p>
        </div>
    </div>

    <div data-role="sell-notice" class="mt-3 hidden rounded-lg border border-amber-500/20 bg-amber-500/10 px-3 py-2.5">
        <p class="text-[9px] font-semibold text-amber-700 dark:text-amber-400">Exit existing exposure</p>
        <p class="mt-1 text-[8px] leading-4 text-muted-foreground">
            Sell does not open a new short contract here. It closes quantity from an existing open position, so new SL / TP / duration controls are disabled for this execution.
        </p>
    </div>
</div>