<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradePositionEvent extends Model
{
    protected $fillable = [
        'trade_position_id','stock_transaction_id','actor_id','actor_type',
        'event_type','quantity','price','profit_loss','note','metadata',
    ];

    protected $casts = [
        'quantity'=>'decimal:8',
        'price'=>'decimal:8',
        'profit_loss'=>'decimal:8',
        'metadata'=>'array',
    ];

    public function position(){ return $this->belongsTo(TradePosition::class,'trade_position_id'); }
    public function stockTransaction(){ return $this->belongsTo(StockTransaction::class); }
    public function actor(){ return $this->belongsTo(User::class,'actor_id'); }
}
