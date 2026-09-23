<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateInvestmentReserveEvent extends Model
{
    protected $fillable = [
        'instrument_id',
        'asset_id',
        'action',
        'valuation_mode',
        'previous_quantity',
        'new_quantity',
        'previous_unit_price',
        'new_unit_price',
        'previous_valuation',
        'new_valuation',
        'market_price',
        'reason',
        'created_by_user_id',
        'metadata',
        'effective_at',
    ];

    protected $casts = [
        'previous_quantity' => 'decimal:8',
        'new_quantity' => 'decimal:8',
        'previous_unit_price' => 'decimal:8',
        'new_unit_price' => 'decimal:8',
        'previous_valuation' => 'decimal:2',
        'new_valuation' => 'decimal:2',
        'market_price' => 'decimal:8',
        'metadata' => 'array',
        'effective_at' => 'datetime',
    ];

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(
            PrivateInvestmentInstrument::class,
            'instrument_id'
        );
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(
            PrivateInvestmentAsset::class,
            'asset_id'
        );
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by_user_id'
        );
    }
}