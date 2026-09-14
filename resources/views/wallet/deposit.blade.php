<x-user-layout>
    <x-slot name="header">Deposit Funds</x-slot>

    @php($accountWallet = auth()->user()->wallet)

    <div class="money-page">
        <section class="money-page-header">
            <div>
                <p class="ui-kicker">Wallet & Finance</p>
                <h1 class="money-page-title">Deposit funds</h1>
                <p class="money-page-copy">Add funds to your account. Deposits remain pending until verification and only then become available to spend.</p>
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
                <strong>{{ format_currency($accountWallet?->available_balance ?? $accountWallet?->balance ?? 0) }}</strong>
            </div>
            <div class="money-balance-card">
                <span>Wallet balance</span>
                <strong>{{ format_currency($accountWallet?->balance ?? 0) }}</strong>
            </div>
            <div class="money-balance-card">
                <span>Reserved</span>
                <strong>{{ format_currency($accountWallet?->reserved_balance ?? 0) }}</strong>
            </div>
        </section>

        <div class="money-workspace">
            <section class="money-form-card">
                <div class="money-card-head">
                    <div>
                        <h2>Deposit request</h2>
                        <p>Choose an amount and a funding method.</p>
                    </div>
                    <div class="ui-metric-icon"><i data-lucide="circle-plus" class="h-4 w-4"></i></div>
                </div>

                <div class="money-card-body">
                    @if($errors->any())
                        <div class="mb-5 rounded-lg border border-red-500/20 bg-red-500/10 p-3 text-sm text-red-600 dark:text-red-400">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form action="{{ route('wallet.process-deposit') }}" method="POST" class="space-y-6">
                        @csrf

                        <div>
                            <label for="amount" class="ui-label">Deposit amount</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">{{ currency_symbol() }}</span>
                                <input id="amount" name="amount" type="number" step="0.01" min="1" max="100000" value="{{ old('amount') }}" class="ui-input pl-8" placeholder="0.00" required>
                            </div>
                            @error('amount')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="ui-label">Funding method</label>
                            <div class="money-method-list">
                                @forelse($paymentMethods->where('allow_deposit', true) as $method)
                                    <label class="money-method">
                                        <input type="radio" name="payment_method_id" value="{{ $method->id }}" class="h-4 w-4 border-border text-foreground focus:ring-ring" {{ old('payment_method_id') == $method->id ? 'checked' : '' }} required>
                                        <div class="money-method-icon">
                                            @if($method->logo)
                                                <img src="{{ asset('storage/' . $method->logo) }}" alt="" class="h-5 w-5 object-contain">
                                            @else
                                                <i data-lucide="{{ $method->isCryptocurrency() ? 'bitcoin' : 'credit-card' }}" class="h-4 w-4"></i>
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-foreground">{{ $method->name }}</p>
                                            <p class="mt-0.5 text-xs text-muted-foreground">{{ $method->description ?: 'Deposit method' }}</p>
                                        </div>
                                        <i data-lucide="chevron-right" class="h-4 w-4 text-muted-foreground"></i>
                                    </label>
                                @empty
                                    <div class="money-note">No active deposit method is currently available.</div>
                                @endforelse
                            </div>
                            @error('payment_method_id')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div class="money-note flex gap-2.5">
                            <i data-lucide="shield-check" class="mt-0.5 h-4 w-4 shrink-0 text-foreground"></i>
                            <p>Your balance will not increase until this deposit is verified and completed.</p>
                        </div>

                        <button type="submit" class="ui-btn ui-btn-primary w-full sm:w-auto">
                            Submit deposit
                            <i data-lucide="arrow-right" class="h-4 w-4"></i>
                        </button>
                    </form>
                </div>
            </section>

            <aside class="money-summary-card">
                <div class="money-card-head">
                    <div>
                        <h2>Deposit summary</h2>
                        <p>Review what will happen after submission.</p>
                    </div>
                </div>
                <div class="money-card-body">
                    <div class="money-summary-row"><span>Deposit amount</span><strong id="deposit-amount">{{ currency_symbol() }}0.00</strong></div>
                    <div class="money-summary-row"><span>Processing fee</span><strong id="processing-fee">{{ currency_symbol() }}0.00</strong></div>
                    <div class="money-summary-row money-summary-total"><span>Total submitted</span><strong id="total-amount">{{ currency_symbol() }}0.00</strong></div>
                    <div class="mt-5 money-note">
                        <strong class="mb-1 block text-foreground">Status flow</strong>
                        Pending → Verified → Credited → Completed
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <script>
        const amountInput = document.getElementById('amount');
        const depositAmount = document.getElementById('deposit-amount');
        const processingFee = document.getElementById('processing-fee');
        const totalAmount = document.getElementById('total-amount');

        function updateDepositSummary() {
            const amount = parseFloat(amountInput?.value) || 0;
            const fee = 0;
            if (depositAmount) depositAmount.textContent = `{{ currency_symbol() }}${amount.toFixed(2)}`;
            if (processingFee) processingFee.textContent = `{{ currency_symbol() }}${fee.toFixed(2)}`;
            if (totalAmount) totalAmount.textContent = `{{ currency_symbol() }}${(amount + fee).toFixed(2)}`;
        }

        amountInput?.addEventListener('input', updateDepositSummary);
        updateDepositSummary();
    </script>
</x-user-layout>
