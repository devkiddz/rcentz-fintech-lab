<x-admin-layout>
    <x-slot name="header">Edit AI Trading Bot</x-slot>

    <div class="max-w-5xl mx-auto space-y-6">
        <div>
            <p class="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">AI Trading Bots</p>
            <h1 class="mt-2 text-2xl font-semibold text-foreground">Edit bot product</h1>
            <p class="mt-1 text-sm text-muted-foreground">Update the product rules customers receive from the marketplace.</p>
        </div>

        <form method="POST" action="{{ route('admin.ai-bots.update', $botProduct) }}" class="ui-panel p-6 space-y-6">
            @csrf
            @method('PATCH')

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="ui-label">Bot name</label>
                    <input name="name" class="ui-input" value="{{ old('name', $botProduct->name) }}" required>
                </div>

                <div>
                    <label class="ui-label">Stock</label>
                    <select name="stock_id" class="ui-input" required>
                        @foreach($stocks as $stock)
                            <option value="{{ $stock->id }}" @selected(old('stock_id', $botProduct->stock_id) == $stock->id)>
                                {{ $stock->symbol }} — {{ $stock->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="ui-label">Description</label>
                <textarea name="description" class="ui-input min-h-28">{{ old('description', $botProduct->description) }}</textarea>
            </div>

            <div class="grid gap-5 md:grid-cols-3">
                <div>
                    <label class="ui-label">Strategy</label>
                    <select name="strategy" class="ui-input">
                        <option value="dca" @selected(old('strategy', $botProduct->strategy) === 'dca')>DCA / Interval</option>
                        <option value="price_below" @selected(old('strategy', $botProduct->strategy) === 'price_below')>Price Below</option>
                        <option value="price_above" @selected(old('strategy', $botProduct->strategy) === 'price_above')>Price Above</option>
                    </select>
                </div>
                <div>
                    <label class="ui-label">Action</label>
                    <select name="action" class="ui-input">
                        <option value="buy" @selected(old('action', $botProduct->action) === 'buy')>Buy</option>
                        <option value="sell" @selected(old('action', $botProduct->action) === 'sell')>Sell</option>
                    </select>
                </div>
                <div>
                    <label class="ui-label">Risk level</label>
                    <select name="risk_level" class="ui-input">
                        <option value="low" @selected(old('risk_level', $botProduct->risk_level) === 'low')>Low</option>
                        <option value="medium" @selected(old('risk_level', $botProduct->risk_level) === 'medium')>Medium</option>
                        <option value="high" @selected(old('risk_level', $botProduct->risk_level) === 'high')>High</option>
                    </select>
                </div>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="ui-label">Access price</label>
                    <input name="price" type="number" min="0" step="0.01" class="ui-input" value="{{ old('price', $botProduct->price) }}" required>
                </div>
                <div>
                    <label class="ui-label">Billing period</label>
                    <select name="billing_period" class="ui-input">
                        <option value="one_time" @selected(old('billing_period', $botProduct->billing_period) === 'one_time')>One time</option>
                        <option value="monthly" @selected(old('billing_period', $botProduct->billing_period) === 'monthly')>Monthly</option>
                        <option value="quarterly" @selected(old('billing_period', $botProduct->billing_period) === 'quarterly')>Quarterly</option>
                        <option value="yearly" @selected(old('billing_period', $botProduct->billing_period) === 'yearly')>Yearly</option>
                    </select>
                </div>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="ui-label">Minimum customer balance</label>
                    <input name="minimum_balance" type="number" min="0" step="0.01" class="ui-input" value="{{ old('minimum_balance', $botProduct->minimum_balance) }}" required>
                </div>
                <div>
                    <label class="ui-label">Maximum user allocation</label>
                    <input name="max_user_allocation" type="number" min="1" step="0.01" class="ui-input" value="{{ old('max_user_allocation', $botProduct->max_user_allocation) }}">
                </div>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="ui-label">Default trade amount</label>
                    <input name="default_trade_amount" type="number" min="1" step="0.01" class="ui-input" value="{{ old('default_trade_amount', $botProduct->default_trade_amount) }}">
                </div>
                <div>
                    <label class="ui-label">Default trigger price</label>
                    <input name="default_trigger_price" type="number" min="0.01" step="0.01" class="ui-input" value="{{ old('default_trigger_price', $botProduct->default_trigger_price) }}">
                </div>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label class="ui-label">Interval minutes</label>
                    <input name="default_interval_minutes" type="number" min="5" class="ui-input" value="{{ old('default_interval_minutes', $botProduct->default_interval_minutes) }}" required>
                </div>
                <div>
                    <label class="ui-label">Maximum daily trades</label>
                    <input name="default_max_daily_trades" type="number" min="1" max="24" class="ui-input" value="{{ old('default_max_daily_trades', $botProduct->default_max_daily_trades) }}" required>
                </div>
            </div>

            <div class="rounded-2xl border border-border bg-muted/20 p-5 space-y-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-foreground">Presentation Performance Override</p>
                        <p class="mt-1 text-xs text-muted-foreground">Optional preview values for portfolio presentation. These values never change wallets, holdings, executions or ledger records.</p>
                    </div>
                    <span class="rounded-md border border-border bg-background px-2 py-1 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Presentation only</span>
                </div>
                <div class="rounded-xl border border-border bg-background px-4 py-3 text-xs text-muted-foreground">Calculation base: <strong id="performance-calculation-base" class="text-foreground">—</strong></div>

                <label class="flex items-center gap-3 rounded-xl border border-border bg-background p-4">
                    <input type="checkbox" name="use_manual_performance" value="1" @checked(old('use_manual_performance', $botProduct->use_manual_performance))>
                    <span class="text-sm font-medium">Use manual P/L and return on presentation cards</span>
                </label>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="ui-label">Manual Profit / Loss</label>
                        <input name="manual_profit_loss" type="number" step="0.01" class="ui-input" value="{{ old('manual_profit_loss', $botProduct->manual_profit_loss) }}" placeholder="e.g. 842.50 or -125.00">
                    </div>
                    <div>
                        <label class="ui-label">Manual Return %</label>
                        <input name="manual_return_percent" type="number" step="0.01" class="ui-input" value="{{ old('manual_return_percent', $botProduct->manual_return_percent) }}" placeholder="e.g. 8.42">
                    </div>
                </div>

                <div>
                    <label class="ui-label">Internal presentation note</label>
                    <input name="manual_performance_note" class="ui-input" value="{{ old('manual_performance_note', $botProduct->manual_performance_note) }}" placeholder="Why this preview override is being used">
                    <input type="hidden" name="manual_performance_source" id="manual_performance_source" value="">
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                <label class="flex items-center gap-3 rounded-xl border border-border p-4">
                    <input type="checkbox" name="allow_user_trade_amount" value="1" @checked(old('allow_user_trade_amount', $botProduct->allow_user_trade_amount))>
                    <span class="text-sm">Customer can adjust trade amount</span>
                </label>
                <label class="flex items-center gap-3 rounded-xl border border-border p-4">
                    <input type="checkbox" name="allow_user_trigger_price" value="1" @checked(old('allow_user_trigger_price', $botProduct->allow_user_trigger_price))>
                    <span class="text-sm">Customer can adjust trigger</span>
                </label>
                <label class="flex items-center gap-3 rounded-xl border border-border p-4">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $botProduct->is_active))>
                    <span class="text-sm">Visible in marketplace</span>
                </label>
            </div>

            <div class="flex justify-end gap-3 border-t border-border pt-5">
                <a href="{{ route('admin.ai-bots.index') }}" class="ui-btn ui-btn-secondary">Cancel</a>
                <button class="ui-btn ui-btn-primary">Save changes</button>
            </div>
        </form>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const pnl = document.querySelector('[name="manual_profit_loss"]');
    const ret = document.querySelector('[name="manual_return_percent"]');
    const source = document.getElementById('manual_performance_source');
    const baseLabel = document.getElementById('performance-calculation-base');

    function numberValue(name) {
        const el = document.querySelector('[name="' + name + '"]');
        if (!el || el.value === '') return 0;
        const value = Number(el.value);
        return Number.isFinite(value) ? value : 0;
    }

    function baseAmount() {
        const maxAllocation = numberValue('max_user_allocation');
            const minBalance = numberValue('minimum_balance');
            const tradeAmount = numberValue('default_trade_amount');
            return maxAllocation || minBalance || tradeAmount || 0;
    }

    function refreshBase() {
        const base = baseAmount();
        if (baseLabel) {
            baseLabel.textContent = base > 0 ? '$' + base.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : 'Set allocation/base amount';
        }
    }

    if (pnl && ret) {
        pnl.addEventListener('input', function () {
            const base = baseAmount();
            if (source) source.value = 'profit_loss';
            if (base > 0 && pnl.value !== '') {
                ret.value = ((Number(pnl.value) / base) * 100).toFixed(2);
            }
        });

        ret.addEventListener('input', function () {
            const base = baseAmount();
            if (source) source.value = 'return_percent';
            if (base > 0 && ret.value !== '') {
                pnl.value = (base * (Number(ret.value) / 100)).toFixed(2);
            }
        });
    }

    ['max_user_allocation','minimum_balance','default_trade_amount','recommended_allocation','minimum_allocation'].forEach(function(name) {
        const el = document.querySelector('[name="' + name + '"]');
        if (el) el.addEventListener('input', refreshBase);
    });

    refreshBase();
});
</script>

</x-admin-layout>
