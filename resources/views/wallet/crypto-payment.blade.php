<x-user-layout>
    <x-slot name="header">
        Confirm Crypto Deposit
    </x-slot>

    <div class="money-page max-w-4xl">
        <div class="ui-panel p-5 sm:p-6">
            <h2 class="text-lg font-semibold mb-4">Deposit Details</h2>

            <div class="grid grid-cols-1 gap-4 mb-6">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">Amount</span>
                    <span class="text-sm font-medium">{{ format_currency($transaction->amount) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">Payment Method</span>
                    <span class="text-sm font-medium flex items-center space-x-2">
                        @if($paymentMethod->logo_url)
                            <img src="{{ $paymentMethod->logo_url }}" alt="{{ $paymentMethod->name }}" class="w-6 h-6 rounded" />
                        @else
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-muted text-muted-foreground text-xs">PM</span>
                        @endif
                        <span>{{ $paymentMethod->name }}</span>
                    </span>
                </div>
                @if($paymentMethod->crypto_symbol)
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">Crypto</span>
                    <span class="text-sm font-medium">{{ strtoupper($paymentMethod->crypto_symbol) }}</span>
                </div>
                @endif
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-foreground mb-2">Wallet Address</label>
                <div class="flex items-center justify-between p-3 bg-muted rounded-lg border border-border">
                    <code class="text-xs break-all">{{ $paymentMethod->wallet_address }}</code>
                    <button type="button" class="ml-3 text-xs px-2 py-1 bg-foreground text-background rounded copy-address" data-address="{{ $paymentMethod->wallet_address }}">Copy</button>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-foreground mb-2">Scan QR</label>
                @php
                    $qrSource = $paymentMethod->barcode
                        ? (\Illuminate\Support\Str::startsWith($paymentMethod->barcode, ['data:', 'http', 'https']) ? $paymentMethod->barcode : \Illuminate\Support\Facades\Storage::url($paymentMethod->barcode))
                        : 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($paymentMethod->wallet_address ?? '');
                @endphp
                <div class="flex items-center justify-center">
                    <img src="{{ $qrSource }}" alt="Wallet QR Code" class="w-40 h-40 object-contain border rounded p-2 bg-card" />
                </div>
            </div>

            <form action="{{ route('wallet.crypto-payment.confirm', $transaction) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="transaction_id" class="block text-sm font-medium text-foreground mb-2">Transaction ID / Hash</label>
                    <input id="transaction_id" name="transaction_id" type="text" value="{{ old('transaction_id', $transaction->reference_id) }}" class="w-full border border-border rounded-lg px-3 py-2 focus:ring-2 focus:ring-ring focus:border-transparent" placeholder="Paste your blockchain tx hash" required />
                    @error('transaction_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-foreground text-background py-3 px-6 rounded-lg font-medium hover:opacity-90 transition-colors duration-200">
                        Submit for Verification
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.querySelectorAll('.copy-address').forEach((btn) => {
            btn.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(btn.dataset.address);
                    btn.textContent = 'Copied';
                    setTimeout(() => btn.textContent = 'Copy', 1500);
                } catch (_) {}
            });
        });
    </script>
</x-user-layout>


