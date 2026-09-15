<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','bot_product_id','trading_bot_id','price_paid','status',
        'starts_at','ends_at','cancelled_at','expired_at',
    ];

    protected $casts = [
        'price_paid'=>'decimal:2',
        'starts_at'=>'datetime',
        'ends_at'=>'datetime',
        'cancelled_at'=>'datetime',
        'expired_at'=>'datetime',
    ];

    public function user(){return $this->belongsTo(User::class);}
    public function product(){return $this->belongsTo(BotProduct::class,'bot_product_id');}
    public function bot(){return $this->belongsTo(TradingBot::class,'trading_bot_id');}
    public function executions(){return $this->hasMany(TradingBotExecution::class,'bot_subscription_id');}

    public function getIsExpiredAttribute(): bool
    {
        return $this->status === 'expired'
            || ($this->ends_at && $this->ends_at->isPast());
    }

    public function getIsUsableAttribute(): bool
    {
        return in_array($this->status,['active','paused'],true)
            && ! $this->is_expired;
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if (! $this->ends_at || $this->is_expired) {
            return $this->is_expired ? 0 : null;
        }

        return max(0, now()->startOfDay()->diffInDays($this->ends_at->copy()->startOfDay(), false));
    }
}
