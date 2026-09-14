<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\PaymentMethod;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function checkoutForm($car_id)
    {
        $car = Car::where('is_available', true)->findOrFail($car_id);
        $paymentMethods = PaymentMethod::active()->allowDeposit()->get();

        return view('frontend.checkout', compact('car', 'paymentMethods'));
    }

    public function processCheckout(Request $request, $car_id)
    {
        $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
            'billing_name' => 'required|string|max:255',
            'billing_email' => 'required|email',
            'billing_phone' => 'required|string|max:20',
            'billing_address' => 'required|string|max:500',
            'billing_city' => 'nullable|string|max:100',
            'billing_state' => 'nullable|string|max:100',
            'billing_postal_code' => 'nullable|string|max:20',
            'billing_country' => 'nullable|string|max:100',
            'company_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            // Cryptocurrency fields (optional, will be filled later for crypto payments)
            'transaction_hash' => 'nullable|string|max:255',
            'crypto_amount' => 'nullable|numeric|min:0',
            'crypto_currency' => 'nullable|string|max:10',
        ]);

        $car = Car::where('is_available', true)->findOrFail($car_id);
        $paymentMethod = PaymentMethod::findOrFail($request->payment_method_id);
        
        if (!$car->is_available) {
            return redirect()->back()->with('error', 'This car is no longer available.');
        }

        try {
            DB::beginTransaction();

            // Prepare purchase data
            $purchaseData = [
                'user_id' => Auth::id(),
                'car_id' => $car->id,
                'payment_method_id' => $request->payment_method_id,
                'amount' => $car->price,
                'billing_name' => $request->billing_name,
                'billing_email' => $request->billing_email,
                'billing_phone' => $request->billing_phone,
                'billing_address' => $request->billing_address,
                'billing_city' => $request->billing_city,
                'billing_state' => $request->billing_state,
                'billing_postal_code' => $request->billing_postal_code,
                'billing_country' => $request->billing_country,
                'company_name' => $request->company_name,
                'tax_id' => $request->tax_id,
                'purchased_at' => now(),
            ];

            // Handle cryptocurrency payments
            if ($paymentMethod->isCryptocurrency()) {
                $purchaseData['status'] = 'pending'; // Crypto payments start as pending
                $purchaseData['crypto_currency'] = $paymentMethod->crypto_symbol;
                
                // For demo purposes, we'll calculate a mock crypto amount
                // In a real application, you'd integrate with a crypto price API
                $mockExchangeRate = $this->getMockExchangeRate($paymentMethod->crypto_symbol);
                $purchaseData['crypto_amount'] = $car->price / $mockExchangeRate;
                $purchaseData['exchange_rate'] = $mockExchangeRate;
                
                if ($request->transaction_hash) {
                    $purchaseData['transaction_hash'] = $request->transaction_hash;
                }
            } else {
                $purchaseData['status'] = 'processing'; // Traditional payments start as processing
            }

            // Create the purchase
            $purchase = Purchase::create($purchaseData);

            // Mark car as sold
            $car->update(['is_available' => false]);

            DB::commit();

            // Redirect based on payment type
            if ($paymentMethod->isCryptocurrency()) {
                return redirect()->route('checkout.crypto-payment', $purchase->id)
                    ->with('success', 'Please complete your cryptocurrency payment to finalize the purchase.');
            } else {
                return redirect()->route('dashboard')
                    ->with('success', 'Your purchase has been processed successfully! You will receive a confirmation email shortly.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'There was an error processing your purchase. Please try again.')
                ->withInput();
        }
    }

    public function cryptoPayment($purchase_id)
    {
        $purchase = Purchase::with(['car', 'paymentMethod', 'user'])
            ->where('user_id', Auth::id())
            ->findOrFail($purchase_id);

        if (!$purchase->paymentMethod->isCryptocurrency()) {
            return redirect()->route('dashboard')->with('error', 'Invalid payment method.');
        }

        return view('frontend.crypto-payment', compact('purchase'));
    }

    public function confirmCryptoPayment(Request $request, $purchase_id)
    {
        $request->validate([
            'transaction_hash' => 'required|string|max:255',
        ]);

        $purchase = Purchase::where('user_id', Auth::id())->findOrFail($purchase_id);

        if ($purchase->status !== 'pending') {
            return redirect()->route('dashboard')->with('error', 'This purchase has already been processed.');
        }

        $purchase->update([
            'transaction_hash' => $request->transaction_hash,
            'status' => 'processing', // Admin will verify and mark as completed
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Thank you! Your cryptocurrency payment has been submitted for verification. You will receive confirmation once the transaction is verified on the blockchain.');
    }

    /**
     * Mock function to get cryptocurrency exchange rates
     * In a real application, integrate with APIs like CoinGecko, CoinMarketCap, etc.
     */
    private function getMockExchangeRate($cryptoSymbol)
    {
        $mockRates = [
            'BTC' => 45000,  // 1 BTC = $45,000
            'ETH' => 3000,   // 1 ETH = $3,000
            'LTC' => 100,    // 1 LTC = $100
            'ADA' => 0.5,    // 1 ADA = $0.50
            'DOT' => 25,     // 1 DOT = $25
        ];

        return $mockRates[strtoupper($cryptoSymbol)] ?? 1;
    }
}
