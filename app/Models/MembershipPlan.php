<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'membership_type_id',
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_interval',
        'duration_days',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function type()
    {
        return $this->belongsTo(MembershipType::class, 'membership_type_id');
    }

    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }

    public function entitlements()
    {
        return $this->hasMany(MembershipEntitlement::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
