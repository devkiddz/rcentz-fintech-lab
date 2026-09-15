<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','stock_id','copy_strategy_id','execution_source','initiated_by_user_id',
        'trade_position_id','wallet_transaction_id','type','quantity','price_per_share',
        'total_amount','fee','status','executed_at',
    ];

    protected $casts = [
        'quantity'=>'decimal:6','price_per_share'=>'decimal:2','total_amount'=>'decimal:2',
        'fee'=>'decimal:2','executed_at'=>'datetime',
    ];

    public function user(){ return $this->belongsTo(User::class); }
    public function stock(){ return $this->belongsTo(Stock::class); }
    public function strategy(){ return $this->belongsTo(CopyStrategy::class,'copy_strategy_id'); }
    public function initiatedBy(){ return $this->belongsTo(User::class,'initiated_by_user_id'); }
    public function position(){ return $this->belongsTo(TradePosition::class,'trade_position_id'); }
    public function walletTransaction(){ return $this->belongsTo(WalletTransaction::class); }

    public function getFormattedQuantityAttribute(){ return number_format($this->quantity,6); }
    public function getFormattedPricePerShareAttribute(){ return '$'.number_format($this->price_per_share,2); }
    public function getFormattedTotalAmountAttribute(){ return '$'.number_format($this->total_amount,2); }
    public function getFormattedFeeAttribute(){ return '$'.number_format($this->fee,2); }
    public function getFormattedExecutedAtAttribute(){ return $this->executed_at?$this->executed_at->format('M j, Y g:i A'):'Pending'; }
    public function getTypeLabelAttribute(){ return ucfirst($this->type); }

    public function scopeByType($query,$type){ return $query->where('type',$type); }
    public function scopeByStatus($query,$status){ return $query->where('status',$status); }
    public function scopeCompleted($query){ return $query->where('status','completed'); }
    public function scopePending($query){ return $query->where('status','pending'); }
    public function scopeBuys($query){ return $query->where('type','buy'); }
    public function scopeSells($query){ return $query->where('type','sell'); }
}
