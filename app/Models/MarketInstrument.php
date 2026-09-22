<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketInstrument extends Model
{
    use HasFactory;

    public const ASSET_STOCK = 'stock';
    public const ASSET_FOREX = 'forex';
    public const ASSET_CRYPTO = 'crypto';
    public const ASSET_COMMODITY = 'commodity';

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

    /**
     * Canonical parent -> child relations. Legacy stock_id / forex_pair_id
     * remain temporarily as compatibility rails while the runtime migrates.
     */
    public function canonicalStock()
    {
        return $this->hasOne(Stock::class, 'market_instrument_id');
    }

    public function canonicalForexPair()
    {
        return $this->hasOne(ForexPair::class, 'market_instrument_id');
    }

    public function canonicalCryptoPair()
    {
        return $this->hasOne(CryptoPair::class, 'market_instrument_id');
    }

    public function canonicalCommodityInstrument()
    {
        return $this->hasOne(CommodityInstrument::class, 'market_instrument_id');
    }

    public function controlledMarketInstrument()
    {
        return $this->hasOne(ControlledMarketInstrument::class, 'market_instrument_id');
    }

    public function stockHoldings()
    {
        return $this->hasMany(StockHolding::class, 'market_instrument_id');
    }

    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class, 'market_instrument_id');
    }

    public function tradePositions()
    {
        return $this->hasMany(TradePosition::class, 'market_instrument_id');
    }

    public function executionTransactions()
    {
        return $this->hasMany(MarketExecutionTransaction::class, 'market_instrument_id');
    }

    public function marketHoldings()
    {
        return $this->hasMany(MarketHolding::class, 'market_instrument_id');
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

    public function isCrypto(): bool
    {
        return $this->asset_class === self::ASSET_CRYPTO;
    }

    public function isCommodity(): bool
    {
        return $this->asset_class === self::ASSET_COMMODITY;
    }
}
