<?php

namespace App\Models;

use App\Services\MarketPriceRouter;
use Illuminate\Database\Eloquent\Model;

class TradePosition extends Model
{
    protected $fillable = [
        'user_id','stock_id','entry_transaction_id','last_exit_transaction_id',
        'source_position_id','context_type','context_id','marketplace','direction',
        'initial_quantity','open_quantity','entry_price','average_exit_price',
        'stop_loss_price','take_profit_price','stop_loss_percent','take_profit_percent',
        'duration_minutes','opened_at','expires_at','closed_at','status','exit_reason',
        'realized_profit_loss','realized_return_percent','metadata',
    ];

    protected $casts = [
        'initial_quantity'=>'decimal:8',
        'open_quantity'=>'decimal:8',
        'entry_price'=>'decimal:8',
        'average_exit_price'=>'decimal:8',
        'stop_loss_price'=>'decimal:8',
        'take_profit_price'=>'decimal:8',
        'stop_loss_percent'=>'decimal:4',
        'take_profit_percent'=>'decimal:4',
        'realized_profit_loss'=>'decimal:8',
        'realized_return_percent'=>'decimal:6',
        'opened_at'=>'datetime',
        'expires_at'=>'datetime',
        'closed_at'=>'datetime',
        'metadata'=>'array',
    ];

    public function user(){ return $this->belongsTo(User::class); }
    public function stock(){ return $this->belongsTo(Stock::class); }
    public function entryTransaction(){ return $this->belongsTo(StockTransaction::class,'entry_transaction_id'); }
    public function lastExitTransaction(){ return $this->belongsTo(StockTransaction::class,'last_exit_transaction_id'); }
    public function sourcePosition(){ return $this->belongsTo(self::class,'source_position_id'); }
    public function events(){ return $this->hasMany(TradePositionEvent::class); }

    public function getIsOpenAttribute(): bool
    {
        return in_array($this->status, ['open','exit_queued'], true) && (float)$this->open_quantity > 0;
    }

    public function getCurrentProfitLossAttribute(): float
    {
        if (! $this->stock) return (float)$this->realized_profit_loss;

        try {
            $cmp = app(MarketPriceRouter::class)->price(
                $this->stock,
                $this->marketplace ?: 'live'
            );
        } catch (\Throwable) {
            $cmp = (float)$this->stock->current_price;
        }

        $open = ($cmp - (float)$this->entry_price) * (float)$this->open_quantity;
        return (float)$this->realized_profit_loss + $open;
    }

    public function getCurrentReturnPercentAttribute(): float
    {
        $basis = (float)$this->entry_price * max((float)$this->initial_quantity, 0);
        return $basis > 0 ? ($this->current_profit_loss / $basis) * 100 : 0;
    }
}
