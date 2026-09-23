<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrivateInvestmentInstrument extends Model
{
    protected $fillable = [
        'slug',
        'symbol',
        'name',
        'category',
        'reference_asset_id',
        'description',
        'risk_level',
        'status',
        'currency',
        'opening_price',
        'current_price',
        'previous_price',
        'unit_supply',
        'available_units',
        'minimum_investment',
        'maximum_investment',
        'management_fee_percent',
        'lock_period_days',
        'duration_days',
        'return_interval_days',
        'projected_return_min_percent',
        'projected_return_max_percent',
        'subscription_fee_percent',
        'redemption_fee_percent',
        'maturity_date',
        'is_featured',
        'is_visible',
        'last_valued_at',
    ];

    protected $casts = [
        'opening_price' => 'decimal:6',
        'current_price' => 'decimal:6',
        'previous_price' => 'decimal:6',
        'unit_supply' => 'decimal:6',
        'available_units' => 'decimal:6',
        'minimum_investment' => 'decimal:2',
        'maximum_investment' => 'decimal:2',
        'management_fee_percent' => 'decimal:4',
        'projected_return_min_percent' => 'decimal:4',
        'projected_return_max_percent' => 'decimal:4',
        'subscription_fee_percent' => 'decimal:4',
        'redemption_fee_percent' => 'decimal:4',
        'maturity_date' => 'date',
        'is_featured' => 'boolean',
        'is_visible' => 'boolean',
        'last_valued_at' => 'datetime',
    ];

    public function referenceAsset(): BelongsTo
    {
        return $this->belongsTo(
            PrivateInvestmentAsset::class,
            'reference_asset_id'
        );
    }

    public function assets(): HasMany
    {
        return $this->hasMany(
            PrivateInvestmentAsset::class,
            'instrument_id'
        );
    }

    public function events(): HasMany
    {
        return $this->hasMany(
            PrivateInvestmentEvent::class,
            'instrument_id'
        );
    }

    public function prices(): HasMany
    {
        return $this->hasMany(
            PrivateInvestmentPrice::class,
            'instrument_id'
        );
    }

    public function holdings(): HasMany
    {
        return $this->hasMany(
            PrivateInvestmentHolding::class,
            'instrument_id'
        );
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(
            PrivateInvestmentTransaction::class,
            'instrument_id'
        );
    }

    public function lifecycleEvents(): HasMany
    {
        return $this->hasMany(
            PrivateInvestmentLifecycleEvent::class,
            'instrument_id'
        );
    }

    public function reserveEvents(): HasMany
    {
        return $this->hasMany(
            PrivateInvestmentReserveEvent::class,
            'instrument_id'
        );
    }

    public function getChangeAmountAttribute(): float
    {
        return (float) $this->current_price
            - (float) $this->previous_price;
    }

    public function getChangePercentAttribute(): float
    {
        $previous = (float) $this->previous_price;

        return $previous > 0
            ? ($this->change_amount / $previous) * 100
            : 0;
    }
}