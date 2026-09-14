<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\Models\User;
use App\Services\NotificationService;
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

    public function approve(WalletTransaction $transaction)
    {
        try {
            DB::beginTransaction();

            if ($transaction->status !== 'pending') {
                return redirect()->back()->with('error', 'Only pending transactions can be approved.');
            }

            $user = $transaction->wallet->user;
            $amount = $transaction->amount;

            if ($transaction->type === 'deposit') {
                // Add funds to user wallet
                $transaction->wallet->addFunds($amount);
                $transaction->update(['status' => 'completed']);

                // Send notification
                NotificationService::createWalletUpdateNotification($user, 'deposit', $amount);

                $message = "Deposit approved. User wallet credited with $" . number_format($amount, 2);

            } elseif ($transaction->type === 'withdrawal') {
                // Check if user has sufficient funds
                if (!$transaction->wallet->canWithdraw($amount)) {
                    return redirect()->back()->with('error', 'User has insufficient funds for this withdrawal.');
                }

                // Deduct funds from user wallet
                $transaction->wallet->deductFunds($amount);
                $transaction->update(['status' => 'completed']);

                // Send notification
                NotificationService::createWalletUpdateNotification($user, 'withdrawal', $amount);

                $message = "Withdrawal approved. User wallet debited with $" . number_format($amount, 2);
            } else {
                return redirect()->back()->with('error', 'Invalid transaction type for approval.');
            }

            DB::commit();

            Log::info("Admin approved {$transaction->type} transaction {$transaction->id} for user {$user->id}");

            return redirect()->route('admin.wallet-transactions.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to approve transaction {$transaction->id}: " . $e->getMessage());

            return redirect()->back()->with('error', 'Failed to approve transaction. Please try again.');
        }
    }

    public function reject(Request $request, WalletTransaction $transaction)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            if ($transaction->status !== 'pending') {
                return redirect()->back()->with('error', 'Only pending transactions can be rejected.');
            }

            $user = $transaction->wallet->user;
            $amount = $transaction->amount;

            // Update transaction status
            $transaction->update([
                'status' => 'rejected',
                'description' => $transaction->description . ' [REJECTED: ' . $request->rejection_reason . ']',
            ]);

            // Send notification
            $notificationMessage = "Your {$transaction->type} request for $" . number_format($amount, 2) . " has been rejected. Reason: " . $request->rejection_reason;
            
            NotificationService::createSystemNotification(
                $user,
                ucfirst($transaction->type) . ' Request Rejected',
                $notificationMessage,
                [
                    'transaction_id' => $transaction->id,
                    'amount' => $amount,
                    'rejection_reason' => $request->rejection_reason,
                ]
            );

            DB::commit();

            Log::info("Admin rejected {$transaction->type} transaction {$transaction->id} for user {$user->id}. Reason: {$request->rejection_reason}");

            return redirect()->route('admin.wallet-transactions.index')
                ->with('success', ucfirst($transaction->type) . ' request rejected successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to reject transaction {$transaction->id}: " . $e->getMessage());

            return redirect()->back()->with('error', 'Failed to reject transaction. Please try again.');
        }
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
