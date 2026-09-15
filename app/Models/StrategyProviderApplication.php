<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StrategyProviderApplication extends Model
{
    use HasFactory;
    protected $fillable=['user_id','display_name','experience','strategy_summary','risk_level','status','admin_notes','reviewed_by','reviewed_at'];
    protected $casts=['reviewed_at'=>'datetime'];
    public function user(){return $this->belongsTo(User::class);}
    public function reviewer(){return $this->belongsTo(User::class,'reviewed_by');}
}
