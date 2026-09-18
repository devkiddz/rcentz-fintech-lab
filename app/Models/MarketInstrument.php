<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketInstrument extends Model
{
    use HasFactory;

    public const ASSET_STOCK = 'stock';
    public const ASSET_FOREX = 'forex';

    protected $fillable = [
        'symbol',
        'display_symbol',
        'name',
        'asset_class',
        'market',
        'base_asset',
        'quote_asset',
        'price_precision',
        'pip_size',
        'stock_id',
        'forex_pair_id',
        'is_active',
        'is_featured',
        'metadata',
    ];

    protected $casts = [
        'price_precision' => 'integer',
        'pip_size' => 'decimal:8',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'metadata' => 'array',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function forexPair()
    {
        return $this->belongsTo(ForexPair::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAssetClass($query, string $assetClass)
    {
        return $query->where('asset_class', strtolower($assetClass));
    }

    public function isStock(): bool
    {
        return $this->asset_class === self::ASSET_STOCK;
    }

    public function isForex(): bool
    {
        return $this->asset_class === self::ASSET_FOREX;
    }
}
