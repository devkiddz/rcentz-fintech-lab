<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoPair extends Model
{
    use HasFactory;

    protected $fillable = [
        'market_instrument_id',
        'symbol',
        'display_symbol',
        'name',
        'base_asset',
        'quote_asset',
        'current_rate',
        'previous_close',
        'price_precision',
        'minimum_tick',
        'is_active',
        'is_featured',
        'external_feed_enabled',
        'last_updated',
        'metadata',
    ];

    protected $casts = [
        'current_rate' => 'decimal:12',
        'previous_close' => 'decimal:12',
        'price_precision' => 'integer',
        'minimum_tick' => 'decimal:12',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'external_feed_enabled' => 'boolean',
        'last_updated' => 'datetime',
        'metadata' => 'array',
    ];

    public function marketInstrument()
    {
        return $this->belongsTo(MarketInstrument::class);
    }

    public function candles()
    {
        return $this->hasMany(CryptoCandle::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
