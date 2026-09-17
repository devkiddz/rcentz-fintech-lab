<x-user-layout>
    <x-slot name="header">Complete Withdrawal</x-slot>

    <div class="ui-page max-w-5xl">
        <section class="ui-page-header">
            <div>
                <p class="ui-kicker">Secure withdrawal · Step 2</p>
                <h1 class="ui-heading">Verify & submit payout</h1>
                <p class="ui-lead">Your token unlocks the actual withdrawal request. Funds are reserved only after this form succeeds.</p>
            </div>
            <a href="{{ route('wallet.withdraw') }}" class="ui-btn ui-btn-secondary">Back</a>
        </section>

        @if($errors->any())<div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-600">{{ $errors->first() }}</div>@endif

        <div class="grid gap-4 lg:grid-cols-[1.05fr_.95fr]">
            <section class="ui-panel p-6">
                <div class="rounded-xl border border-border bg-muted/30 p-4">
                    <p class="ui-label">Approved token request amount</p>
                    <p class="mt-2 text-2xl font-semibold">{{ format_currency($tokenRequest->amount) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">Token ending ••••{{ $tokenRequest->token_last_four }} · expires {{ $tokenRequest->token_expires_at?->format('M j, Y H:i') }}</p>
                </div>

                <form method="POST" action="{{ route('wallet.withdrawal.token.submit',$tokenRequest) }}" class="mt-6 space-y-5">
                    @csrf
                    <div>
                        <label class="ui-label">6-digit token</label>
                        <input class="ui-input mt-1 w-full text-center font-mono text-xl tracking-[.35em]" name="token" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required>
                    </div>

                    <div>
                        <label class="ui-label">Withdrawal method</label>
                        <div class="mt-2 space-y-2">
                            @forelse($paymentMethods as $method)
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-border bg-card p-4 hover:bg-muted/30">
                                    <input type="radio" name="payment_method_id" value="{{ $method->id }}" required>
                                    <div class="flex-1"><p class="text-sm font-semibold">{{ $method->name }}</p><p class="mt-1 text-xs text-muted-foreground">{{ $method->description ?: 'Withdrawal method' }}</p></div>
                                </label>
                            @empty
                                <div class="rounded-xl border border-border bg-muted/30 p-4 text-sm text-muted-foreground">No withdrawal method is available.</div>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <label class="ui-label">Wallet / account destination <span class="font-normal text-muted-foreground">(when required)</span></label>
                        <input class="ui-input mt-1 w-full font-mono" name="wallet_address" value="{{ old('wallet_address') }}" placeholder="Wallet address or payout destination">
                    </div>

                    <button class="ui-btn ui-btn-primary w-full justify-center">Verify Token & Submit Withdrawal</button>
                </form>
            </section>

            <aside class="ui-panel p-6">
                <p class="ui-kicker">Final check</p>
                <h2 class="mt-1 text-lg font-semibold">What happens next?</h2>
                <div class="mt-5 space-y-4 text-sm text-muted-foreground">
                    <p>Once the token is accepted, the system creates the real pending withdrawal and reserves {{ format_currency($tokenRequest->amount) }}.</p>
                    <p>Admin can then approve or reject that withdrawal. Rejection releases the reservation.</p>
                    <p>The token is single-use. It becomes unusable as soon as the withdrawal request is created.</p>
                </div>
            </aside>
        </div>
    </div>
</x-user-layout>
