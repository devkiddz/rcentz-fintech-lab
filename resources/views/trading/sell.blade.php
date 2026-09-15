<x-user-layout>
<x-slot name="header">Sell {{ $stock->symbol }}</x-slot>

@php
    $price = (float) $stock->current_price;
    $owned = (float) $holding->quantity;
    $walletAvailable = (float) $wallet->available_balance;
    $latestQuote = App\Models\StockQuote::getLatestQuote($stock->symbol);
@endphp

<div class="ui-page max-w-[1280px]">
    <section class="ui-page-header">
        <div>
            <div class="flex items-center gap-2">
                <p class="ui-kicker text-[10px]">Stock Order</p>
                <span class="rounded-full border border-red-500/20 bg-red-500/10 px-2 py-1 text-[9px] font-semibold text-red-600">{{ $stock->symbol }}</span>
            </div>
            <h1 class="ui-heading !text-2xl">Sell {{ $stock->company_name }}</h1>
            <p class="ui-lead !text-[13px]">Reduce or close your position with a visible proceeds and re-entry plan.</p>
        </div>

        <div class="ui-panel min-w-[240px] p-3.5">
            <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Current price</p>
            <div class="mt-1 flex items-end justify-between gap-4">
                <p class="text-2xl font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($price,2) }}</p>
                <p class="text-xs font-semibold {{ $stock->change_percentage >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $stock->change_percentage >= 0 ? '+' : '' }}{{ number_format($stock->change_percentage,2) }}%
                </p>
            </div>
        </div>
    </section>


    <div class="mb-4">
        @include('trading.partials.analysis-chart',['stock'=>$stock,'analysis'=>$analysis,'chartHeight'=>'h-[300px] sm:h-[360px] lg:h-[420px]'])
    </div>

    <form action="{{ route('trading.execute-sell',$stock) }}" method="POST" id="sell-stock-form" class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_390px]">
        @csrf

        <div class="space-y-4">
            <section class="ui-panel p-4">
                <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Position size</p>
                <h2 class="mt-1 text-sm font-semibold">Choose shares to sell</h2>

                <div class="mt-4 grid gap-3 sm:grid-cols-[1fr_220px]">
                    <div>
                        <label for="quantity" class="ui-label !text-[10px]">Number of shares</label>
                        <input id="quantity" name="quantity" type="number" min="1" max="{{ $owned }}" step="1"
                               value="{{ old('quantity',1) }}"
                               class="ui-input !h-14 !text-lg !font-semibold" required>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach([25,50,75,100] as $pct)
                                <button type="button" data-sell-percent="{{ $pct }}" class="ui-btn ui-btn-secondary !h-7 !px-2.5 !text-[10px]">{{ $pct }}%</button>
                            @endforeach
                            <span class="ml-auto self-center text-[9px] text-muted-foreground">{{ number_format($owned,6) }} shares owned</span>
                        </div>
                    </div>
                    <div class="rounded-xl border border-border bg-muted/20 p-3">
                        <p class="text-[9px] uppercase tracking-[.12em] text-muted-foreground">Estimated proceeds</p>
                        <p id="sell-order-value" class="mt-1 text-xl font-semibold tabular-nums">{{ currency_symbol() }}{{ number_format($price,2) }}</p>
                        <p class="mt-2 text-[10px] text-muted-foreground">{{ currency_symbol() }}{{ number_format($price,2) }} per share</p>
                    </div>
                </div>
                @error('quantity')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </section>

            <section class="ui-panel p-4">
                <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Position context</p>
                <div class="mt-3 grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                    @foreach([
                        ['Shares Owned', number_format($owned,6)],
                        ['Avg. Entry', currency_symbol().number_format((float)$holding->average_buy_price,2)],
                        ['Current Value', currency_symbol().number_format((float)$holding->current_value,2)],
                        ['Unrealized P/L', (($holding->unrealized_gain_loss >= 0 ? '+' : '').currency_symbol().number_format((float)$holding->unrealized_gain_loss,2))],
                    ] as [$label,$value])
                        <div class="rounded-lg border border-border bg-muted/10 p-2.5">
                            <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p>
                            <p class="mt-1 text-[11px] font-semibold">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>
            </section>


            <section class="ui-panel p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Exit intelligence</p>
                        <h2 class="mt-1 text-sm font-semibold">Market context before you sell</h2>
                    </div>
                    <span class="rounded-full border border-border bg-muted px-2 py-1 text-[9px] font-semibold text-muted-foreground">{{ $analysis['trend'] }}</span>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2.5 md:grid-cols-4">
                    @foreach([
                        ['Momentum',$analysis['momentum_label']],
                        ['Support',$analysis['support'] ? currency_symbol().number_format($analysis['support'],2) : '—'],
                        ['Resistance',$analysis['resistance'] ? currency_symbol().number_format($analysis['resistance'],2) : '—'],
                        ['Risk / Reward',$analysis['risk_reward']],
                    ] as [$label,$value])
                        <div class="rounded-xl border border-border bg-muted/10 p-2.5">
                            <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">{{ $label }}</p>
                            <p class="mt-1 text-xs font-semibold">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            @if($latestQuote && $latestQuote->total_recommendations > 0)
            <section class="ui-panel p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Analyst sentiment</p>
                        <h2 class="mt-1 text-sm font-semibold">{{ $latestQuote->recommendation_label }}</h2>
                    </div>
                    <span class="rounded-full border border-violet-500/20 bg-violet-500/10 px-2 py-1 text-[10px] font-semibold text-violet-600">
                        {{ number_format($latestQuote->recommendation_percentage,1) }}% buy rating
                    </span>
                </div>
            </section>
            @endif
        </div>

        <aside class="space-y-4">
            <section class="ui-panel overflow-hidden">
                <div class="border-b border-border px-4 py-3">
                    <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Live sell calculator</p>
                    <h2 class="mt-1 text-sm font-semibold">Sale preview</h2>
                </div>

                <div class="p-4">
                    <div class="grid grid-cols-2 gap-2.5">
                        <div class="rounded-xl border border-red-500/20 bg-red-500/5 p-3">
                            <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">Shares selling</p>
                            <p id="sell-shares-display" class="mt-1 text-lg font-semibold">1</p>
                        </div>
                        <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-3">
                            <p class="text-[8px] uppercase tracking-[.11em] text-muted-foreground">Proceeds</p>
                            <p id="sell-total-display" class="mt-1 text-lg font-semibold">{{ currency_symbol() }}{{ number_format($price,2) }}</p>
                        </div>
                    </div>

                    <div class="mt-3 space-y-2.5 rounded-xl border border-border bg-muted/10 p-3">
                        <div class="flex justify-between text-xs"><span class="text-muted-foreground">Shares remaining</span><span id="shares-remaining" class="font-semibold">{{ number_format(max(0,$owned-1),6) }}</span></div>
                        <div class="flex justify-between text-xs"><span class="text-muted-foreground">Wallet before sale</span><span class="font-semibold">{{ currency_symbol() }}{{ number_format($walletAvailable,2) }}</span></div>
                        <div class="flex justify-between text-xs"><span class="text-muted-foreground">Wallet after sale</span><span id="wallet-after-sale" class="font-semibold text-emerald-600">{{ currency_symbol() }}{{ number_format($walletAvailable+$price,2) }}</span></div>
                    </div>

                    <div class="mt-4 border-t border-border pt-4">
                        <p class="text-[9px] uppercase tracking-[.13em] text-muted-foreground">Re-entry horizon</p>
                        <h3 class="mt-1 text-xs font-semibold">When should this sale be reviewed or bought back?</h3>

                        <div class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-6">
                            @foreach([[15,'15m'],[30,'30m'],[60,'1h'],[240,'4h'],[1440,'1d'],[10080,'1w']] as [$minutes,$label])
                                <button type="button" data-plan-minutes="{{ $minutes }}"
                                        class="trade-horizon-btn ui-btn ui-btn-secondary !h-8 !px-2 !text-[10px] {{ $minutes === 60 ? '!border-sky-500/40 !bg-sky-500/10' : '' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <label for="custom-plan-minutes" class="ui-label !text-[10px]">Custom minutes</label>
                                <input id="custom-plan-minutes" type="number" min="1" max="10080" class="ui-input" placeholder="e.g. 90">
                            </div>
                            <div>
                                <label for="plan_mode" class="ui-label !text-[10px]">At expiry</label>
                                <select id="plan_mode" name="plan_mode" class="ui-input">
                                    <option value="reminder">Notify me to review & buy back</option>
                                    <option value="automatic">Automatically buy back this quantity</option>
                                </select>
                            </div>
                        </div>

                        <input type="hidden" id="plan_duration_minutes" name="plan_duration_minutes" value="60">

                        <div class="mt-3 flex items-center justify-between rounded-lg border border-border bg-muted/10 px-3 py-2.5">
                            <div>
                                <p class="text-[9px] uppercase tracking-[.11em] text-muted-foreground">Planned action</p>
                                <p class="mt-1 text-[11px] font-semibold">Buy back sold quantity</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[9px] uppercase tracking-[.11em] text-muted-foreground">Due after</p>
                                <p id="plan-horizon-label" class="mt-1 text-[11px] font-semibold">1 hour</p>
                            </div>
                        </div>
                    </div>

                    <button id="sell-submit" type="submit" class="ui-btn ui-btn-primary mt-4 h-11 w-full">
                        <i data-lucide="arrow-down-right" class="h-4 w-4"></i> Sell Shares
                    </button>
                </div>
            </section>
        </aside>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const quantityInput = document.getElementById('quantity');
    const sharesDisplay = document.getElementById('sell-shares-display');
    const orderValue = document.getElementById('sell-order-value');
    const totalDisplay = document.getElementById('sell-total-display');
    const sharesRemaining = document.getElementById('shares-remaining');
    const walletAfter = document.getElementById('wallet-after-sale');
    const percentButtons = document.querySelectorAll('[data-sell-percent]');

    const price = @json($price);
    const owned = @json($owned);
    const wallet = @json($walletAvailable);
    const currency = @json(currency_symbol());

    function money(value) {
        return currency + Number(value).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
    }

    function updateSale() {
        let q = Math.max(0, Math.min(owned, Number(quantityInput.value) || 0));
        let total = q * price;
        sharesDisplay.textContent = q.toLocaleString(undefined,{maximumFractionDigits:6});
        orderValue.textContent = money(total);
        totalDisplay.textContent = money(total);
        sharesRemaining.textContent = Math.max(0, owned-q).toLocaleString(undefined,{maximumFractionDigits:6});
        walletAfter.textContent = money(wallet+total);
    }

    quantityInput.addEventListener('input', updateSale);
    percentButtons.forEach(function(button){
        button.addEventListener('click', function(){
            const pct = Number(button.dataset.sellPercent)/100;
            quantityInput.value = Math.max(1, Math.floor(owned*pct));
            updateSale();
        });
    });

    const planMinutesInput = document.getElementById('plan_duration_minutes');
    const planLabel = document.getElementById('plan-horizon-label');
    const customPlanMinutes = document.getElementById('custom-plan-minutes');
    const horizonButtons = document.querySelectorAll('[data-plan-minutes]');

    function prettyDuration(minutes) {
        minutes = Number(minutes) || 0;
        if (minutes % 10080 === 0 && minutes >= 10080) return (minutes/10080)+' week'+(minutes===10080?'':'s');
        if (minutes % 1440 === 0 && minutes >= 1440) return (minutes/1440)+' day'+(minutes===1440?'':'s');
        if (minutes % 60 === 0 && minutes >= 60) return (minutes/60)+' hour'+(minutes===60?'':'s');
        return minutes+' minutes';
    }

    function setHorizon(minutes, sourceButton) {
        minutes = Math.max(1,Math.min(10080,Math.floor(Number(minutes)||60)));
        planMinutesInput.value = minutes;
        planLabel.textContent = prettyDuration(minutes);
        horizonButtons.forEach(btn=>btn.classList.remove('!border-sky-500/40','!bg-sky-500/10'));
        if(sourceButton) sourceButton.classList.add('!border-sky-500/40','!bg-sky-500/10');
    }

    horizonButtons.forEach(button=>button.addEventListener('click',function(){
        customPlanMinutes.value='';
        setHorizon(button.dataset.planMinutes,button);
    }));
    customPlanMinutes.addEventListener('input',function(){
        if(customPlanMinutes.value) setHorizon(customPlanMinutes.value,null);
    });

    updateSale();
});
</script>
</x-user-layout>
