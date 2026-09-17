<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateInvestmentLifecycleEvent extends Model
{
    protected $fillable = [
        'instrument_id',
        'actor_user_id',
        'type',
        'calculation_mode',
        'value',
        'total_amount',
        'affected_holdings',
        'reason',
        'metadata',
        'effective_at',
    ];

    protected $casts = [
        'value' => 'decimal:6',
        'total_amount' => 'decimal:2',
        'metadata' => 'array',
        'effective_at' => 'datetime',
    ];

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(PrivateInvestmentInstrument::class, 'instrument_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
