<x-user-layout>
    <x-slot name="header">Crypto Deposit</x-slot>

    <div class="ui-page max-w-5xl">
        <section class="ui-page-header">
            <div>
                <p class="ui-kicker">Money · Add money</p>
                <h1 class="ui-heading">Complete crypto deposit</h1>
                <p class="ui-lead">Send the exact transfer to the address below, then submit the blockchain transaction hash for verification.</p>
            </div>
            <div class="ui-header-actions">
                <a href="{{ route('money.add') }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Add money
                </a>
                <a href="{{ route('money.activity') }}" class="ui-btn ui-btn-secondary">
                    <i data-lucide="history" class="h-4 w-4"></i>
                    Activity
                </a>
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-[minmax(0,1.15fr)_minmax(300px,.85fr)]">
            <article class="ui-panel overflow-hidden">
                <div class="border-b border-border px-5 py-4 sm:px-6">
                    <p class="ui-kicker">Transfer details</p>
                    <h2 class="text-lg font-semibold text-foreground">Send your deposit</h2>
                </div>

                <div class="space-y-5 p-5 sm:p-6">
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-border bg-muted/30 p-4">
                            <p class="ui-label">Amount</p>
                            <p class="mt-1 text-lg font-semibold text-foreground">{{ format_currency($transaction->amount) }}</p>
                        </div>
                        <div class="rounded-xl border border-border bg-muted/30 p-4">
                            <p class="ui-label">Method</p>
                            <div class="mt-1 flex items-center gap-2">
                                @if($paymentMethod->logo_url)
                                    <img src="{{ $paymentMethod->logo_url }}" alt="" class="h-6 w-6 rounded-md object-contain" />
                                @endif
                                <p class="truncate text-sm font-semibold text-foreground">{{ $paymentMethod->name }}</p>
                            </div>
                        </div>
                        <div class="rounded-xl border border-border bg-muted/30 p-4">
                            <p class="ui-label">Asset</p>
                            <p class="mt-1 text-lg font-semibold uppercase text-foreground">{{ $paymentMethod->crypto_symbol ?: 'Crypto' }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="ui-label">Deposit address</label>
                        <div class="flex items-start gap-3 rounded-xl border border-border bg-background p-4">
                            <code class="min-w-0 flex-1 break-all text-xs leading-5 text-foreground">{{ $paymentMethod->wallet_address }}</code>
                            <button type="button" class="copy-address ui-btn ui-btn-secondary ui-btn-sm shrink-0" data-address="{{ $paymentMethod->wallet_address }}">
                                <i data-lucide="copy" class="h-3.5 w-3.5"></i>
                                <span>Copy</span>
                            </button>
                        </div>
                    </div>

                    @php
                        $qrSource = $paymentMethod->barcode
                            ? (\Illuminate\Support\Str::startsWith($paymentMethod->barcode, ['data:', 'http', 'https']) ? $paymentMethod->barcode : \Illuminate\Support\Facades\Storage::url($paymentMethod->barcode))
                            : 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($paymentMethod->wallet_address ?? '');
                    @endphp

                    <div class="rounded-xl border border-border bg-muted/20 p-5 text-center">
                        <p class="ui-label">Scan address</p>
                        <div class="mt-3 inline-flex rounded-xl border border-border bg-white p-3">
                            <img src="{{ $qrSource }}" alt="Deposit address QR code" class="h-40 w-40 object-contain" />
                        </div>
                    </div>

                    <div class="flex gap-3 rounded-xl border border-amber-500/20 bg-amber-500/10 p-4 text-xs leading-5 text-muted-foreground">
                        <i data-lucide="triangle-alert" class="mt-0.5 h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400"></i>
                        <p>Confirm the asset and network before sending. Your account is credited only after the submitted transfer is reviewed.</p>
                    </div>
                </div>
            </article>

            <aside class="ui-panel overflow-hidden">
                <div class="border-b border-border px-5 py-4">
                    <p class="ui-kicker">Verification</p>
                    <h2 class="text-lg font-semibold text-foreground">Submit transfer hash</h2>
                    <p class="mt-1 text-xs text-muted-foreground">Paste the transaction ID from your wallet or exchange after sending.</p>
                </div>

                <form action="{{ route('money.add.crypto.confirm', $transaction) }}" method="POST" class="space-y-5 p-5">
                    @csrf
                    <div>
                        <label for="transaction_id" class="ui-label">Transaction ID / hash</label>
                        <textarea id="transaction_id" name="transaction_id" rows="4" class="ui-input min-h-28 resize-y font-mono text-xs" placeholder="Paste blockchain transaction hash" required>{{ old('transaction_id') }}</textarea>
                        @error('transaction_id')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="rounded-xl border border-border bg-muted/20 p-4 text-xs leading-5 text-muted-foreground">
                        <strong class="block text-foreground">Reference</strong>
                        <span class="mt-1 block break-all font-mono">{{ $transaction->reference_id }}</span>
                    </div>

                    <button type="submit" class="ui-btn ui-btn-primary w-full">
                        Submit for verification
                        <i data-lucide="arrow-right" class="h-4 w-4"></i>
                    </button>
                </form>
            </aside>
        </section>
    </div>

    <script>
        document.querySelectorAll('.copy-address').forEach((button) => {
            button.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(button.dataset.address);
                    const label = button.querySelector('span');
                    if (label) label.textContent = 'Copied';
                    setTimeout(() => { if (label) label.textContent = 'Copy'; }, 1500);
                } catch (_) {}
            });
        });
    </script>
</x-user-layout>
