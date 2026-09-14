<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'currency',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function getFormattedBalanceAttribute()
    {
        return '$' . number_format($this->balance, 2);
    }

    public function canWithdraw($amount)
    {
        return $this->balance >= $amount;
    }

    public function addFunds($amount)
    {
        $this->increment('balance', $amount);
        return $this;
    }

    public function deductFunds($amount)
    {
        if ($this->canWithdraw($amount)) {
            $this->decrement('balance', $amount);
            return $this;
        }
        
        throw new \Exception('Insufficient funds');
    }

    public function getTotalDepositsAttribute()
    {
        return $this->transactions()
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->sum('amount');
    }

    public function getTotalWithdrawalsAttribute()
    {
        return $this->transactions()
            ->where('type', 'withdrawal')
            ->where('status', 'completed')
            ->sum('amount');
    }

    public function getTotalInvestmentsAttribute()
    {
        return $this->transactions()
            ->where('type', 'investment')
            ->where('status', 'completed')
            ->sum('amount');
    }
}
