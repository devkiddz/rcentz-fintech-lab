<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateMarketReferencePrice extends Model
{
    protected $fillable = [
        'reference_id',
        'previous_price',
        'price',
        'change_amount',
        'change_percent',
        'reason',
        'valued_by_user_id',
        'recorded_at',
    ];

    protected $casts = [
        'previous_price' => 'decimal:8',
        'price' => 'decimal:8',
        'change_amount' => 'decimal:8',
        'change_percent' => 'decimal:8',
        'recorded_at' => 'datetime',
    ];

    public function reference(): BelongsTo
    {
        return $this->belongsTo(PrivateMarketReference::class, 'reference_id');
    }
}
