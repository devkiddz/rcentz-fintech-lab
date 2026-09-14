<x-user-layout>
    <x-slot name="header">Withdraw Funds</x-slot>

    <div class="money-page">
        <section class="money-page-header">
            <div>
                <p class="ui-kicker">Wallet & Finance</p>
                <h1 class="money-page-title">Withdraw funds</h1>
                <p class="money-page-copy">Request a payout from your available balance. Requested funds are reserved immediately while the withdrawal is reviewed.</p>
            </div>
            <div class="ui-header-actions">
                <a href="{{ route('account.history') }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="history" class="h-4 w-4"></i> History
                </a>
                <a href="{{ route('wallet.index') }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="wallet" class="h-4 w-4"></i> Wallet
                </a>
            </div>
        </section>

        <section class="money-balance-strip">
            <div class="money-balance-card">
                <span>Available balance</span>
                <strong>{{ format_currency($wallet->available_balance) }}</strong>
            </div>
            <div class="money-balance-card">
                <span>Wallet balance</span>
                <strong>{{ format_currency($wallet->balance) }}</strong>
            </div>
            <div class="money-balance-card">
                <span>Reserved</span>
                <strong>{{ format_currency($wallet->reserved_balance) }}</strong>
            </div>
        </section>

        <div class="money-workspace">
            <section class="money-form-card">
                <div class="money-card-head">
                    <div>
                        <h2>Withdrawal request</h2>
                        <p>Choose an amount and payout destination.</p>
                    </div>
                    <div class="ui-metric-icon"><i data-lucide="circle-minus" class="h-4 w-4"></i></div>
                </div>

                <div class="money-card-body">
                    @if($errors->any())
                        <div class="mb-5 rounded-lg border border-red-500/20 bg-red-500/10 p-3 text-sm text-red-600 dark:text-red-400">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form action="{{ route('wallet.process-withdrawal') }}" method="POST" class="space-y-6">
                        @csrf

                        <div>
                            <label for="amount" class="ui-label">Withdrawal amount</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">{{ currency_symbol() }}</span>
                                <input id="amount" name="amount" type="number" step="0.01" min="1" max="{{ $wallet->available_balance }}" value="{{ old('amount') }}" class="ui-input pl-8" placeholder="0.00" required>
                            </div>
                            <p class="mt-1.5 text-xs text-muted-foreground">Maximum available: {{ format_currency($wallet->available_balance) }}</p>
                            @error('amount')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="ui-label">Withdrawal method</label>
                            <div class="money-method-list">
                                @forelse($paymentMethods->where('allow_withdraw', true) as $method)
                                    <label class="money-method">
                                        <input type="radio" name="payment_method_id" value="{{ $method->id }}" class="h-4 w-4 border-border text-foreground focus:ring-ring" {{ old('payment_method_id') == $method->id ? 'checked' : '' }} required>
                                        <div class="money-method-icon">
                                            @if($method->logo)
                                                <img src="{{ asset('storage/' . $method->logo) }}" alt="" class="h-5 w-5 object-contain">
                                            @else
                                                <i data-lucide="{{ $method->isCryptocurrency() ? 'bitcoin' : 'landmark' }}" class="h-4 w-4"></i>
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-foreground">{{ $method->name }}</p>
                                            <p class="mt-0.5 text-xs text-muted-foreground">{{ $method->description ?: 'Withdrawal method' }}</p>
                                        </div>
                                        <i data-lucide="chevron-right" class="h-4 w-4 text-muted-foreground"></i>
                                    </label>
                                @empty
                                    <div class="money-note">No active withdrawal method is currently available.</div>
                                @endforelse
                            </div>
                            @error('payment_method_id')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div id="destination-crypto" class="hidden">
                            <label for="wallet_address" class="ui-label">Destination wallet address</label>
                            <input id="wallet_address" name="wallet_address" type="text" value="{{ old('wallet_address') }}" class="ui-input font-mono" placeholder="Enter destination address">
                            @error('wallet_address')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div class="money-note flex gap-2.5">
                            <i data-lucide="lock-keyhole" class="mt-0.5 h-4 w-4 shrink-0 text-foreground"></i>
                            <p>Submitting this request reserves the amount immediately. Rejected requests release the reservation; approved requests settle it permanently.</p>
                        </div>

                        <button type="submit" class="ui-btn ui-btn-primary w-full sm:w-auto">
                            Submit withdrawal
                            <i data-lucide="arrow-right" class="h-4 w-4"></i>
                        </button>
                    </form>
                </div>
            </section>

            <aside class="money-summary-card">
                <div class="money-card-head">
                    <div>
                        <h2>Withdrawal summary</h2>
                        <p>Balance impact before you submit.</p>
                    </div>
                </div>
                <div class="money-card-body">
                    <div class="money-summary-row"><span>Withdrawal amount</span><strong id="withdrawal-amount">{{ currency_symbol() }}0.00</strong></div>
                    <div class="money-summary-row"><span>Processing fee</span><strong id="processing-fee">{{ currency_symbol() }}0.00</strong></div>
                    <div class="money-summary-row"><span>Available after request</span><strong id="available-after">{{ format_currency($wallet->available_balance) }}</strong></div>
                    <div class="money-summary-row"><span>Reserved after request</span><strong id="reserved-after">{{ format_currency($wallet->reserved_balance) }}</strong></div>
                    <div class="money-summary-row money-summary-total"><span>Requested payout</span><strong id="net-amount">{{ currency_symbol() }}0.00</strong></div>
                    <div class="mt-5 money-note">
                        <strong class="mb-1 block text-foreground">Status flow</strong>
                        Requested → Reserved → Reviewed → Completed / Released
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <script>
        const amountInput = document.getElementById('amount');
        const withdrawalAmount = document.getElementById('withdrawal-amount');
        const processingFee = document.getElementById('processing-fee');
        const netAmount = document.getElementById('net-amount');
        const availableAfter = document.getElementById('available-after');
        const reservedAfter = document.getElementById('reserved-after');
        const startingAvailable = {{ (float) $wallet->available_balance }};
        const startingReserved = {{ (float) $wallet->reserved_balance }};

        function money(value) {
            return `{{ currency_symbol() }}${Math.max(0, value).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        }

        function updateWithdrawalSummary() {
            const amount = parseFloat(amountInput?.value) || 0;
            const fee = 0;
            if (withdrawalAmount) withdrawalAmount.textContent = money(amount);
            if (processingFee) processingFee.textContent = money(fee);
            if (netAmount) netAmount.textContent = money(amount - fee);
            if (availableAfter) availableAfter.textContent = money(startingAvailable - amount);
            if (reservedAfter) reservedAfter.textContent = money(startingReserved + amount);
        }

        amountInput?.addEventListener('input', updateWithdrawalSummary);
        updateWithdrawalSummary();

        const methodRadios = document.querySelectorAll('input[name="payment_method_id"]');
        const cryptoIds = [
            @foreach($paymentMethods->where('allow_withdraw', true)->where('type','cryptocurrency') as $m)
                '{{ $m->id }}',
            @endforeach
        ];
        const destCrypto = document.getElementById('destination-crypto');

        function updateDestinationFields() {
            const selected = document.querySelector('input[name="payment_method_id"]:checked');
            const isCrypto = selected ? cryptoIds.includes(selected.value) : false;
            destCrypto?.classList.toggle('hidden', !isCrypto);
            const walletInput = document.getElementById('wallet_address');
            if (walletInput) walletInput.required = isCrypto;
        }

        methodRadios.forEach(r => r.addEventListener('change', updateDestinationFields));
        updateDestinationFields();
    </script>
</x-user-layout>
