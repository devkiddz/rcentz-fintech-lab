<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipEntitlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'membership_plan_id',
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
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id');
    }
}
