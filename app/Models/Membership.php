<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Membership extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'membership_plan_id',
        'status',
        'price_paid',
        'currency',
        'source',
        'reference',
        'starts_at',
        'ends_at',
        'activated_at',
        'cancelled_at',
        'expired_at',
        'activated_by_user_id',
        'metadata',
    ];

    protected $casts = [
        'price_paid' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'activated_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expired_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id');
    }

    public function activatedBy()
    {
        return $this->belongsTo(User::class, 'activated_by_user_id');
    }

    public function scopeActiveAt($query, $at = null)
    {
        $at ??= now();

        return $query
            ->where('status', 'active')
            ->where(function ($q) use ($at) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $at);
            })
            ->where(function ($q) use ($at) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', $at);
            })
            ->whereNull('cancelled_at')
            ->whereNull('expired_at');
    }

    public function getIsActiveAttribute(): bool
    {
        if ($this->status !== 'active') return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->ends_at && ! $this->ends_at->isFuture()) return false;
        if ($this->cancelled_at || $this->expired_at) return false;

        return true;
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if (! $this->ends_at) return null;
        if (! $this->is_active) return 0;

        return max(0, now()->startOfDay()->diffInDays($this->ends_at->copy()->startOfDay(), false));
    }
}
