<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function plans()
    {
        return $this->hasMany(MembershipPlan::class);
    }

    public function memberships()
    {
        return $this->hasManyThrough(
            Membership::class,
            MembershipPlan::class,
            'membership_type_id',
            'membership_plan_id',
            'id',
            'id'
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
