<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SignalEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'signal_id',
        'signal_target_id',
        'analysis_run_id',
        'actor_user_id',
        'type',
        'payload',
        'occurred_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function signal()
    {
        return $this->belongsTo(Signal::class);
    }

    public function target()
    {
        return $this->belongsTo(SignalTarget::class, 'signal_target_id');
    }

    public function analysisRun()
    {
        return $this->belongsTo(SignalAnalysisRun::class, 'analysis_run_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
