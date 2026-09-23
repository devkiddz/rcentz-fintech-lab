<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrivateMarketReference extends Model
{
    protected $fillable = [
        'symbol',
        'name',
        'category',
        'location',
        'reference_unit',
        'currency',
        'description',
        'current_price',
        'previous_price',
        'status',
        'last_valued_at',
        'created_by_user_id',
    ];

    protected $casts = [
        'current_price' => 'decimal:8',
        'previous_price' => 'decimal:8',
        'last_valued_at' => 'datetime',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(PrivateMarketReferencePrice::class, 'reference_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(PrivateInvestmentAsset::class, 'private_market_reference_id');
    }
}
