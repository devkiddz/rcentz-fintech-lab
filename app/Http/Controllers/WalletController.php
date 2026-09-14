<?php

namespace App\Http\Controllers;

use App\Models\InternalTransfer;
use App\Models\LinkedWallet;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WalletController extends Controller
{
    /**
     * Display wallet overview
     */
    public function index()
    {
        $user = Auth::user();
        $wallet = $user->wallet;
        
        // Get recent transactions
        $recentTransactions = $wallet->transactions()
            ->with('paymentMethod')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get transaction statistics
        $totalDeposits = $wallet->transactions()->deposits()->completed()->sum('amount');
        $totalWithdrawals = $wallet->transactions()->withdrawals()->completed()->sum('amount');
        $totalInvestments = $wallet->transactions()->investments()->completed()->sum('amount');

        return view('wallet.index', compact(
            'wallet',
            'recentTransactions',
            'totalDeposits',
            'totalWithdrawals',
            'totalInvestments'
        ));
    }

    /**
     * Show deposit form
     */
    public function deposit()
    {
        $paymentMethods = PaymentMethod::allowDeposit()->active()->get();
        
        return view('wallet.deposit', compact('paymentMethods'));
    }

    /**
     * Process deposit
     */
    public function processDeposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:100000',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'description' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $wallet = $user->wallet;
        $paymentMethod = PaymentMethod::findOrFail($request->payment_method_id);

        // Verify payment method allows deposits
        if (!$paymentMethod->canDeposit()) {
            return back()->withErrors(['payment_method_id' => 'This payment method does not support deposits.']);
        }

        try {
            DB::beginTransaction();

            // Create wallet transaction (pending by default)
            $transaction = $wallet->transactions()->create([
                'payment_method_id' => $paymentMethod->id,
                'type' => 'deposit',
                'amount' => $request->amount,
                'fee' => 0.00, // Optionally compute based on payment method
                'status' => 'pending',
                'description' => $request->description ?? "Deposit via {$paymentMethod->name}",
            ]);

            DB::commit();

            // For cryptocurrency deposits, redirect to a confirmation page with wallet address and QR
            if ($paymentMethod->isCryptocurrency()) {
                return redirect()->route('wallet.crypto-payment', $transaction)
                    ->with('info', 'Please complete your crypto transfer and submit the transaction ID.');
            }

            // For traditional methods, credit instantly (existing behavior)
            DB::beginTransaction();
            $wallet->addFunds($request->amount);
            $transaction->update(['status' => 'completed']);

            NotificationService::createWalletUpdateNotification(
                $user,
                'deposit',
                $request->amount
            );

            DB::commit();

            return redirect()->route('wallet.index')
                ->with('success', 'Deposit processed successfully. Your wallet has been credited.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to process deposit. Please try again.']);
        }
    }

    /**
     * Show crypto payment confirmation page (wallet address + QR + tx id input)
     */
    public function cryptoPayment(WalletTransaction $transaction)
    {
        $user = Auth::user();

        // Ensure the transaction belongs to the authenticated user and is a pending deposit
        if (
            $transaction->wallet_id !== $user->wallet->id ||
            $transaction->type !== 'deposit' ||
            $transaction->status !== 'pending'
        ) {
            abort(403);
        }

        $paymentMethod = $transaction->paymentMethod;

        return view('wallet.crypto-payment', compact('transaction', 'paymentMethod'));
    }

    /**
     * Confirm crypto payment by saving user-provided transaction hash/id. Remains pending for admin approval.
     */
    public function confirmCryptoPayment(Request $request, WalletTransaction $transaction)
    {
        $user = Auth::user();

        if (
            $transaction->wallet_id !== $user->wallet->id ||
            $transaction->type !== 'deposit' ||
            $transaction->status !== 'pending'
        ) {
            abort(403);
        }

        $request->validate([
            'transaction_id' => ['required', 'string', 'max:255'],
        ]);

        $transaction->update([
            'reference_id' => $request->transaction_id,
            // keep status as 'pending' until admin verifies and approves
            'description' => $transaction->description ?? 'Crypto deposit pending verification',
        ]);

        // Notify user that their crypto deposit is pending approval
        NotificationService::createWalletDepositPendingNotification(
            $user,
            $transaction->amount,
            $transaction->paymentMethod->name,
            $transaction->reference_id
        );

        return redirect()->route('wallet.index')
            ->with('success', 'Your crypto deposit is submitted and pending admin approval.');
    }

    /**
     * Show withdrawal form
     */
    public function withdraw()
    {
        $paymentMethods = PaymentMethod::allowWithdraw()->active()->get();
        $wallet = Auth::user()->wallet;
        
        return view('wallet.withdraw', compact('paymentMethods', 'wallet'));
    }

    /**
     * Process withdrawal
     */
    public function processWithdrawal(Request $request)
    {
        $user = Auth::user();
        $wallet = $user->wallet;

        $request->validate([
            'amount' => [
                'required',
                'numeric',
                'min:1',
                'max:' . $wallet->balance,
            ],
            'payment_method_id' => 'required|exists:payment_methods,id',
            'description' => 'nullable|string|max:500',
            // Require wallet address if crypto withdrawal
            'wallet_address' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $paymentMethod = PaymentMethod::findOrFail($request->payment_method_id);

        // Verify payment method allows withdrawals
        if (!$paymentMethod->canWithdraw()) {
            return back()->withErrors(['payment_method_id' => 'This payment method does not support withdrawals.']);
        }

        // Verify sufficient balance
        if (!$wallet->canWithdraw($request->amount)) {
            return back()->withErrors(['amount' => 'Insufficient funds in wallet.']);
        }

        try {
            DB::beginTransaction();

            // Create wallet transaction
            $transaction = $wallet->transactions()->create([
                'payment_method_id' => $paymentMethod->id,
                'type' => 'withdrawal',
                'amount' => $request->amount,
                'fee' => 0.00,
                'status' => 'pending',
                'description' => $request->description ?? "Withdrawal via {$paymentMethod->name}",
                'user_crypto_details' => $paymentMethod->isCryptocurrency() ? [
                    'wallet_address' => $request->wallet_address,
                    'crypto_symbol' => $paymentMethod->crypto_symbol,
                    'payment_method' => $paymentMethod->name,
                ] : null,
            ]);

            // For withdrawals, keep pending for admin to process payout
            // Do not deduct immediately; admin approval/process should handle funds movement

            // Create notification for successful withdrawal
            NotificationService::createSystemNotification(
                $user,
                'Withdrawal requested',
                'Your withdrawal request of $' . number_format($request->amount, 2) . ' is pending review.',
                [
                    'type' => 'withdrawal_pending',
                    'amount' => $request->amount,
                    'payment_method' => $paymentMethod->name,
                    'crypto' => $paymentMethod->isCryptocurrency(),
                ]
            );

            DB::commit();

            return redirect()->route('wallet.index')
                ->with('success', 'Withdrawal request submitted and pending admin approval.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to process withdrawal. Please try again.']);
        }
    }

    /**
     * Show the internal transfer form.
     */
    public function transfer()
    {
        $user = Auth::user();
        $wallet = $user->wallet;
        $recentTransfers = InternalTransfer::with(['sender', 'recipient'])
            ->where(function ($query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->orWhere('recipient_id', $user->id);
            })
            ->latest()
            ->limit(8)
            ->get();

        return view('wallet.transfer', compact('wallet', 'recentTransfers'));
    }

    /**
     * Execute a user-to-user transfer atomically.
     */
    public function processTransfer(Request $request)
    {
        $user = Auth::user();
        $wallet = $user->wallet;

        $data = $request->validate([
            'recipient' => ['required', 'email', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1', 'max:100000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $recipient = User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($data['recipient'])])
            ->where('is_admin', false)
            ->first();

        if (! $recipient || ! $recipient->wallet) {
            return back()->withInput()->withErrors([
                'recipient' => 'No customer account with that email was found.',
            ]);
        }

        if ($recipient->id === $user->id) {
            return back()->withInput()->withErrors([
                'recipient' => 'You cannot transfer funds to your own wallet.',
            ]);
        }

        if (! $wallet->canWithdraw($data['amount'])) {
            return back()->withInput()->withErrors([
                'amount' => 'Your wallet does not have enough available balance.',
            ]);
        }

        try {
            $transfer = DB::transaction(function () use ($user, $recipient, $wallet, $data) {
                // Lock both wallet rows so simultaneous transfers cannot spend the same balance.
                $senderWallet = $wallet->newQuery()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
                $recipientWallet = $recipient->wallet()->lockForUpdate()->firstOrFail();

                if ((float) $senderWallet->balance < (float) $data['amount']) {
                    throw new \RuntimeException('Insufficient funds.');
                }

                $reference = (string) Str::uuid();
                $paymentMethod = PaymentMethod::firstOrCreate(
                    ['name' => 'Internal Transfer'],
                    [
                        'type' => 'traditional',
                        'details' => 'User-to-user transfer inside the platform.',
                        'is_active' => true,
                        'allow_deposit' => false,
                        'allow_withdraw' => false,
                    ]
                );

                $senderWallet->deductFunds($data['amount']);
                $recipientWallet->addFunds($data['amount']);

                $transfer = InternalTransfer::create([
                    'reference' => $reference,
                    'sender_id' => $user->id,
                    'recipient_id' => $recipient->id,
                    'amount' => $data['amount'],
                    'currency' => $senderWallet->currency ?: 'USD',
                    'status' => 'completed',
                    'note' => $data['note'] ?? null,
                ]);

                $senderWallet->transactions()->create([
                    'payment_method_id' => $paymentMethod->id,
                    'type' => 'withdrawal',
                    'amount' => $data['amount'],
                    'fee' => 0,
                    'status' => 'completed',
                    'reference_id' => 'TX-' . $reference,
                    'description' => 'Internal transfer to ' . $recipient->email,
                ]);

                $recipientWallet->transactions()->create([
                    'payment_method_id' => $paymentMethod->id,
                    'type' => 'deposit',
                    'amount' => $data['amount'],
                    'fee' => 0,
                    'status' => 'completed',
                    'reference_id' => 'TX-' . $reference,
                    'description' => 'Internal transfer from ' . $user->email,
                ]);

                return $transfer;
            });

            NotificationService::createSystemNotification(
                $recipient,
                'Funds received',
                'You received ' . format_currency($transfer->amount) . ' from ' . $user->name . '.',
                ['type' => 'internal_transfer', 'reference' => $transfer->reference]
            );

            NotificationService::createSystemNotification(
                $user,
                'Transfer completed',
                format_currency($transfer->amount) . ' was transferred to ' . $recipient->email . '.',
                ['type' => 'internal_transfer', 'reference' => $transfer->reference]
            );

            return redirect()->route('wallet.transfer')
                ->with('success', 'Transfer completed successfully.');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors([
                'error' => $e->getMessage() === 'Insufficient funds.'
                    ? $e->getMessage()
                    : 'The transfer could not be completed. Please try again.',
            ]);
        }
    }

    /**
     * Manage external wallet connections.
     */
    public function connections()
    {
        $wallets = Auth::user()->linkedWallets()
            ->where('status', 'active')
            ->latest()
            ->get();

        return view('wallet.connections', compact('wallets'));
    }

    /**
     * Link an external wallet reference to the account.
     */
    public function storeConnection(Request $request)
    {
        $data = $request->validate([
            'provider' => ['required', Rule::in(['walletconnect', 'metamask', 'coinbase', 'trust', 'other'])],
            'network' => ['required', Rule::in(['ethereum', 'bnb', 'polygon', 'solana', 'bitcoin'])],
            'address' => ['required', 'string', 'min:12', 'max:255'],
            'label' => ['nullable', 'string', 'max:80'],
        ]);

        $user = Auth::user();

        $exists = $user->linkedWallets()
            ->where('network', $data['network'])
            ->where('address', $data['address'])
            ->where('status', 'active')
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'address' => 'This wallet is already connected to your account.',
            ]);
        }

        $linked = DB::transaction(function () use ($user, $data) {
            $makePrimary = ! $user->linkedWallets()->where('status', 'active')->exists();

            return $user->linkedWallets()->create([
                ...$data,
                'is_primary' => $makePrimary,
                'status' => 'active',
            ]);
        });

        NotificationService::createSystemNotification(
            $user,
            'Wallet connected',
            ucfirst($linked->network) . ' wallet ' . $linked->masked_address . ' was connected to your account.',
            ['type' => 'wallet_connection', 'wallet_id' => $linked->id]
        );

        return redirect()->route('wallet.connections')
            ->with('success', 'Wallet connected successfully.');
    }

    public function setPrimaryConnection(LinkedWallet $linkedWallet)
    {
        $user = Auth::user();
        abort_unless($linkedWallet->user_id === $user->id && $linkedWallet->status === 'active', 403);

        DB::transaction(function () use ($user, $linkedWallet) {
            $user->linkedWallets()->update(['is_primary' => false]);
            $linkedWallet->update(['is_primary' => true]);
        });

        return back()->with('success', 'Primary wallet updated.');
    }

    public function destroyConnection(LinkedWallet $linkedWallet)
    {
        $user = Auth::user();
        abort_unless($linkedWallet->user_id === $user->id, 403);

        DB::transaction(function () use ($user, $linkedWallet) {
            $wasPrimary = $linkedWallet->is_primary;
            $linkedWallet->update(['status' => 'disconnected', 'is_primary' => false]);

            if ($wasPrimary) {
                $next = $user->linkedWallets()
                    ->where('status', 'active')
                    ->whereKeyNot($linkedWallet->id)
                    ->latest()
                    ->first();
                $next?->update(['is_primary' => true]);
            }
        });

        return back()->with('success', 'Wallet disconnected.');
    }

    /**
     * Show transaction history
     */
    public function transactions(Request $request)
    {
        $user = Auth::user();
        $wallet = $user->wallet;

        $query = $wallet->transactions()->with('paymentMethod');

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

        // Calculate transaction statistics
        $totalDeposits = $wallet->transactions()->deposits()->completed()->sum('amount');
        $totalWithdrawals = $wallet->transactions()->withdrawals()->completed()->sum('amount');
        $totalFees = $wallet->transactions()->sum('fee');

        return view('wallet.transactions', compact('transactions', 'wallet', 'totalDeposits', 'totalWithdrawals', 'totalFees'));
    }
}
