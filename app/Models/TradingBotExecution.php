<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingBotExecution extends Model
{
    use HasFactory;
    protected $fillable=['trading_bot_id','bot_subscription_id','stock_transaction_id','action','quantity','price','amount','status','reason','executed_at'];
    protected $casts=['quantity'=>'decimal:6','price'=>'decimal:2','amount'=>'decimal:2','executed_at'=>'datetime'];
    public function bot(){return $this->belongsTo(TradingBot::class,'trading_bot_id');}
    public function subscription(){return $this->belongsTo(BotSubscription::class,'bot_subscription_id');}
    public function stockTransaction(){return $this->belongsTo(StockTransaction::class);}
}
