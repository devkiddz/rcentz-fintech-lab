<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'payment_method_id',
        'type',
        'direction',
        'amount',
        'fee',
        'status',
        'reference_id',
        'description',
        'user_crypto_details',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'user_crypto_details' => 'array',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }


    public function getIsCreditAttribute(): bool
    {
        if ($this->direction) {
            return $this->direction === 'credit';
        }

        if (in_array($this->type, ['deposit', 'refund', 'dividend'], true)) {
            return true;
        }

        return $this->type === 'investment'
            && str_starts_with((string) $this->description, 'Sale of');
    }

    public function getIsDebitAttribute(): bool
    {
        return !$this->is_credit;
    }

    public function getFormattedAmountAttribute()
    {
        return '$' . number_format($this->amount, 2);
    }

    public function getFormattedFeeAttribute()
    {
        return '$' . number_format($this->fee, 2);
    }

    public function getTotalAmountAttribute()
    {
        return $this->amount + $this->fee;
    }

    public function getFormattedTotalAmountAttribute()
    {
        return '$' . number_format($this->total_amount, 2);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDeposits($query)
    {
        return $query->where('type', 'deposit');
    }

    public function scopeWithdrawals($query)
    {
        return $query->where('type', 'withdrawal');
    }

    public function scopeInvestments($query)
    {
        return $query->where('type', 'investment');
    }
}
