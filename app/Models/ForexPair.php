<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForexPair extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'display_symbol',
        'name',
        'base_currency',
        'quote_currency',
        'pip_size',
        'price_precision',
        'current_rate',
        'previous_close',
        'is_active',
        'is_featured',
        'external_feed_enabled',
        'preferred_sessions',
        'last_updated',
    ];

    protected $casts = [
        'pip_size' => 'decimal:8',
        'price_precision' => 'integer',
        'current_rate' => 'decimal:8',
        'previous_close' => 'decimal:8',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'external_feed_enabled' => 'boolean',
        'preferred_sessions' => 'array',
        'last_updated' => 'datetime',
    ];

    public function candles()
    {
        return $this->hasMany(ForexCandle::class);
    }

    public function marketInstrument()
    {
        return $this->hasOne(MarketInstrument::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function getRouteKeyName(): string
    {
        return 'symbol';
    }
}
