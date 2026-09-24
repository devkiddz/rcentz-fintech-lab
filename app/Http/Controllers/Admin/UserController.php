<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MembershipAccessService;
use App\Services\FinancialActivityService;
use App\Support\ProductionDemoGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
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
    public function update(Request $request, User $user, ProductionDemoGuard $productionDemo)
    {
        $productionDemo->assertMutationAllowed($user, 'administrator account update');
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
    public function fundWallet(
        Request $request,
        User $user,
        ProductionDemoGuard $productionDemo,
        FinancialActivityService $activity
    ) {
        $productionDemo->assertMutationAllowed(
            $user,
            'administrator wallet funding'
        );

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $paymentMethod = \App\Models\PaymentMethod::query()
            ->where('is_active', true)
            ->first();

        if (! $paymentMethod) {
            return redirect()->route('admin.users.show', $user)
                ->with('error', 'No active payment methods found. Please configure payment methods first.');
        }

        DB::transaction(function () use (
            $user,
            $data,
            $paymentMethod,
            $activity
        ): void {
            \App\Models\Wallet::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'reserved_balance' => 0, 'currency' => 'USD']
            );

            $wallet = \App\Models\Wallet::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $before = $activity->snapshot($wallet);
            $reference = $activity->reference('ADM-FUND');

            $wallet->addFunds((float) $data['amount']);

            $transaction = $wallet->transactions()->create([
                'payment_method_id' => $paymentMethod->id,
                'type' => 'deposit',
                'direction' => 'credit',
                'amount' => $data['amount'],
                'fee' => 0,
                'status' => 'completed',
                'description' => $data['description'] ?: 'Admin funding',
                'reference_id' => $reference,
            ]);

            $activity->record(
                $user,
                'admin.wallet_fund',
                'Wallet funded by administrator',
                $data['description'] ?: 'Administrator credited customer wallet.',
                $reference,
                'completed',
                'credit',
                (float) $data['amount'],
                $wallet,
                $transaction,
                null,
                $before,
                ['payment_method_id' => $paymentMethod->id],
                'admin',
                auth()->id()
            );
        });

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Wallet funded successfully.');
    }

    public function deductWallet(
        Request $request,
        User $user,
        ProductionDemoGuard $productionDemo,
        FinancialActivityService $activity
    ) {
        $productionDemo->assertMutationAllowed(
            $user,
            'administrator wallet deduction'
        );

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $paymentMethod = \App\Models\PaymentMethod::query()
            ->where('is_active', true)
            ->first();

        if (! $paymentMethod) {
            return redirect()->route('admin.users.show', $user)
                ->with('error', 'No active payment methods found. Please configure payment methods first.');
        }

        try {
            DB::transaction(function () use (
                $user,
                $data,
                $paymentMethod,
                $activity
            ): void {
                $wallet = \App\Models\Wallet::query()
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if (! $wallet) {
                    throw new \RuntimeException('User has no wallet.');
                }

                if (! $wallet->canWithdraw((float) $data['amount'])) {
                    throw new \RuntimeException('Insufficient available balance.');
                }

                $before = $activity->snapshot($wallet);
                $reference = $activity->reference('ADM-DEBIT');

                $wallet->deductFunds((float) $data['amount']);

                $transaction = $wallet->transactions()->create([
                    'payment_method_id' => $paymentMethod->id,
                    'type' => 'withdrawal',
                    'direction' => 'debit',
                    'amount' => $data['amount'],
                    'fee' => 0,
                    'status' => 'completed',
                    'description' => $data['description'] ?: 'Admin deduction',
                    'reference_id' => $reference,
                ]);

                $activity->record(
                    $user,
                    'admin.wallet_deduct',
                    'Wallet debited by administrator',
                    $data['description'] ?: 'Administrator debited customer wallet.',
                    $reference,
                    'completed',
                    'debit',
                    (float) $data['amount'],
                    $wallet,
                    $transaction,
                    null,
                    $before,
                    ['payment_method_id' => $paymentMethod->id],
                    'admin',
                    auth()->id()
                );
            });
        } catch (\RuntimeException $exception) {
            return redirect()->route('admin.users.show', $user)
                ->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Amount deducted from wallet successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user, ProductionDemoGuard $productionDemo)
    {
        $productionDemo->assertMutationAllowed($user, 'administrator account deletion');
        if ($user->purchases()->count() > 0) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Cannot delete user with existing purchases.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
