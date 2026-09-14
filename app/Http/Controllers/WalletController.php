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
        try { $transaction=DB::transaction(function() use($user,$paymentMethod,$request,$activity){ $wallet=$user->wallet()->lockForUpdate()->firstOrFail(); $before=$activity->snapshot($wallet); $reference=$activity->reference('DEP'); $tx=$wallet->transactions()->create(['payment_method_id'=>$paymentMethod->id,'type'=>'deposit','direction'=>'credit','amount'=>$request->amount,'fee'=>0,'status'=>'pending','reference_id'=>$reference,'description'=>$request->description??"Deposit via {$paymentMethod->name}"]); $activity->record($user,'deposit.submitted','Deposit submitted','Deposit request created and awaiting verification.',$reference,'pending','credit',(float)$request->amount,$wallet,$tx,null,$before,['payment_method'=>$paymentMethod->name],'user',$user->id); return $tx; }); NotificationService::createSystemNotification($user,'Deposit submitted','Your deposit of $'.number_format($request->amount,2).' is pending verification.',['type'=>'deposit_pending','transaction_id'=>$transaction->id,'reference'=>$transaction->reference_id]); if($paymentMethod->isCryptocurrency())return redirect()->route('wallet.crypto-payment',$transaction)->with('info','Please complete your crypto transfer and submit the transaction ID.'); return redirect()->route('wallet.index')->with('success','Deposit submitted successfully and is pending verification.'); } catch(\Throwable $e){ return back()->withInput()->withErrors(['error'=>'Failed to submit deposit. Please try again.']); }
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
        NotificationService::createWalletDepositPendingNotification($user,$transaction->amount,$transaction->paymentMethod->name,$transaction->reference_id); return redirect()->route('wallet.index')->with('success','Your deposit proof was submitted and is pending admin approval.');
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
    public function processWithdrawal(Request $request, FinancialActivityService $activity)
    {
        $user=Auth::user();$wallet=$user->wallet;$request->validate(['amount'=>['required','numeric','min:1','max:'.$wallet->available_balance],'payment_method_id'=>'required|exists:payment_methods,id','description'=>'nullable|string|max:500','wallet_address'=>['nullable','string','max:255']]);$paymentMethod=PaymentMethod::findOrFail($request->payment_method_id);if(!$paymentMethod->canWithdraw())return back()->withErrors(['payment_method_id'=>'This payment method does not support withdrawals.']);
        try{$tx=DB::transaction(function() use($user,$request,$paymentMethod,$activity){$wallet=$user->wallet()->lockForUpdate()->firstOrFail();if(!$wallet->canWithdraw($request->amount))throw new \RuntimeException('Insufficient available balance.');$before=$activity->snapshot($wallet);$ref=$activity->reference('WDR');$wallet->reserveFunds($request->amount);$tx=$wallet->transactions()->create(['payment_method_id'=>$paymentMethod->id,'type'=>'withdrawal','direction'=>'debit','amount'=>$request->amount,'fee'=>0,'status'=>'pending','reference_id'=>$ref,'description'=>$request->description??"Withdrawal via {$paymentMethod->name}",'user_crypto_details'=>$paymentMethod->isCryptocurrency()?['wallet_address'=>$request->wallet_address,'crypto_symbol'=>$paymentMethod->crypto_symbol,'payment_method'=>$paymentMethod->name]:null]);$activity->record($user,'withdrawal.reserved','Withdrawal requested','Funds were reserved while the withdrawal awaits review.',$ref,'pending','debit',(float)$request->amount,$wallet,$tx,null,$before,['payment_method'=>$paymentMethod->name],'user',$user->id);return $tx;});NotificationService::createSystemNotification($user,'Withdrawal requested','Your withdrawal request of $'.number_format($request->amount,2).' is pending review. The funds are reserved.',['type'=>'withdrawal_pending','transaction_id'=>$tx->id,'reference'=>$tx->reference_id]);return redirect()->route('wallet.index')->with('success','Withdrawal request submitted. The funds are now reserved pending review.');}catch(\Throwable $e){return back()->withInput()->withErrors(['amount'=>$e->getMessage()==='Insufficient available balance.'?$e->getMessage():'Failed to submit withdrawal. Please try again.']);}
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
        $user=Auth::user();$data=$request->validate(['recipient'=>['required','email','max:255'],'amount'=>['required','numeric','min:1','max:100000'],'note'=>['nullable','string','max:255']]);$recipient=User::query()->whereRaw('LOWER(email) = ?',[strtolower($data['recipient'])])->where('is_admin',false)->first();if(!$recipient||!$recipient->wallet)return back()->withInput()->withErrors(['recipient'=>'No customer account with that email was found.']);if($recipient->id===$user->id)return back()->withInput()->withErrors(['recipient'=>'You cannot transfer funds to your own wallet.']);
        try{$transfer=DB::transaction(function() use($user,$recipient,$data,$activity){$ids=[$user->wallet->id,$recipient->wallet->id];sort($ids);$locked=\App\Models\Wallet::query()->whereIn('id',$ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');$sender=$locked[$user->wallet->id];$receiver=$locked[$recipient->wallet->id];if(!$sender->canWithdraw($data['amount']))throw new \RuntimeException('Insufficient available balance.');$sb=$activity->snapshot($sender);$rb=$activity->snapshot($receiver);$ref=$activity->reference('TRF');$pm=PaymentMethod::firstOrCreate(['name'=>'Internal Transfer'],['type'=>'traditional','details'=>'User-to-user transfer inside the platform.','is_active'=>true,'allow_deposit'=>false,'allow_withdraw'=>false]);$sender->deductFunds($data['amount']);$receiver->addFunds($data['amount']);$transfer=InternalTransfer::create(['reference'=>(string)Str::uuid(),'sender_id'=>$user->id,'recipient_id'=>$recipient->id,'amount'=>$data['amount'],'currency'=>$sender->currency?:'USD','status'=>'completed','note'=>$data['note']??null]);$st=$sender->transactions()->create(['payment_method_id'=>$pm->id,'type'=>'withdrawal','direction'=>'debit','amount'=>$data['amount'],'fee'=>0,'status'=>'completed','reference_id'=>$ref,'description'=>'Internal transfer to '.$recipient->email]);$rt=$receiver->transactions()->create(['payment_method_id'=>$pm->id,'type'=>'deposit','direction'=>'credit','amount'=>$data['amount'],'fee'=>0,'status'=>'completed','reference_id'=>$ref,'description'=>'Internal transfer from '.$user->email]);$activity->record($user,'transfer.sent','Transfer sent','Funds transferred to '.$recipient->email.'.',$ref,'completed','debit',(float)$data['amount'],$sender,$st,$transfer,$sb,['counterparty_user_id'=>$recipient->id],'user',$user->id);$activity->record($recipient,'transfer.received','Transfer received','Funds received from '.$user->email.'.',$ref,'completed','credit',(float)$data['amount'],$receiver,$rt,$transfer,$rb,['counterparty_user_id'=>$user->id],'user',$user->id);return $transfer;});NotificationService::createSystemNotification($recipient,'Funds received','You received '.format_currency($transfer->amount).' from '.$user->name.'.',['type'=>'internal_transfer','reference'=>$transfer->reference]);NotificationService::createSystemNotification($user,'Transfer completed',format_currency($transfer->amount).' was transferred to '.$recipient->email.'.',['type'=>'internal_transfer','reference'=>$transfer->reference]);return redirect()->route('wallet.transfer')->with('success','Transfer completed successfully.');}catch(\Throwable $e){return back()->withInput()->withErrors(['amount'=>$e->getMessage()==='Insufficient available balance.'?$e->getMessage():'The transfer could not be completed. Please try again.']);}
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
