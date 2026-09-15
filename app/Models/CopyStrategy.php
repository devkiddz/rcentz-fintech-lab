<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CopyStrategy extends Model
{
    use HasFactory;
    protected $fillable=['copy_trader_profile_id','name','description','risk_level','minimum_allocation','recommended_allocation','is_public','is_active','use_manual_performance','manual_profit_loss','manual_return_percent','manual_performance_label','manual_performance_note','manual_performance_updated_by','manual_performance_updated_at'];
    protected $casts=['minimum_allocation'=>'decimal:2','recommended_allocation'=>'decimal:2','is_public'=>'boolean','is_active'=>'boolean','use_manual_performance'=>'boolean','manual_profit_loss'=>'decimal:2','manual_return_percent'=>'decimal:2','manual_performance_updated_at'=>'datetime'];
    public function profile(){return $this->belongsTo(CopyTraderProfile::class,'copy_trader_profile_id');}
    public function relationships(){return $this->hasMany(CopyRelationship::class);}
    public function getProviderAttribute(){return $this->profile?->user;}
    public function manualPerformanceEditor(){return $this->belongsTo(User::class,'manual_performance_updated_by');}
}
