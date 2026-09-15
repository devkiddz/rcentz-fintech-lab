<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradingBot extends Model
{
    use HasFactory;
    protected $fillable=['user_id','stock_id','name','strategy','action','amount_per_trade','quantity_per_trade','trigger_price','interval_minutes','max_daily_trades','max_total_spend','spent_total','status','last_run_at','next_run_at'];
    protected $casts=['amount_per_trade'=>'decimal:2','quantity_per_trade'=>'decimal:6','trigger_price'=>'decimal:2','max_total_spend'=>'decimal:2','spent_total'=>'decimal:2','last_run_at'=>'datetime','next_run_at'=>'datetime'];
    public function user(){return $this->belongsTo(User::class);}
    public function stock(){return $this->belongsTo(Stock::class);}
    public function executions(){return $this->hasMany(TradingBotExecution::class);}
    public function subscription(){return $this->hasOne(BotSubscription::class);}
    public function getIsActiveAttribute():bool{return $this->status==='active';}
}
