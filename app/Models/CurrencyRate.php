<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrencyRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'currency',
        'rate',
        'last_updated',
    ];

    protected $casts = [
        'rate' => 'decimal:8',
        'last_updated' => 'datetime',
    ];

    /**
     * Get rate for a specific currency
     */
    public static function getRate($currency)
    {
        $rate = self::where('currency', strtoupper($currency))->first();
        return $rate ? $rate->rate : 1.0;
    }

    /**
     * Check if rates need update (older than 1 hour)
     */
    public static function needsUpdate()
    {
        $latestRate = self::orderBy('last_updated', 'desc')->first();
        
        if (!$latestRate) {
            return true;
        }
        
        return $latestRate->last_updated->diffInHours(now()) >= 1;
    }
}