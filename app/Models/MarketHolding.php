<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketHolding extends Model
{
    protected $fillable = [
        'user_id',
        'market_instrument_id',
        'marketplace',
        'settlement_currency',
        'quantity',
        'average_entry_price',
        'total_invested',
        'current_value',
        'unrealized_gain_loss',
        'unrealized_gain_loss_percentage',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:8',
        'average_entry_price' => 'decimal:10',
        'total_invested' => 'decimal:8',
        'current_value' => 'decimal:8',
        'unrealized_gain_loss' => 'decimal:8',
        'unrealized_gain_loss_percentage' => 'decimal:6',
        'metadata' => 'array',
    ];

    public function user(){ return $this->belongsTo(User::class); }
    public function marketInstrument(){ return $this->belongsTo(MarketInstrument::class); }
}
