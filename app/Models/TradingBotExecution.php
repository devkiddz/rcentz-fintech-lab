<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingBotExecution extends Model
{
    use HasFactory;

    protected $fillable=[
        'trading_bot_id','bot_subscription_id','market_instrument_id','broker_order_id',
        'market_execution_transaction_id','stock_transaction_id','action','quantity','price',
        'amount','status','reason','executed_at'
    ];

    protected $casts=[
        'quantity'=>'decimal:8',
        'price'=>'decimal:8',
        'amount'=>'decimal:8',
        'executed_at'=>'datetime'
    ];

    public function bot(){return $this->belongsTo(TradingBot::class,'trading_bot_id');}
    public function subscription(){return $this->belongsTo(BotSubscription::class,'bot_subscription_id');}
    public function marketInstrument(){return $this->belongsTo(MarketInstrument::class);}
    public function brokerOrder(){return $this->belongsTo(BrokerOrder::class);}
    public function marketExecution(){return $this->belongsTo(MarketExecutionTransaction::class,'market_execution_transaction_id');}
    public function stockTransaction(){return $this->belongsTo(StockTransaction::class);}
}
