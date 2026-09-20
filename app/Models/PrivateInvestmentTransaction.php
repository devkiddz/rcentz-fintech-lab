<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateInvestmentTransaction extends Model
{
    protected $fillable = [
        'user_id','instrument_id','holding_id','type','units','price_per_unit','gross_amount',
        'fee','net_amount','status','reference','idempotency_key','metadata','executed_at',
    ];

    protected $casts = [
        'units'=>'decimal:6','price_per_unit'=>'decimal:6','gross_amount'=>'decimal:2',
        'fee'=>'decimal:2','net_amount'=>'decimal:2','metadata'=>'array','executed_at'=>'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (PrivateInvestmentTransaction $transaction) {
            $metadata = is_array($transaction->metadata) ? $transaction->metadata : [];

            PrivateInvestmentAuditLog::query()->create([
                'actor_user_id' => $metadata['actor_user_id'] ?? $transaction->user_id,
                'target_user_id' => $transaction->user_id,
                'instrument_id' => $transaction->instrument_id,
                'action' => 'transaction.'.$transaction->type,
                'reference' => $transaction->reference,
                'reason' => $transaction->type === 'subscription'
                    ? 'Private investment subscription executed.'
                    : ($transaction->type === 'redemption'
                        ? 'Private investment redemption executed.'
                        : 'Private investment transaction executed.'),
                'metadata' => [
                    'transaction_id' => $transaction->id,
                    'holding_id' => $transaction->holding_id,
                    'source' => $metadata['source'] ?? 'unknown',
                    'units' => (string) $transaction->units,
                    'price_per_unit' => (string) $transaction->price_per_unit,
                    'gross_amount' => (string) $transaction->gross_amount,
                    'fee' => (string) $transaction->fee,
                    'net_amount' => (string) $transaction->net_amount,
                    'wallet_transaction_id' => $metadata['wallet_transaction_id'] ?? null,
                    'idempotency_key' => $transaction->idempotency_key,
                ],
                'occurred_at' => $transaction->executed_at ?? now(),
            ]);
        });
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function instrument(): BelongsTo { return $this->belongsTo(PrivateInvestmentInstrument::class, 'instrument_id'); }
    public function holding(): BelongsTo { return $this->belongsTo(PrivateInvestmentHolding::class, 'holding_id'); }
}
