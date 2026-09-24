<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrivateInvestmentAsset extends Model
{
    public const VALUATION_MANUAL = 'manual';
    public const VALUATION_PRIVATE = 'private';
    public const VALUATION_MARKET_LINKED = 'market_linked';

    protected $fillable = [
        'instrument_id',
        'market_instrument_id',
        'public_investment_base_asset_id',
        'private_market_reference_id',
        'valuation_mode',
        'is_reserve_backing',
        'reserve_quantity',
        'reserve_unit',
        'acquisition_unit_price',
        'current_unit_price',
        'last_valued_at',
        'asset_type',
        'name',
        'description',
        'acquisition_value',
        'current_valuation',
        'ownership_percentage',
        'status',
        'acquired_at',
        'effective_at',
        'notes',
    ];

    protected $casts = [
        'is_reserve_backing' => 'boolean',
        'reserve_quantity' => 'decimal:8',
        'acquisition_unit_price' => 'decimal:8',
        'current_unit_price' => 'decimal:8',
        'last_valued_at' => 'datetime',
        'acquisition_value' => 'decimal:2',
        'current_valuation' => 'decimal:2',
        'ownership_percentage' => 'decimal:4',
        'acquired_at' => 'date',
        'effective_at' => 'datetime',
    ];

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(
            PrivateInvestmentInstrument::class,
            'instrument_id'
        );
    }

    public function marketInstrument(): BelongsTo
    {
        return $this->belongsTo(
            MarketInstrument::class,
            'market_instrument_id'
        );
    }

    public function publicBaseAsset(): BelongsTo
    {
        return $this->belongsTo(
            PublicInvestmentBaseAsset::class,
            'public_investment_base_asset_id'
        );
    }

    public function privateMarketReference(): BelongsTo
    {
        return $this->belongsTo(
            PrivateMarketReference::class,
            'private_market_reference_id'
        );
    }

    public function reserveEvents(): HasMany
    {
        return $this->hasMany(
            PrivateInvestmentReserveEvent::class,
            'asset_id'
        );
    }

    public function isMarketLinked(): bool
    {
        return $this->valuation_mode === self::VALUATION_MARKET_LINKED
            && $this->market_instrument_id !== null;
    }

    public function isPrivateLinked(): bool
    {
        return $this->valuation_mode === self::VALUATION_PRIVATE
            && $this->private_market_reference_id !== null;
    }
}