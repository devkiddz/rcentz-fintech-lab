<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VipEntitlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'vip_plan_id',
        'key',
        'label',
        'description',
        'value',
        'enabled',
    ];

    protected $casts = [
        'value' => 'array',
        'enabled' => 'boolean',
    ];

    public function plan()
    {
        return $this->belongsTo(VipPlan::class, 'vip_plan_id');
    }
}
