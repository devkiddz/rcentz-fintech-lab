<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlledMarketTick extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'controlled_market_instrument_id',
        'drive_mode',
        'open',
        'high',
        'low',
        'close',
        'change_amount',
        'change_percent',
        'ticked_at',
    ];

    protected $casts = [
        'open' => 'decimal:8',
        'high' => 'decimal:8',
        'low' => 'decimal:8',
        'close' => 'decimal:8',
        'change_amount' => 'decimal:8',
        'change_percent' => 'decimal:6',
        'ticked_at' => 'datetime',
    ];

    public function instrument()
    {
        return $this->belongsTo(ControlledMarketInstrument::class, 'controlled_market_instrument_id');
    }
}
