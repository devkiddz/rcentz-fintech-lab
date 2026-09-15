<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CopyTraderProfile extends Model
{
    use HasFactory;
    protected $fillable=['user_id','strategy_name','bio','risk_level','is_public','is_accepting_copiers','approved_at','approved_by'];
    protected $casts=['is_public'=>'boolean','is_accepting_copiers'=>'boolean','approved_at'=>'datetime'];
    public function user(){return $this->belongsTo(User::class);}
    public function strategies(){return $this->hasMany(CopyStrategy::class);}
    public function relationships(){return $this->hasMany(CopyRelationship::class,'provider_id','user_id');}
    public function activeRelationships(){return $this->relationships()->where('status','active');}
    public function approver(){return $this->belongsTo(User::class,'approved_by');}
}
