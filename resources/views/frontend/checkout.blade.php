@extends('layouts.main')

@section('title', 'Checkout - ' . $car->title)

@section('content')
<div class="min-h-screen bg-background">
    <div class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('cars.show', $car->id) }}" class="mb-3 inline-flex items-center gap-2 text-sm text-muted-foreground transition hover:text-foreground">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Back to vehicle
                </a>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">Vehicle checkout</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">Complete your order</h1>
                <p class="mt-2 max-w-2xl text-sm text-muted-foreground sm:text-base">Confirm your contact details, billing address and preferred payment method.</p>
            </div>
            <div class="hidden items-center gap-2 text-xs text-muted-foreground sm:flex">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-primary text-primary-foreground">1</span>
                <span>Details</span>
                <span class="h-px w-8 bg-border"></span>
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-border bg-card">2</span>
                <span>Payment</span>
                <span class="h-px w-8 bg-border"></span>
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-border bg-card">3</span>
                <span>Confirmation</span>
            </div>
        </div>

        <form action="{{ route('checkout.process', $car->id) }}" method="POST" id="checkout-form">
            @csrf

            <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_360px] xl:items-start">
                <div class="space-y-6">
                    <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
                        <div class="mb-6 flex items-start gap-3">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary text-sm font-semibold text-primary-foreground">1</span>
                            <div>
                                <h2 class="text-lg font-semibold tracking-tight text-foreground">Contact information</h2>
                                <p class="mt-1 text-sm text-muted-foreground">We’ll attach these details to your order record.</p>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="billing_name" class="ui-label">Full name</label>
                                <input id="billing_name" name="billing_name" type="text" value="{{ old('billing_name', auth()->user()->name) }}" class="ui-input @error('billing_name') border-red-500 @enderror" required>
                                @error('billing_name')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="billing_email" class="ui-label">Email address</label>
                                <input id="billing_email" name="billing_email" type="email" value="{{ old('billing_email', auth()->user()->email) }}" class="ui-input @error('billing_email') border-red-500 @enderror" required>
                                @error('billing_email')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="billing_phone" class="ui-label">Phone number</label>
                                <input id="billing_phone" name="billing_phone" type="tel" value="{{ old('billing_phone') }}" class="ui-input @error('billing_phone') border-red-500 @enderror" required>
                                @error('billing_phone')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="company_name" class="ui-label">Company <span class="font-normal text-muted-foreground">(optional)</span></label>
                                <input id="company_name" name="company_name" type="text" value="{{ old('company_name') }}" class="ui-input @error('company_name') border-red-500 @enderror">
                                @error('company_name')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
                        <div class="mb-6 flex items-start gap-3">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary text-sm font-semibold text-primary-foreground">2</span>
                            <div>
                                <h2 class="text-lg font-semibold tracking-tight text-foreground">Billing address</h2>
                                <p class="mt-1 text-sm text-muted-foreground">Provide the billing details associated with this purchase.</p>
                            </div>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label for="billing_address" class="ui-label">Street address</label>
                                <textarea id="billing_address" name="billing_address" rows="3" class="ui-input resize-none @error('billing_address') border-red-500 @enderror" required>{{ old('billing_address') }}</textarea>
                                @error('billing_address')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>

                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label for="billing_city" class="ui-label">City</label>
                                    <input id="billing_city" name="billing_city" type="text" value="{{ old('billing_city') }}" class="ui-input @error('billing_city') border-red-500 @enderror" required>
                                    @error('billing_city')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="billing_state" class="ui-label">State / province</label>
                                    <input id="billing_state" name="billing_state" type="text" value="{{ old('billing_state') }}" class="ui-input @error('billing_state') border-red-500 @enderror" required>
                                    @error('billing_state')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="billing_postal_code" class="ui-label">Postal code</label>
                                    <input id="billing_postal_code" name="billing_postal_code" type="text" value="{{ old('billing_postal_code') }}" class="ui-input @error('billing_postal_code') border-red-500 @enderror" required>
                                    @error('billing_postal_code')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="billing_country" class="ui-label">Country</label>
                                    <select id="billing_country" name="billing_country" class="ui-input @error('billing_country') border-red-500 @enderror" required>
                                        <option value="">Select country</option>
                                        <option value="US" {{ old('billing_country') == 'US' ? 'selected' : '' }}>United States</option>
                                        <option value="CA" {{ old('billing_country') == 'CA' ? 'selected' : '' }}>Canada</option>
                                        <option value="GB" {{ old('billing_country') == 'GB' ? 'selected' : '' }}>United Kingdom</option>
                                        <option value="DE" {{ old('billing_country') == 'DE' ? 'selected' : '' }}>Germany</option>
                                        <option value="FR" {{ old('billing_country') == 'FR' ? 'selected' : '' }}>France</option>
                                        <option value="AU" {{ old('billing_country') == 'AU' ? 'selected' : '' }}>Australia</option>
                                        <option value="NG" {{ old('billing_country') == 'NG' ? 'selected' : '' }}>Nigeria</option>
                                    </select>
                                    @error('billing_country')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div>
                                <label for="tax_id" class="ui-label">Tax ID / VAT number <span class="font-normal text-muted-foreground">(optional)</span></label>
                                <input id="tax_id" name="tax_id" type="text" value="{{ old('tax_id') }}" class="ui-input @error('tax_id') border-red-500 @enderror">
                                @error('tax_id')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
                        <div class="mb-6 flex items-start gap-3">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary text-sm font-semibold text-primary-foreground">3</span>
                            <div>
                                <h2 class="text-lg font-semibold tracking-tight text-foreground">Payment method</h2>
                                <p class="mt-1 text-sm text-muted-foreground">Choose how you want this order processed.</p>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach($paymentMethods as $method)
                                <label class="group relative cursor-pointer">
                                    <input type="radio" name="payment_method_id" value="{{ $method->id }}" class="peer sr-only payment-method-radio" {{ old('payment_method_id') == $method->id ? 'checked' : '' }} required>
                                    <div class="payment-method-card flex min-h-[92px] items-center gap-3 rounded-xl border border-border bg-background p-4 transition hover:border-foreground/30 peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-1 peer-checked:ring-primary/20">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-border bg-card">
                                            @if($method->logo_url)
                                                <img src="{{ $method->logo_url }}" alt="{{ $method->name }}" class="h-6 w-6 object-contain">
                                            @elseif($method->isCryptocurrency())
                                                <i data-lucide="bitcoin" class="h-5 w-5 text-orange-500"></i>
                                            @else
                                                <i data-lucide="credit-card" class="h-5 w-5 text-muted-foreground"></i>
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="truncate text-sm font-medium text-foreground">{{ $method->name }}</span>
                                                <span class="h-4 w-4 shrink-0 rounded-full border border-border p-[3px] peer-checked:border-primary">
                                                    <span class="block h-full w-full rounded-full bg-primary opacity-0 transition peer-checked:opacity-100"></span>
                                                </span>
                                            </div>
                                            @if($method->details)
                                                <p class="mt-1 line-clamp-2 text-xs leading-5 text-muted-foreground">{{ $method->details }}</p>
                                            @endif
                                            @if($method->isCryptocurrency() && $method->formatted_network_fee)
                                                <p class="mt-1 text-[11px] text-muted-foreground">Network fee: {{ $method->formatted_network_fee }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('payment_method_id')<p class="mt-2 text-xs text-red-500">{{ $message }}</p>@enderror
                    </section>

                    <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
                        <label class="flex cursor-pointer items-start gap-3" for="terms_accepted">
                            <input type="checkbox" id="terms_accepted" name="terms_accepted" class="mt-0.5 h-4 w-4 rounded border-border text-primary focus:ring-primary" required>
                            <span class="text-sm leading-6 text-muted-foreground">
                                I agree to the <a href="{{ route('terms') }}" class="font-medium text-foreground underline-offset-4 hover:underline">Terms of Service</a> and <a href="{{ route('privacy') }}" class="font-medium text-foreground underline-offset-4 hover:underline">Privacy Policy</a>.
                            </span>
                        </label>
                    </section>

                    <div class="xl:hidden">
                        <button type="submit" id="mobile-submit-btn" class="ui-button-primary w-full py-3.5">
                            <span class="submit-text">Complete purchase · {{ $car->formatted_price }}</span>
                            <span class="loading-text hidden">Processing…</span>
                        </button>
                    </div>
                </div>

                <aside class="xl:sticky xl:top-24">
                    <div class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                        <div class="border-b border-border p-5 sm:p-6">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">Order summary</p>
                            <div class="mt-4 flex gap-4">
                                <div class="h-20 w-24 shrink-0 overflow-hidden rounded-xl bg-muted">
                                    @if($car->first_image)
                                        <img src="{{ strpos($car->first_image, 'http') === 0 ? $car->first_image : asset('storage/' . $car->first_image) }}" alt="{{ $car->title }}" class="h-full w-full object-cover">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center"><i data-lucide="car" class="h-6 w-6 text-muted-foreground"></i></div>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <h2 class="line-clamp-2 text-sm font-semibold leading-5 text-foreground">{{ $car->title }}</h2>
                                    <p class="mt-1 text-xs text-muted-foreground">{{ $car->year }} {{ $car->make }} {{ $car->model }}</p>
                                    @if($car->color)<p class="mt-1 text-xs text-muted-foreground">{{ $car->color }}</p>@endif
                                </div>
                            </div>
                        </div>

                        <div class="space-y-5 p-5 sm:p-6">
                            <div class="grid grid-cols-2 gap-2 text-xs text-muted-foreground">
                                <div class="rounded-lg bg-muted/50 p-3"><span class="block font-medium text-foreground">{{ $car->engine_range ?? '410' }} mi</span><span>Range</span></div>
                                <div class="rounded-lg bg-muted/50 p-3"><span class="block font-medium text-foreground">{{ $car->acceleration ?? '3.1' }}s</span><span>0–60 mph</span></div>
                            </div>

                            <div class="space-y-3 border-t border-border pt-5 text-sm">
                                <div class="flex items-center justify-between gap-4"><span class="text-muted-foreground">Vehicle price</span><span class="font-medium text-foreground">{{ $car->formatted_price }}</span></div>
                                <div class="flex items-center justify-between gap-4"><span class="text-muted-foreground">Order fee</span><span class="font-medium text-foreground">{{ currency_symbol() }}0</span></div>
                                <div class="flex items-center justify-between gap-4"><span class="text-muted-foreground">Taxes & fees</span><span class="text-xs text-muted-foreground">Due at delivery</span></div>
                                <div class="flex items-center justify-between gap-4 border-t border-border pt-4"><span class="font-semibold text-foreground">Order total</span><span class="text-lg font-semibold tracking-tight text-foreground">{{ $car->formatted_price }}</span></div>
                            </div>

                            <div class="space-y-2 border-t border-border pt-5 text-xs text-muted-foreground">
                                <div class="flex items-center gap-2"><i data-lucide="shield-check" class="h-4 w-4 text-emerald-500"></i><span>Secure checkout session</span></div>
                                <div class="flex items-center gap-2"><i data-lucide="file-check-2" class="h-4 w-4 text-emerald-500"></i><span>Order details recorded to your account</span></div>
                                <div class="flex items-center gap-2"><i data-lucide="bell" class="h-4 w-4 text-emerald-500"></i><span>Status updates available from your dashboard</span></div>
                            </div>

                            <button type="submit" id="desktop-submit-btn" class="ui-button-primary hidden w-full py-3.5 xl:inline-flex">
                                <span class="submit-text">Complete purchase</span>
                                <span class="loading-text hidden">Processing…</span>
                            </button>
                        </div>
                    </div>
                </aside>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('checkout-form');
    if (!form) return;

    const submitButtons = document.querySelectorAll('#mobile-submit-btn, #desktop-submit-btn');
    form.addEventListener('submit', function () {
        submitButtons.forEach((button) => {
            button.disabled = true;
            button.querySelector('.submit-text')?.classList.add('hidden');
            button.querySelector('.loading-text')?.classList.remove('hidden');
        });
    });
});
</script>
@endsection
