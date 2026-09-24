<?php

namespace App\Http\Controllers;

use App\Models\InternalTransfer;
use App\Models\LinkedWallet;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\NotificationService;
use App\Services\FinancialOverviewService;
use App\Services\FinancialActivityService;
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
        $totalInvestments = $wallet->total_investments;

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
    public function processDeposit(Request $request, FinancialActivityService $activity)
    {
        $request->validate(['amount'=>'required|numeric|min:1|max:100000','payment_method_id'=>'required|exists:payment_methods,id','description'=>'nullable|string|max:500']);
        $user=Auth::user(); $paymentMethod=PaymentMethod::findOrFail($request->payment_method_id);
        if(!$paymentMethod->canDeposit()) return back()->withErrors(['payment_method_id'=>'This payment method does not support deposits.']);
        try { $transaction=DB::transaction(function() use($user,$paymentMethod,$request,$activity){ $wallet=$user->wallet()->lockForUpdate()->firstOrFail(); $before=$activity->snapshot($wallet); $reference=$activity->reference('DEP'); $tx=$wallet->transactions()->create(['payment_method_id'=>$paymentMethod->id,'type'=>'deposit','direction'=>'credit','amount'=>$request->amount,'fee'=>0,'status'=>'pending','reference_id'=>$reference,'description'=>$request->description??"Deposit via {$paymentMethod->name}"]); $activity->record($user,'deposit.submitted','Deposit submitted','Deposit request created and awaiting verification.',$reference,'pending','credit',(float)$request->amount,$wallet,$tx,null,$before,['payment_method'=>$paymentMethod->name],'user',$user->id); return $tx; }); NotificationService::createSystemNotification($user,'Deposit submitted','Your deposit of $'.number_format($request->amount,2).' is pending verification.',['type'=>'deposit_pending','transaction_id'=>$transaction->id,'reference'=>$transaction->reference_id]); if($paymentMethod->isCryptocurrency())return redirect()->route('money.add.crypto',$transaction)->with('info','Please complete your crypto transfer and submit the transaction ID.'); return redirect()->route('money.index')->with('success','Deposit submitted successfully and is pending verification.'); } catch(\Throwable $e){ return back()->withInput()->withErrors(['error'=>'Failed to submit deposit. Please try again.']); }
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
    public function confirmCryptoPayment(Request $request, WalletTransaction $transaction, FinancialActivityService $activity)
    {
        $user=Auth::user(); if($transaction->wallet_id!==$user->wallet->id||$transaction->type!=='deposit'||$transaction->status!=='pending')abort(403); $request->validate(['transaction_id'=>['required','string','max:255']]);
        DB::transaction(function() use($user,$transaction,$request,$activity){$locked=WalletTransaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();$wallet=$user->wallet()->lockForUpdate()->firstOrFail();$before=$activity->snapshot($wallet);$locked->update(['user_crypto_details'=>array_merge($locked->user_crypto_details??[],['submitted_transaction_id'=>$request->transaction_id])]);$activity->record($user,'deposit.proof_submitted','Deposit proof submitted','Transaction proof was submitted for admin verification.',$locked->reference_id,'pending','credit',(float)$locked->amount,$wallet,$locked,null,$before,['submitted_transaction_id'=>$request->transaction_id],'user',$user->id);});
        NotificationService::createWalletDepositPendingNotification($user,$transaction->amount,$transaction->paymentMethod->name,$transaction->reference_id); return redirect()->route('money.index')->with('success','Your deposit proof was submitted and is pending admin approval.');
    }

    /**
     * Show withdrawal form
     */
    public function withdraw()
    {
        $paymentMethods = PaymentMethod::allowWithdraw()->active()->get();
        $wallet = Auth::user()->wallet;
        
        $expiredRequests = \App\Models\WithdrawalTokenRequest::query()
            ->where('user_id', Auth::id())
            ->where('status', 'token_issued')
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<', now())
            ->get();

        foreach ($expiredRequests as $expiredRequest) {
            $expiredRequest->alert?->update(['dismissed_at' => now()]);
            $expiredRequest->update([
                'status' => 'expired',
                'token_hash' => null,
            ]);
        }

        $activeTokenRequest = \App\Models\WithdrawalTokenRequest::query()
            ->with('walletTransaction')
            ->where('user_id', Auth::id())
            ->whereIn('status', ['pending','token_issued'])
            ->latest()
            ->first();

        $withdrawalRequests = \App\Models\WithdrawalTokenRequest::query()
            ->with('walletTransaction')
            ->where('user_id', Auth::id())
            ->latest()
            ->limit(12)
            ->get();

        return view('wallet.withdraw', compact('paymentMethods', 'wallet', 'activeTokenRequest', 'withdrawalRequests'));
    }

    /**
     * Process withdrawal
     */
    public function processWithdrawal(Request $request, FinancialActivityService $activity)
    {
        // Compatibility endpoint: the first withdrawal step now creates only a token request.
        return $this->requestWithdrawalToken($request);
    }

    public function requestWithdrawalToken(Request $request)
    {
        $user = Auth::user();
        $wallet = $user->wallet;

        $data = $request->validate([
            'amount' => ['required','numeric','min:1','max:'.$wallet->available_balance],
            'note' => ['nullable','string','max:1000'],
        ]);

        \App\Models\WithdrawalTokenRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'token_issued')
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<', now())
            ->get()
            ->each(function ($expiredRequest) {
                $expiredRequest->alert?->update(['dismissed_at' => now()]);
                $expiredRequest->update(['status' => 'expired', 'token_hash' => null]);
            });

        $active = \App\Models\WithdrawalTokenRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending','token_issued'])
            ->latest()
            ->first();

        if ($active) {
            return back()->withErrors(['amount' => 'You already have an active withdrawal token request. Complete or cancel it before starting another.']);
        }

        \App\Models\WithdrawalTokenRequest::query()->create([
            'user_id' => $user->id,
            'amount' => $data['amount'],
            'note' => $data['note'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Withdrawal token requested. No funds have been reserved yet.');
    }

    public function showWithdrawalVerification(\App\Models\WithdrawalTokenRequest $tokenRequest)
    {
        $user = Auth::user();

        abort_unless($tokenRequest->user_id === $user->id && $tokenRequest->status === 'token_issued', 403);

        if (! $tokenRequest->token_expires_at || now()->gt($tokenRequest->token_expires_at)) {
            $tokenRequest->alert?->update(['dismissed_at' => now()]);
            $tokenRequest->update(['status' => 'expired', 'token_hash' => null]);

            return redirect()->route('money.withdraw')->withErrors(['token' => 'This verification code expired. You can submit a new withdrawal request.']);
        }

        $paymentMethods = PaymentMethod::allowWithdraw()->active()->get();

        return view('wallet.verify-withdrawal', compact('tokenRequest', 'paymentMethods'));
    }

    public function verifyWithdrawalToken(Request $request, \App\Models\WithdrawalTokenRequest $tokenRequest, FinancialActivityService $activity)
    {
        $user = Auth::user();

        abort_unless($tokenRequest->user_id === $user->id && $tokenRequest->status === 'token_issued', 403);

        $data = $request->validate([
            'token' => ['required','digits:6'],
            'payment_method_id' => ['required','exists:payment_methods,id'],
            'wallet_address' => ['nullable','string','max:255'],
        ]);

        if (! $tokenRequest->token_hash) return back()->withErrors(['token' => 'This withdrawal token is no longer active.']);
        if (! $tokenRequest->token_expires_at || now()->gt($tokenRequest->token_expires_at)) return back()->withErrors(['token' => 'This withdrawal token has expired.']);
        if (! \Illuminate\Support\Facades\Hash::check($data['token'], $tokenRequest->token_hash)) return back()->withErrors(['token' => 'The withdrawal verification token is invalid.']);

        $paymentMethod = PaymentMethod::findOrFail($data['payment_method_id']);
        if (! $paymentMethod->canWithdraw()) return back()->withErrors(['payment_method_id' => 'This payment method does not support withdrawals.']);
        if ($paymentMethod->isCryptocurrency() && blank($data['wallet_address'] ?? null)) return back()->withErrors(['wallet_address' => 'Destination wallet address is required for cryptocurrency withdrawals.']);

        try {
            $tx = DB::transaction(function () use ($user, $tokenRequest, $paymentMethod, $data, $activity) {
                $lockedRequest = \App\Models\WithdrawalTokenRequest::query()->whereKey($tokenRequest->id)->lockForUpdate()->firstOrFail();

                if ($lockedRequest->status !== 'token_issued') throw new \RuntimeException('This token has already been used or revoked.');
                if (! $lockedRequest->token_expires_at || now()->gt($lockedRequest->token_expires_at)) throw new \RuntimeException('This withdrawal token has expired.');

                $wallet = $user->wallet()->lockForUpdate()->firstOrFail();
                $amount = (float) $lockedRequest->amount;

                if (! $wallet->canWithdraw($amount)) throw new \RuntimeException('Insufficient available balance.');

                $before = $activity->snapshot($wallet);
                $reference = $activity->reference('WDR');

                $wallet->reserveFunds($amount);

                $tx = $wallet->transactions()->create([
                    'payment_method_id' => $paymentMethod->id,
                    'type' => 'withdrawal',
                    'direction' => 'debit',
                    'amount' => $amount,
                    'fee' => 0,
                    'status' => 'pending',
                    'reference_id' => $reference,
                    'description' => "Withdrawal via {$paymentMethod->name}",
                    'withdrawal_purpose' => $lockedRequest->note,
                    'withdrawal_token_verified_at' => now(),
                    'user_crypto_details' => $paymentMethod->isCryptocurrency()
                        ? ['wallet_address'=>$data['wallet_address'],'crypto_symbol'=>$paymentMethod->crypto_symbol,'payment_method'=>$paymentMethod->name]
                        : ($data['wallet_address'] ? ['destination'=>$data['wallet_address'],'payment_method'=>$paymentMethod->name] : ['payment_method'=>$paymentMethod->name]),
                ]);

                $lockedRequest->alert?->update(['read_at' => now(), 'dismissed_at' => now()]);

                $lockedRequest->update([
                    'status' => 'used',
                    'token_verified_at' => now(),
                    'used_at' => now(),
                    'wallet_transaction_id' => $tx->id,
                    'token_hash' => null,
                ]);

                $activity->record(
                    $user,
                    'withdrawal.reserved',
                    'Withdrawal requested',
                    'Token verified and funds reserved while the withdrawal awaits final Admin approval.',
                    $reference,
                    'pending',
                    'debit',
                    $amount,
                    $wallet,
                    $tx,
                    null,
                    $before,
                    ['payment_method'=>$paymentMethod->name,'token_request_id'=>$lockedRequest->id],
                    'user',
                    $user->id
                );

                return $tx;
            });

            return redirect()->route('money.withdraw')->with('success', 'Withdrawal verified and submitted. Funds are reserved while the payout awaits approval.');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['token' => $e->getMessage()]);
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
    public function processTransfer(Request $request, FinancialActivityService $activity)
    {
        $user=Auth::user();$data=$request->validate(['recipient'=>['required','email','max:255'],'amount'=>['required','numeric','min:1','max:100000'],'note'=>['nullable','string','max:255']]);$recipient=User::query()
            ->whereRaw('LOWER(email) = ?',[strtolower($data['recipient'])])
            ->where('is_admin',false)
            ->where('is_production_demo',false)
            ->first();if(!$recipient||!$recipient->wallet)return back()->withInput()->withErrors(['recipient'=>'No customer account with that email was found.']);if($recipient->id===$user->id)return back()->withInput()->withErrors(['recipient'=>'You cannot transfer funds to your own wallet.']);
        try{$transfer=DB::transaction(function() use($user,$recipient,$data,$activity){$ids=[$user->wallet->id,$recipient->wallet->id];sort($ids);$locked=\App\Models\Wallet::query()->whereIn('id',$ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');$sender=$locked[$user->wallet->id];$receiver=$locked[$recipient->wallet->id];if(!$sender->canWithdraw($data['amount']))throw new \RuntimeException('Insufficient available balance.');$sb=$activity->snapshot($sender);$rb=$activity->snapshot($receiver);$ref=$activity->reference('TRF');$pm=PaymentMethod::firstOrCreate(['name'=>'Internal Transfer'],['type'=>'traditional','details'=>'User-to-user transfer inside the platform.','is_active'=>true,'allow_deposit'=>false,'allow_withdraw'=>false]);$sender->deductFunds($data['amount']);$receiver->addFunds($data['amount']);$transfer=InternalTransfer::create(['reference'=>(string)Str::uuid(),'sender_id'=>$user->id,'recipient_id'=>$recipient->id,'amount'=>$data['amount'],'currency'=>$sender->currency?:'USD','status'=>'completed','note'=>$data['note']??null]);$st=$sender->transactions()->create(['payment_method_id'=>$pm->id,'type'=>'withdrawal','direction'=>'debit','amount'=>$data['amount'],'fee'=>0,'status'=>'completed','reference_id'=>$ref,'description'=>'Internal transfer to '.$recipient->email]);$rt=$receiver->transactions()->create(['payment_method_id'=>$pm->id,'type'=>'deposit','direction'=>'credit','amount'=>$data['amount'],'fee'=>0,'status'=>'completed','reference_id'=>$ref,'description'=>'Internal transfer from '.$user->email]);$activity->record($user,'transfer.sent','Transfer sent','Funds transferred to '.$recipient->email.'.',$ref,'completed','debit',(float)$data['amount'],$sender,$st,$transfer,$sb,['counterparty_user_id'=>$recipient->id],'user',$user->id);$activity->record($recipient,'transfer.received','Transfer received','Funds received from '.$user->email.'.',$ref,'completed','credit',(float)$data['amount'],$receiver,$rt,$transfer,$rb,['counterparty_user_id'=>$user->id],'user',$user->id);return $transfer;});NotificationService::createSystemNotification($recipient,'Funds received','You received '.format_currency($transfer->amount).' from '.$user->name.'.',['type'=>'internal_transfer','reference'=>$transfer->reference]);NotificationService::createSystemNotification($user,'Transfer completed',format_currency($transfer->amount).' was transferred to '.$recipient->email.'.',['type'=>'internal_transfer','reference'=>$transfer->reference]);return redirect()->route('money.send')->with('success','Transfer completed successfully.');}catch(\Throwable $e){return back()->withInput()->withErrors(['amount'=>$e->getMessage()==='Insufficient available balance.'?$e->getMessage():'The transfer could not be completed. Please try again.']);}
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

        return redirect()->route('money.connections')
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
     * Show the customer's unified financial ledger.
     */
    public function transactions(Request $request, FinancialOverviewService $financialOverview)
    {
        $user = Auth::user();
        $wallet = $user->wallet;

        $query = $wallet->transactions()->with('paymentMethod');

        if ($request->filled('direction') && in_array($request->direction, ['credit', 'debit'], true)) {
            $query->direction($request->direction);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $finance = $financialOverview->forUser($user, 6);

        $transactionTypes = $wallet->transactions()
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        return view('wallet.transactions', array_merge($finance, [
            'transactions' => $transactions,
            'transactionTypes' => $transactionTypes,
        ]));
    }
}
