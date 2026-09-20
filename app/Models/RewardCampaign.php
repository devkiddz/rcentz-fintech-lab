<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardCampaign extends Model
{
    protected $fillable = [
        'name', 'slug', 'campaign_type', 'description', 'reward_kind', 'cash_amount', 'currency',
        'non_cash_label', 'eligibility_key', 'status', 'starts_at', 'ends_at', 'max_grants',
        'per_user_limit', 'is_visible', 'metadata',
    ];

    protected $casts = [
        'cash_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'max_grants' => 'integer',
        'per_user_limit' => 'integer',
        'is_visible' => 'boolean',
        'metadata' => 'array',
    ];

    public function grants()
    {
        return $this->hasMany(RewardGrant::class);
    }

    public function getIsOpenAttribute(): bool
    {
        if ($this->status !== 'active') return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->ends_at && ! $this->ends_at->isFuture()) return false;

        return true;
    }
}
