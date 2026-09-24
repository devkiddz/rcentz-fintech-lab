<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PublicInvestmentBaseAsset extends Model
{
    protected $fillable = [
        'market_instrument_id',
        'status',
        'created_by_user_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function marketInstrument(): BelongsTo
    {
        return $this->belongsTo(MarketInstrument::class, 'market_instrument_id');
    }

    public function reserveAssets(): HasMany
    {
        return $this->hasMany(
            PrivateInvestmentAsset::class,
            'public_investment_base_asset_id'
        );
    }
}
