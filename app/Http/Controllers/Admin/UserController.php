<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MembershipAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(MembershipAccessService $membershipAccess)
    {
        $users = User::with(['purchases', 'wallet', 'investmentHoldings.investmentPlan', 'stockHoldings.stock', 'wallet.transactions', 'memberships.plan.type', 'memberships.plan.entitlements'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        // Calculate total revenue from all completed purchases
        $totalRevenue = \App\Models\Purchase::where('status', 'completed')->sum('amount');
        
        // Calculate total wallet balance across all users
        $totalWalletBalance = \App\Models\Wallet::sum('balance');
        
        // Calculate total investment value
        $totalInvestmentValue = \App\Models\InvestmentHolding::sum('current_value');
        
        // Calculate total stock value
        $totalStockValue = \App\Models\StockHolding::sum('current_value');
        $membershipStatusesByUser = $users->getCollection()->mapWithKeys(
            fn (User $customer) => [$customer->id => $membershipAccess->statusMemberships($customer)]
        );
        
        return view('admin.users.index', compact('users', 'totalRevenue', 'totalWalletBalance', 'totalInvestmentValue', 'totalStockValue', 'membershipStatusesByUser'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'user_type' => 'required|in:user,admin',
            'email_verified' => 'boolean',
            'is_admin' => 'boolean',
            'country' => 'nullable|string|max:80',
            'currency' => 'nullable|in:USD,EUR,GBP,NGN,JPY,AUD,CAD,CHF,CNY,INR,ZAR,SGD',
            'date_of_birth' => 'nullable|date|after_or_equal:1900-01-01|before_or_equal:'.now()->subYears(18)->toDateString(),
            'employment_class' => 'nullable|in:student,employed,self_employed,business_owner,professional,freelancer,unemployed,retired,other',
            'education_level' => 'nullable|in:secondary,diploma,undergraduate,bachelor,postgraduate,masters,doctorate,professional,other',
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => $request->user_type === 'admin' || $request->has('is_admin'),
            'country' => $request->country,
            'currency' => $request->currency ?? 'USD',
            'date_of_birth' => $request->date_of_birth,
            'employment_class' => $request->employment_class,
            'education_level' => $request->education_level,
            'account_status' => 'active',
        ];

        if ($request->has('email_verified') || !is_email_verification_enabled()) {
            $userData['email_verified_at'] = now();
        }

        $user = User::create($userData);

        $user->wallet()->create([
            'balance' => 0,
            'currency' => $request->currency ?? 'USD',
        ]);

        if (is_email_verification_enabled() && !$request->has('email_verified')) {
            $user->sendEmailVerificationNotification();
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user, MembershipAccessService $membershipAccess)
    {
        $user->load([
            'purchases.car', 
            'wallet.transactions.paymentMethod',
            'investmentHoldings.investmentPlan',
            'stockHoldings.stock',
            'investmentTransactions.investmentPlan',
            'stockTransactions.stock',
            'memberships.plan.type',
            'memberships.plan.entitlements'
        ]);
        
        $totalSpent = $user->purchases()->where('status', 'completed')->sum('amount');
        $totalInvestmentValue = $user->investmentHoldings->sum('current_value');
        $totalStockValue = $user->stockHoldings->sum('current_value');
        $walletBalance = $user->wallet ? $user->wallet->balance : 0;
        $membershipStatuses = $membershipAccess->statusMemberships($user);
        
        return view('admin.users.show', compact('user', 'totalSpent', 'totalInvestmentValue', 'totalStockValue', 'walletBalance', 'membershipStatuses'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'user_type' => 'required|in:user,admin',
            'is_admin' => 'boolean',
            'country' => 'nullable|string|max:80',
            'currency' => 'nullable|in:USD,EUR,GBP,NGN,JPY,AUD,CAD,CHF,CNY,INR,ZAR,SGD',
            'date_of_birth' => 'nullable|date|after_or_equal:1900-01-01|before_or_equal:'.now()->subYears(18)->toDateString(),
            'employment_class' => 'nullable|in:student,employed,self_employed,business_owner,professional,freelancer,unemployed,retired,other',
            'education_level' => 'nullable|in:secondary,diploma,undergraduate,bachelor,postgraduate,masters,doctorate,professional,other',
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'is_admin' => $request->user_type === 'admin' || $request->has('is_admin'),
            'country' => $request->country,
            'currency' => $request->currency ?? 'USD',
            'date_of_birth' => $request->date_of_birth,
            'employment_class' => $request->employment_class,
            'education_level' => $request->education_level,
        ];

        if ($request->filled('password')) $updateData['password'] = Hash::make($request->password);
        $user->update($updateData);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Fund user wallet
     */
    public function fundWallet(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        // Create wallet if it doesn't exist
        if (!$user->wallet) {
            $user->wallet()->create([
                'balance' => 0,
                'currency' => 'USD',
            ]);
            $user->refresh(); // Refresh to load the new wallet
        }

        $user->wallet->addFunds($request->amount);

        // Get a default payment method for admin transactions
        $defaultPaymentMethod = \App\Models\PaymentMethod::where('is_active', true)->first();
        
        if (!$defaultPaymentMethod) {
            return redirect()->route('admin.users.show', $user)
                ->with('error', 'No active payment methods found. Please configure payment methods first.');
        }

        // Create wallet transaction
        $user->wallet->transactions()->create([
            'payment_method_id' => $defaultPaymentMethod->id,
            'type' => 'deposit',
            'direction' => 'credit',
            'amount' => $request->amount,
            'status' => 'completed',
            'description' => $request->description ?: 'Admin funding',
            'reference_id' => 'ADMIN_' . time(),
        ]);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Wallet funded successfully.');
    }

    /**
     * Deduct from user wallet
     */
    public function deductWallet(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        if (!$user->wallet) {
            return redirect()->route('admin.users.show', $user)
                ->with('error', 'User has no wallet.');
        }

        if (!$user->wallet->canWithdraw($request->amount)) {
            return redirect()->route('admin.users.show', $user)
                ->with('error', 'Insufficient funds in wallet.');
        }

        $user->wallet->deductFunds($request->amount);

        // Get a default payment method for admin transactions
        $defaultPaymentMethod = \App\Models\PaymentMethod::where('is_active', true)->first();
        
        if (!$defaultPaymentMethod) {
            return redirect()->route('admin.users.show', $user)
                ->with('error', 'No active payment methods found. Please configure payment methods first.');
        }

        // Create wallet transaction
        $user->wallet->transactions()->create([
            'payment_method_id' => $defaultPaymentMethod->id,
            'type' => 'withdrawal',
            'direction' => 'debit',
            'amount' => $request->amount,
            'status' => 'completed',
            'description' => $request->description ?: 'Admin deduction',
            'reference_id' => 'ADMIN_' . time(),
        ]);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Amount deducted from wallet successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if ($user->purchases()->count() > 0) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Cannot delete user with existing purchases.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
