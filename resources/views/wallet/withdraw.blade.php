<x-user-layout>
    <x-slot name="header">Withdraw Funds</x-slot>

    <div class="ui-page max-w-5xl">
        <section class="ui-page-header">
            <div>
                <p class="ui-kicker">Secure withdrawals</p>
                <h1 class="ui-heading">Request withdrawal access</h1>
                <p class="ui-lead">Start with the amount. Admin will issue a short-lived verification token before payout details are requested.</p>
            </div>
            <a href="{{ route('wallet.index') }}" class="ui-btn ui-btn-secondary"><i data-lucide="wallet" class="h-4 w-4"></i>Wallet</a>
        </section>

        @if(session('success'))<div class="mb-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm text-emerald-700">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="mb-4 rounded-xl border border-red-500/20 bg-red-500/10 p-4 text-sm text-red-600">{{ $errors->first() }}</div>@endif

        <section class="grid gap-4 sm:grid-cols-3">
            <div class="ui-metric-card"><div><p class="ui-label">Available</p><p class="mt-2 text-2xl font-semibold">{{ format_currency($wallet->available_balance) }}</p></div></div>
            <div class="ui-metric-card"><div><p class="ui-label">Reserved</p><p class="mt-2 text-2xl font-semibold">{{ format_currency($wallet->reserved_balance) }}</p></div></div>
            <div class="ui-metric-card"><div><p class="ui-label">Security gate</p><p class="mt-2 text-sm font-semibold">Token required</p><p class="mt-1 text-xs text-muted-foreground">No payout request exists before verification.</p></div></div>
        </section>

        <section class="mt-4 grid gap-4 lg:grid-cols-[1.05fr_.95fr]">
            <article class="ui-panel p-6">
                <div class="flex items-start gap-3">
                    <div class="ui-metric-icon"><i data-lucide="key-round" class="h-5 w-5"></i></div>
                    <div><p class="ui-kicker">Step 1</p><h2 class="text-lg font-semibold">Request a withdrawal token</h2><p class="mt-1 text-sm text-muted-foreground">This does not reserve funds and does not create a withdrawal transaction.</p></div>
                </div>

                <form method="POST" action="{{ route('wallet.withdrawal.token.request') }}" class="mt-6 space-y-5">
                    @csrf
                    <div>
                        <label class="ui-label">Amount you intend to withdraw</label>
                        <div class="relative mt-1">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">{{ currency_symbol() }}</span>
                            <input class="ui-input w-full pl-8" name="amount" type="number" min="1" step="0.01" max="{{ $wallet->available_balance }}" required>
                        </div>
                    </div>
                    <div>
                        <label class="ui-label">Withdrawal note <span class="font-normal text-muted-foreground">(optional)</span></label>
                        <textarea class="ui-input mt-1 w-full" name="note" rows="4" maxlength="1000" placeholder="Optional context for Admin."></textarea>
                    </div>
                    <button class="ui-btn ui-btn-primary w-full justify-center">Request Verification Token</button>
                </form>
            </article>

            <aside class="ui-panel p-6">
                <p class="ui-kicker">How it works</p>
                <h2 class="mt-1 text-lg font-semibold">No token, no withdrawal</h2>
                <div class="mt-5 space-y-4 text-sm">
                    @foreach([
                        ['1','Request a token','Choose the amount and optionally add a note.'],
                        ['2','Admin generates token','The code appears in your private dashboard alert window for 30 minutes.'],
                        ['3','Complete payout details','Enter the token, choose method and add destination details.'],
                        ['4','Admin approves withdrawal','Only now does a real pending withdrawal exist and funds become reserved.'],
                    ] as $item)
                        <div class="flex gap-3"><div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-foreground text-xs font-semibold text-background">{{ $item[0] }}</div><div><p class="font-semibold">{{ $item[1] }}</p><p class="mt-1 text-xs leading-5 text-muted-foreground">{{ $item[2] }}</p></div></div>
                    @endforeach
                </div>

                @if($activeTokenRequest)
                    <div class="mt-6 rounded-xl border border-border bg-muted/30 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[.12em] text-muted-foreground">Current request</p>
                        <p class="mt-2 font-semibold">{{ format_currency($activeTokenRequest->amount) }} · {{ ucwords(str_replace('_',' ',$activeTokenRequest->status)) }}</p>
                        @if($activeTokenRequest->status === 'token_issued')
                            <a href="{{ route('wallet.withdrawal.token.form',$activeTokenRequest) }}" class="ui-btn ui-btn-primary ui-btn-sm mt-4">Continue Withdrawal</a>
                        @else
                            <p class="mt-2 text-xs text-muted-foreground">Waiting for Admin to generate your token.</p>
                        @endif
                    </div>
                @endif
            </aside>
        </section>
    </div>
</x-user-layout>
