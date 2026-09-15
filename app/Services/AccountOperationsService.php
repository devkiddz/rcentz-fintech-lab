<?php

namespace App\Services;

use App\Models\AdminAccountOperation;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AccountOperationsService
{
    public function __construct(private FinancialActivityService $activity) {}

    public function apply(
        User $user,
        User $admin,
        string $type,
        ?float $amount,
        string $label,
        string $reason,
        ?string $effectiveAt = null,
        array $metadata = []
    ): AdminAccountOperation {
        return DB::transaction(function () use (
            $user,$admin,$type,$amount,$label,$reason,$effectiveAt,$metadata
        ) {
            $wallet = $user->wallet()->lockForUpdate()->first();

            if (! $wallet) {
                $wallet = $user->wallet()->create([
                    'balance'=>0,
                    'reserved_balance'=>0,
                    'currency'=>$user->currency ?: 'USD',
                ]);
                $wallet = $user->wallet()->lockForUpdate()->firstOrFail();
            }

            $reference = $this->activity->reference('ADM-ACC');
            $before = $this->activity->snapshot($wallet);
            $walletTx = null;
            $direction = null;
            $eventType = 'admin.history_event';
            $eventAmount = $amount;

            if (in_array($type, ['wallet_credit','profit_credit','wallet_debit'], true)) {
                if (! $amount || $amount <= 0) {
                    throw new RuntimeException('Amount must be greater than zero.');
                }

                $method = $this->internalMethod();

                if ($type === 'wallet_debit') {
                    if (! $wallet->canWithdraw($amount)) {
                        throw new RuntimeException('Insufficient available balance for this debit.');
                    }

                    $direction = 'debit';
                    $eventType = 'admin.wallet_debit';
                    $walletTx = $wallet->transactions()->create([
                        'payment_method_id'=>$method->id,
                        'type'=>'withdrawal',
                        'direction'=>'debit',
                        'amount'=>$amount,
                        'fee'=>0,
                        'status'=>'completed',
                        'reference_id'=>$reference,
                        'description'=>$label,
                    ]);
                    $wallet->deductFunds($amount);
                } else {
                    $direction = 'credit';
                    $eventType = $type === 'profit_credit'
                        ? 'admin.profit_credit'
                        : 'admin.wallet_credit';

                    $walletTx = $wallet->transactions()->create([
                        'payment_method_id'=>$method->id,
                        'type'=>$type === 'profit_credit' ? 'dividend' : 'deposit',
                        'direction'=>'credit',
                        'amount'=>$amount,
                        'fee'=>0,
                        'status'=>'completed',
                        'reference_id'=>$reference,
                        'description'=>$label,
                    ]);
                    $wallet->addFunds($amount);
                }
            }

            $operation = AdminAccountOperation::create([
                'user_id'=>$user->id,
                'admin_id'=>$admin->id,
                'operation_type'=>$type,
                'direction'=>$direction,
                'amount'=>$eventAmount,
                'effective_at'=>$effectiveAt,
                'reference'=>$reference,
                'label'=>$label,
                'reason'=>$reason,
                'metadata'=>$metadata ?: null,
            ]);

            $this->activity->record(
                $user,
                $eventType,
                $label,
                $reason,
                $reference,
                'completed',
                $direction,
                $eventAmount,
                $wallet,
                $walletTx,
                null,
                $before,
                array_merge($metadata, [
                    'admin_account_operation_id'=>$operation->id,
                    'effective_at'=>$effectiveAt,
                    'recorded_at'=>now()->toIso8601String(),
                    'balance_impact'=>in_array($type,['wallet_credit','profit_credit','wallet_debit'],true),
                ]),
                'admin',
                $admin->id
            );

            return $operation;
        });
    }

    private function internalMethod(): PaymentMethod
    {
        return PaymentMethod::firstOrCreate(
            ['name'=>'Admin Account Operations'],
            [
                'type'=>'traditional',
                'details'=>'Audited administrative account adjustment method.',
                'is_active'=>true,
                'allow_deposit'=>false,
                'allow_withdraw'=>false,
            ]
        );
    }
}
