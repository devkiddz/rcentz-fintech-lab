<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlledMarketInstrument extends Model
{
    protected $fillable = [
        'stock_id',
        'symbol',
        'label',
        'asset_class',
        'current_price',
        'previous_price',
        'opening_price',
        'high',
        'low',
        'decimal_precision',
        'minimum_tick',
        'minimum_price',
        'volatility_percent',
        'individual_bias',
        'is_active',
        'last_moved_at',
    ];

    protected $casts = [
        'current_price' => 'decimal:8',
        'previous_price' => 'decimal:8',
        'opening_price' => 'decimal:8',
        'high' => 'decimal:8',
        'low' => 'decimal:8',
        'decimal_precision' => 'integer',
        'minimum_tick' => 'decimal:8',
        'minimum_price' => 'decimal:8',
        'volatility_percent' => 'decimal:6',
        'individual_bias' => 'decimal:4',
        'is_active' => 'boolean',
        'last_moved_at' => 'datetime',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function ticks()
    {
        return $this->hasMany(ControlledMarketTick::class, 'controlled_market_instrument_id');
    }
}
