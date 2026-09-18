<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Signal extends Model
{
    use HasFactory;

    public const OPEN_STATUSES = ['candidate', 'ready', 'published', 'active'];
    public const TERMINAL_STATUSES = ['closed', 'stopped', 'expired', 'invalidated', 'cancelled'];

    protected $fillable = [
        'stock_id',
        'marketplace',
        'source',
        'direction',
        'timeframe',
        'status',
        'strength',
        'confluence_score',
        'entry_min',
        'entry_max',
        'stop_loss',
        'risk_reward',
        'rationale',
        'analysis_snapshot',
        'generated_at',
        'published_at',
        'activated_at',
        'expires_at',
        'closed_at',
        'invalidated_at',
        'cancelled_at',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'confluence_score' => 'decimal:2',
        'entry_min' => 'decimal:8',
        'entry_max' => 'decimal:8',
        'stop_loss' => 'decimal:8',
        'risk_reward' => 'decimal:4',
        'analysis_snapshot' => 'array',
        'generated_at' => 'datetime',
        'published_at' => 'datetime',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'closed_at' => 'datetime',
        'invalidated_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function targets()
    {
        return $this->hasMany(SignalTarget::class)->orderBy('sequence');
    }

    public function analysisRuns()
    {
        return $this->hasMany(SignalAnalysisRun::class);
    }

    public function revisions()
    {
        return $this->hasMany(SignalRevision::class)->orderBy('revision_number');
    }

    public function events()
    {
        return $this->hasMany(SignalEvent::class)->orderBy('occurred_at');
    }

    public function distributions()
    {
        return $this->hasMany(SignalDistribution::class);
    }

    public function deliveries()
    {
        return $this->hasMany(SignalDelivery::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function scopeOpenLifecycle($query)
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    public function getIsOpenAttribute(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }
}
