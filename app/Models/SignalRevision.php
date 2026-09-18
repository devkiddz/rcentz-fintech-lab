<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SignalRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'signal_id',
        'revision_number',
        'change_source',
        'previous_values',
        'new_values',
        'reason',
        'analysis_run_id',
        'changed_by_user_id',
    ];

    protected $casts = [
        'revision_number' => 'integer',
        'previous_values' => 'array',
        'new_values' => 'array',
    ];

    public function signal()
    {
        return $this->belongsTo(Signal::class);
    }

    public function analysisRun()
    {
        return $this->belongsTo(SignalAnalysisRun::class, 'analysis_run_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
