<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateInvestmentAuditLog extends Model
{
    protected $fillable = [
        'actor_user_id',
        'target_user_id',
        'instrument_id',
        'action',
        'reference',
        'reason',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(PrivateInvestmentInstrument::class, 'instrument_id');
    }
}
