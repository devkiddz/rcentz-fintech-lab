<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class CopyTradeExecution extends Model {
 use HasFactory;
 protected $fillable=['copy_relationship_id','provider_stock_transaction_id','follower_stock_transaction_id','action','requested_amount','executed_amount','status','failure_reason','executed_at'];
 protected $casts=['requested_amount'=>'decimal:2','executed_amount'=>'decimal:2','executed_at'=>'datetime'];
 public function relationship(){return $this->belongsTo(CopyRelationship::class,'copy_relationship_id');}
 public function providerTrade(){return $this->belongsTo(StockTransaction::class,'provider_stock_transaction_id');}
 public function followerTrade(){return $this->belongsTo(StockTransaction::class,'follower_stock_transaction_id');}
}
