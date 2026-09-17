<x-user-layout>
    <x-slot name="header">Verify Withdrawal</x-slot>

    <div class="ui-page max-w-5xl">
        <section class="ui-page-header">
            <div>
                <p class="ui-kicker">Money · Withdrawal verification</p>
                <h1 class="ui-heading">Confirm payout details</h1>
                <p class="ui-lead max-w-2xl">Enter the verification code from your private account alert, then choose where the payout should be sent.</p>
            </div>
            <a href="{{ route('money.withdraw') }}" class="ui-btn ui-btn-secondary"><i data-lucide="arrow-left" class="h-4 w-4"></i>Back to withdrawal</a>
        </section>

        <section class="grid gap-4 lg:grid-cols-[minmax(0,1.15fr)_minmax(300px,.85fr)]">
            <article class="ui-surface overflow-hidden">
                <div class="border-b border-border px-5 py-4">
                    <p class="ui-kicker">Secure verification</p>
                    <h2 class="mt-1 text-lg font-semibold">{{ format_currency($tokenRequest->amount) }} withdrawal</h2>
                </div>

                <form method="POST" action="{{ route('money.withdraw.verify.submit', $tokenRequest) }}" class="space-y-5 p-5">
                    @csrf

                    <div>
                        <label class="ui-label">6-digit verification code</label>
                        <input class="ui-input mt-1 w-full text-center font-mono text-2xl tracking-[.4em]"
                               name="token" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required>
                        <p class="mt-2 text-[10px] text-muted-foreground">
                            Code ending ••••{{ $tokenRequest->token_last_four }} · expires {{ $tokenRequest->token_expires_at?->format('M j, Y · h:i A') }}
                        </p>
                    </div>

                    <div>
                        <label class="ui-label">Payout method</label>
                        <div class="mt-2 grid gap-2">
                            @forelse($paymentMethods as $method)
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-border p-4 transition hover:bg-muted/30">
                                    <input type="radio" name="payment_method_id" value="{{ $method->id }}" required>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold">{{ $method->name }}</p>
                                        <p class="mt-1 text-xs text-muted-foreground">{{ $method->description ?: 'Available payout method' }}</p>
                                    </div>
                                </label>
                            @empty
                                <div class="rounded-xl border border-border bg-muted/20 p-4 text-sm text-muted-foreground">No withdrawal method is currently available.</div>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <label class="ui-label">Destination</label>
                        <input class="ui-input mt-1 w-full"
                               name="wallet_address"
                               value="{{ old('wallet_address') }}"
                               placeholder="Wallet address, account destination or payout reference">
                        <p class="mt-1 text-[10px] text-muted-foreground">Required for cryptocurrency withdrawals. For other methods, enter any destination details requested by the platform.</p>
                    </div>

                    <button class="ui-btn ui-btn-primary w-full justify-center">
                        <i data-lucide="lock-keyhole" class="h-4 w-4"></i>
                        Verify & submit payout
                    </button>
                </form>
            </article>

            <aside class="ui-panel p-5">
                <p class="ui-kicker">What happens next</p>
                <h2 class="mt-1 text-lg font-semibold">Funds are reserved after verification</h2>
                <div class="mt-5 space-y-4 text-sm text-muted-foreground">
                    <p>The verification code is single-use and expires automatically.</p>
                    <p>Once verification succeeds, the withdrawal becomes a real pending payout and the requested amount is reserved.</p>
                    <p>An administrator can then approve or decline the payout. A decline releases the reserved amount.</p>
                </div>
            </aside>
        </section>
    </div>
</x-user-layout>
