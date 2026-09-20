<section class="flex min-h-[470px] flex-col rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
    <p class="text-[10px] font-semibold uppercase tracking-[.16em] text-red-600">Investment Action</p>

    @if(auth()->user()->isAdmin())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-xs leading-5 text-red-800 dark:border-red-950 dark:bg-red-950/20 dark:text-red-300">
            Customer-owned financial actions are disabled in audit mode. Use <strong>Admin Investment Control</strong> to perform an explicit customer-targeted subscription or redemption.
        </div>
    @else
        @php
            $walletAvailable = $viewerWallet ? max(0, (float)$viewerWallet->balance - (float)$viewerWallet->reserved_balance) : 0;
            $holdingUnits = $viewerHolding ? (float)$viewerHolding->units : 0;
            $locked = $viewerHolding?->locked_until && now()->lt($viewerHolding->locked_until);
        @endphp

        @php
            $actionStory = app(\App\Services\PrivateInvestmentProjectionService::class)->forInstrument($instrument, (float)$instrument->minimum_investment);
        @endphp
        <div class="mt-4 rounded-xl border border-zinc-200 p-3 dark:border-zinc-800">
            <div class="flex items-center justify-between gap-3"><span class="text-[9px] uppercase tracking-[.1em] text-zinc-400">Return cycle</span><strong class="text-[10px]">{{ $actionStory['cycle_return_label'] }} / {{ $actionStory['return_interval_label'] }}</strong></div>
            <div class="mt-2 flex items-center justify-between gap-3"><span class="text-[9px] uppercase tracking-[.1em] text-zinc-400">Duration</span><strong class="text-[10px]">{{ $actionStory['duration_label'] }}</strong></div>
            <div class="mt-2 flex items-center justify-between gap-3"><span class="text-[9px] uppercase tracking-[.1em] text-zinc-400">Subscription fee</span><strong class="text-[10px]">{{ number_format((float)$instrument->subscription_fee_percent,2) }}%</strong></div>
            <div class="mt-2 flex items-center justify-between gap-3"><span class="text-[9px] uppercase tracking-[.1em] text-zinc-400">Redemption fee</span><strong class="text-[10px]">{{ number_format((float)$instrument->redemption_fee_percent,2) }}%</strong></div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3">
            <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-900">
                <p class="text-[9px] uppercase tracking-[.1em] text-zinc-400">Wallet available</p>
                <p class="mt-1 text-sm font-semibold">{{ currency_symbol() }}{{ number_format($walletAvailable,2) }}</p>
            </div>
            <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-900">
                <p class="text-[9px] uppercase tracking-[.1em] text-zinc-400">Your units</p>
                <p class="mt-1 text-sm font-semibold">{{ number_format($holdingUnits,6) }}</p>
            </div>
        </div>

        @if($viewerHolding)
            <div class="mt-3 rounded-xl border border-zinc-200 p-3 text-[10px] leading-4 text-zinc-500 dark:border-zinc-800">
                <div class="flex justify-between gap-4"><span>Current value</span><strong class="text-zinc-900 dark:text-white">{{ currency_symbol() }}{{ number_format((float)$viewerHolding->current_value,2) }}</strong></div>
                <div class="mt-2 flex justify-between gap-4"><span>Average entry</span><strong class="text-zinc-900 dark:text-white">{{ currency_symbol() }}{{ number_format((float)$viewerHolding->average_entry_price,2) }}</strong></div>
                @if($viewerHolding->locked_until)
                    <div class="mt-2 flex justify-between gap-4"><span>Locked until</span><strong class="{{ $locked ? 'text-amber-600' : 'text-emerald-600' }}">{{ $viewerHolding->locked_until->format('M j, Y H:i') }}</strong></div>
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('account.investments.subscribe',$instrument) }}" class="mt-4">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
            <label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-zinc-400">Investment amount</label>
            <div class="flex gap-2">
                <input class="ui-input flex-1" type="number" step="0.01" min="{{ max(.01,(float)$instrument->minimum_investment) }}" name="amount" placeholder="Minimum {{ currency_symbol() }}{{ number_format((float)$instrument->minimum_investment,2) }}" required>
                <button class="rounded-xl bg-red-600 px-4 text-xs font-semibold text-white hover:bg-red-700" @disabled($instrument->status!=='active')>Subscribe</button>
            </div>
        </form>

        @if($holdingUnits > 0)
            <form method="POST" action="{{ route('account.investments.redeem',$instrument) }}" class="mt-3">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                <label class="mb-1.5 block text-[9px] font-semibold uppercase tracking-[.1em] text-zinc-400">Redeem units</label>
                <div class="flex gap-2">
                    <input class="ui-input flex-1" type="number" step="0.000001" min="0.000001" max="{{ $holdingUnits }}" name="units" value="{{ $holdingUnits }}" required>
                    <button class="rounded-xl border border-zinc-300 px-4 text-xs font-semibold dark:border-zinc-700" @disabled($locked || $instrument->status!=='active')>Redeem</button>
                </div>
                @if($locked)
                    <p class="mt-2 text-[9px] text-amber-600">This holding is still inside its lock period.</p>
                @endif
            </form>
        @endif

        <p class="mt-3 text-[9px] leading-4 text-zinc-400">
            Executions use the current Private Investment Engine price. No external live-market quote is used.
        </p>
    @endif
</section>
