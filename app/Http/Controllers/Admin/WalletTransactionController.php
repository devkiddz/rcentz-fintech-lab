<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\FinancialActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = WalletTransaction::with(['wallet.user', 'paymentMethod']);

        // Filter by type if provided
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Filter by status if provided
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

        $stats = [
            'total_transactions' => WalletTransaction::count(),
            'total_deposits' => WalletTransaction::where('type', 'deposit')->count(),
            'total_withdrawals' => WalletTransaction::where('type', 'withdrawal')->count(),
            'total_investments' => WalletTransaction::where('type', 'investment')->count(),
            'pending_deposits' => WalletTransaction::where('type', 'deposit')->where('status', 'pending')->count(),
            'pending_withdrawals' => WalletTransaction::where('type', 'withdrawal')->where('status', 'pending')->count(),
            'total_volume' => WalletTransaction::sum('amount'),
            'completed_volume' => WalletTransaction::where('status', 'completed')->sum('amount'),
        ];

        return view('admin.wallet-transactions.index', compact('transactions', 'stats'));
    }

    public function show(WalletTransaction $transaction)
    {
        $transaction->load(['wallet.user', 'paymentMethod']);

        return view('admin.wallet-transactions.show', compact('transaction'));
    }

    public function approve(WalletTransaction $transaction, FinancialActivityService $activity)
    {
        try{$message=DB::transaction(function() use($transaction,$activity){$tx=WalletTransaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();if($tx->status!=='pending')throw new \RuntimeException('Only pending transactions can be approved.');$wallet=$tx->wallet()->lockForUpdate()->firstOrFail();$user=$wallet->user;$amount=(float)$tx->amount;$before=$activity->snapshot($wallet);if($tx->type==='deposit'){$wallet->addFunds($amount);$tx->update(['status'=>'completed']);$activity->record($user,'deposit.approved','Deposit approved','Deposit verified and credited to the wallet.',$tx->reference_id,'completed','credit',$amount,$wallet,$tx,null,$before,[],'admin',auth()->id());NotificationService::createWalletUpdateNotification($user,'deposit',$amount);return 'Deposit approved. User wallet credited with $'.number_format($amount,2);}if($tx->type==='withdrawal'){if((float)$wallet->reserved_balance<$amount)throw new \RuntimeException('This withdrawal does not have enough reserved balance.');$wallet->settleReservedDebit($amount);$tx->update(['status'=>'completed']);$activity->record($user,'withdrawal.approved','Withdrawal approved','Reserved funds were settled and debited from the wallet.',$tx->reference_id,'completed','debit',$amount,$wallet,$tx,null,$before,[],'admin',auth()->id());NotificationService::createWalletUpdateNotification($user,'withdrawal',$amount);return 'Withdrawal approved. Reserved funds settled for $'.number_format($amount,2);}throw new \RuntimeException('Invalid transaction type for approval.');});return redirect()->route('admin.wallet-transactions.index')->with('success',$message);}catch(\Throwable $e){Log::error('Failed to approve transaction '.$transaction->id.': '.$e->getMessage());return back()->with('error',$e->getMessage());}
    }

    public function reject(Request $request, WalletTransaction $transaction, FinancialActivityService $activity)
    {
        $request->validate(['rejection_reason'=>'required|string|max:255']);try{DB::transaction(function() use($request,$transaction,$activity){$tx=WalletTransaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();if($tx->status!=='pending')throw new \RuntimeException('Only pending transactions can be rejected.');$wallet=$tx->wallet()->lockForUpdate()->firstOrFail();$user=$wallet->user;$amount=(float)$tx->amount;$before=$activity->snapshot($wallet);if($tx->type==='withdrawal'){$wallet->releaseReservedFunds($amount);}$tx->update(['status'=>'rejected','description'=>trim(($tx->description?$tx->description.' ':'').'[REJECTED: '.$request->rejection_reason.']')]);$activity->record($user,$tx->type.'.rejected',ucfirst($tx->type).' rejected',$request->rejection_reason,$tx->reference_id,'rejected',$tx->direction,$amount,$wallet,$tx,null,$before,['rejection_reason'=>$request->rejection_reason],'admin',auth()->id());NotificationService::createSystemNotification($user,ucfirst($tx->type).' Request Rejected','Your '.$tx->type.' request for $'.number_format($amount,2).' was rejected. Reason: '.$request->rejection_reason,['transaction_id'=>$tx->id,'reference'=>$tx->reference_id]);});return redirect()->route('admin.wallet-transactions.index')->with('success',ucfirst($transaction->type).' request rejected successfully.');}catch(\Throwable $e){Log::error('Failed to reject transaction '.$transaction->id.': '.$e->getMessage());return back()->with('error',$e->getMessage());}
    }

    public function destroy(WalletTransaction $transaction)
    {
        try {
            if ($transaction->status === 'completed') {
                return redirect()->back()->with('error', 'Cannot delete completed transactions.');
            }

            $transaction->delete();

            Log::info("Admin deleted transaction {$transaction->id}");

            return redirect()->route('admin.wallet-transactions.index')
                ->with('success', 'Transaction deleted successfully.');

        } catch (\Exception $e) {
            Log::error("Failed to delete transaction {$transaction->id}: " . $e->getMessage());

            return redirect()->back()->with('error', 'Failed to delete transaction. Please try again.');
        }
    }
}
