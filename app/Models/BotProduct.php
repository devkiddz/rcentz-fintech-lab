<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by','stock_id','name','slug','description','strategy','action','risk_level',
        'price','billing_period','minimum_balance','max_user_allocation',
        'default_interval_minutes','default_max_daily_trades','default_trade_amount',
        'default_trigger_price','allow_user_trade_amount','allow_user_trigger_price','is_active',
        'use_manual_performance','manual_profit_loss','manual_return_percent',
        'manual_performance_label','manual_performance_note',
        'manual_performance_updated_by','manual_performance_updated_at',
    ];

    protected $casts = [
        'price'=>'decimal:2',
        'minimum_balance'=>'decimal:2',
        'max_user_allocation'=>'decimal:2',
        'default_trade_amount'=>'decimal:2',
        'default_trigger_price'=>'decimal:2',
        'allow_user_trade_amount'=>'boolean',
        'allow_user_trigger_price'=>'boolean',
        'is_active'=>'boolean',
        'use_manual_performance'=>'boolean',
        'manual_profit_loss'=>'decimal:2',
        'manual_return_percent'=>'decimal:2',
        'manual_performance_updated_at'=>'datetime',
    ];

    public function creator(){return $this->belongsTo(User::class,'created_by');}
    public function stock(){return $this->belongsTo(Stock::class);}
    public function subscriptions(){return $this->hasMany(BotSubscription::class);}
    public function manualPerformanceEditor(){return $this->belongsTo(User::class,'manual_performance_updated_by');}

    /**
     * Bots are subscription products. Legacy "one_time" input is normalized
     * instead of being allowed to re-enter the system.
     */
    public function setBillingPeriodAttribute(?string $value): void
    {
        $allowed = ['monthly','quarterly','yearly'];
        $this->attributes['billing_period'] = in_array($value, $allowed, true)
            ? $value
            : 'monthly';
    }
}
