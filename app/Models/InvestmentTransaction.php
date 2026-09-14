<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'investment_plan_id',
        'wallet_transaction_id',
        'type',
        'units',
        'nav_at_transaction',
        'total_amount',
        'fee',
        'status',
        'executed_at',
    ];

    protected $casts = [
        'units' => 'decimal:6',
        'nav_at_transaction' => 'decimal:4',
        'total_amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'executed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function investmentPlan()
    {
        return $this->belongsTo(InvestmentPlan::class);
    }

    public function walletTransaction()
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    public function getFormattedUnitsAttribute()
    {
        return number_format($this->units, 6);
    }

    public function getFormattedNavAtTransactionAttribute()
    {
        return '$' . number_format($this->nav_at_transaction, 4);
    }

    public function getFormattedTotalAmountAttribute()
    {
        return '$' . number_format($this->total_amount, 2);
    }

    public function getFormattedFeeAttribute()
    {
        return '$' . number_format($this->fee, 2);
    }

    public function getFormattedExecutedAtAttribute()
    {
        return $this->executed_at ? $this->executed_at->format('M j, Y g:i A') : 'Pending';
    }

    public function getTypeLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->type));
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            'failed' => 'bg-red-100 text-red-800',
        ];

        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
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

    public function scopeBuys($query)
    {
        return $query->where('type', 'buy');
    }

    public function scopeSells($query)
    {
        return $query->where('type', 'sell');
    }

    public function scopeAutomaticInvestments($query)
    {
        return $query->where('type', 'automatic_investment');
    }

    public function scopeDividendReinvestments($query)
    {
        return $query->where('type', 'dividend_reinvestment');
    }
}
