<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CopyRelationship extends Model
{
    use HasFactory;
    protected $fillable=['follower_id','provider_id','copy_strategy_id','allocation_limit','used_amount','max_trade_amount','copy_ratio_percent','status','started_at','stopped_at'];
    protected $casts=['allocation_limit'=>'decimal:2','used_amount'=>'decimal:2','max_trade_amount'=>'decimal:2','copy_ratio_percent'=>'decimal:2','started_at'=>'datetime','stopped_at'=>'datetime'];
    public function follower(){return $this->belongsTo(User::class,'follower_id');}
    public function provider(){return $this->belongsTo(User::class,'provider_id');}
    public function strategy(){return $this->belongsTo(CopyStrategy::class,'copy_strategy_id');}
    public function executions(){return $this->hasMany(CopyTradeExecution::class);}
    public function getRemainingAllocationAttribute(): float{return max(0,(float)$this->allocation_limit-(float)$this->used_amount);}
}
