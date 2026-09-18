<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SignalDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'signal_id',
        'initiated_by_user_id',
        'mode',
        'membership_rules_applied',
        'reason',
        'audience_snapshot',
        'started_at',
        'completed_at',
        'delivered_count',
        'skipped_count',
        'failed_count',
    ];

    protected $casts = [
        'membership_rules_applied' => 'boolean',
        'audience_snapshot' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'delivered_count' => 'integer',
        'skipped_count' => 'integer',
        'failed_count' => 'integer',
    ];

    public function signal()
    {
        return $this->belongsTo(Signal::class);
    }

    public function initiatedBy()
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    public function deliveries()
    {
        return $this->hasMany(SignalDelivery::class, 'distribution_id');
    }
}
