<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $paymentMethods = PaymentMethod::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.payment_methods.index', compact('paymentMethods'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.payment_methods.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255|unique:payment_methods',
            'type' => 'required|in:traditional,cryptocurrency',
            'details' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'boolean',
            'allow_deposit' => 'boolean',
            'allow_withdraw' => 'boolean',
        ];

        // Add cryptocurrency-specific validation rules
        if ($request->type === 'cryptocurrency') {
            $rules = array_merge($rules, [
                'crypto_symbol' => 'required|string|max:10',
                'wallet_address' => 'required|string|max:255',
                'network_fee' => 'nullable|numeric|min:0',
                'barcode' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
        }

        $request->validate($rules);

        $data = [
            'name' => $request->name,
            'type' => $request->type,
            'details' => $request->details,
            'is_active' => $request->has('is_active'),
            'allow_deposit' => $request->has('allow_deposit'),
            'allow_withdraw' => $request->has('allow_withdraw'),
        ];

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('payment-methods/logos', 'public');
            $data['logo'] = $logoPath;
        }

        // Handle cryptocurrency fields
        if ($request->type === 'cryptocurrency') {
            $data['crypto_symbol'] = strtoupper($request->crypto_symbol);
            $data['wallet_address'] = $request->wallet_address;
            $data['network_fee'] = $request->network_fee;

            // Handle QR code/barcode upload
            if ($request->hasFile('barcode')) {
                $barcodePath = $request->file('barcode')->store('payment-methods/qrcodes', 'public');
                $data['barcode'] = $barcodePath;
            }
        }

        PaymentMethod::create($data);

        return redirect()->route('admin.payment_methods.index')
            ->with('success', 'Payment method created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(PaymentMethod $paymentMethod)
    {
        $paymentMethod->load('purchases.user', 'purchases.car');
        $totalTransactions = $paymentMethod->purchases()->count();
        $totalAmount = $paymentMethod->purchases()->where('status', 'completed')->sum('amount');
        
        return view('admin.payment_methods.show', compact('paymentMethod', 'totalTransactions', 'totalAmount'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PaymentMethod $paymentMethod)
    {
        return view('admin.payment_methods.edit', compact('paymentMethod'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $rules = [
            'name' => 'required|string|max:255|unique:payment_methods,name,' . $paymentMethod->id,
            'type' => 'required|in:traditional,cryptocurrency',
            'details' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'boolean',
            'allow_deposit' => 'boolean',
            'allow_withdraw' => 'boolean',
        ];

        // Add cryptocurrency-specific validation rules
        if ($request->type === 'cryptocurrency') {
            $rules = array_merge($rules, [
                'crypto_symbol' => 'required|string|max:10',
                'wallet_address' => 'required|string|max:255',
                'network_fee' => 'nullable|numeric|min:0',
                'barcode' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
        }

        $request->validate($rules);

        $data = [
            'name' => $request->name,
            'type' => $request->type,
            'details' => $request->details,
            'is_active' => $request->has('is_active'),
            'allow_deposit' => $request->has('allow_deposit'),
            'allow_withdraw' => $request->has('allow_withdraw'),
        ];

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($paymentMethod->logo && Storage::disk('public')->exists($paymentMethod->logo)) {
                Storage::disk('public')->delete($paymentMethod->logo);
            }
            
            $logoPath = $request->file('logo')->store('payment-methods/logos', 'public');
            $data['logo'] = $logoPath;
        }

        // Handle cryptocurrency fields
        if ($request->type === 'cryptocurrency') {
            $data['crypto_symbol'] = strtoupper($request->crypto_symbol);
            $data['wallet_address'] = $request->wallet_address;
            $data['network_fee'] = $request->network_fee;

            // Handle QR code/barcode upload
            if ($request->hasFile('barcode')) {
                // Delete old barcode if exists
                if ($paymentMethod->barcode && Storage::disk('public')->exists($paymentMethod->barcode)) {
                    Storage::disk('public')->delete($paymentMethod->barcode);
                }
                
                $barcodePath = $request->file('barcode')->store('payment-methods/qrcodes', 'public');
                $data['barcode'] = $barcodePath;
            }
        } else {
            // If changing from crypto to traditional, clear crypto fields
            $data['crypto_symbol'] = null;
            $data['wallet_address'] = null;
            $data['network_fee'] = null;
            
            // Delete barcode file if exists
            if ($paymentMethod->barcode && Storage::disk('public')->exists($paymentMethod->barcode)) {
                Storage::disk('public')->delete($paymentMethod->barcode);
            }
            $data['barcode'] = null;
        }

        $paymentMethod->update($data);

        return redirect()->route('admin.payment_methods.index')
            ->with('success', 'Payment method updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentMethod $paymentMethod)
    {
        if ($paymentMethod->purchases()->count() > 0) {
            return redirect()->route('admin.payment_methods.index')
                ->with('error', 'Cannot delete payment method with existing transactions.');
        }

        // Delete associated files
        if ($paymentMethod->logo && Storage::disk('public')->exists($paymentMethod->logo)) {
            Storage::disk('public')->delete($paymentMethod->logo);
        }
        
        if ($paymentMethod->barcode && Storage::disk('public')->exists($paymentMethod->barcode)) {
            Storage::disk('public')->delete($paymentMethod->barcode);
        }

        $paymentMethod->delete();

        return redirect()->route('admin.payment_methods.index')
            ->with('success', 'Payment method deleted successfully.');
    }
}
