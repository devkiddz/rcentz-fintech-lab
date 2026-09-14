<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StockQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'current_price',
        'previous_close',
        'change_amount',
        'change_percentage',
        'volume',
        'high',
        'low',
        'open',
        'strong_buy',
        'buy',
        'hold',
        'sell',
        'strong_sell',
        'recommendation_date',
        'fetched_at',
    ];

    protected $casts = [
        'current_price' => 'decimal:2',
        'previous_close' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'change_percentage' => 'decimal:2',
        'high' => 'decimal:2',
        'low' => 'decimal:2',
        'open' => 'decimal:2',
        'volume' => 'integer',
        'strong_buy' => 'integer',
        'buy' => 'integer',
        'hold' => 'integer',
        'sell' => 'integer',
        'strong_sell' => 'integer',
        'recommendation_date' => 'date',
        'fetched_at' => 'datetime',
    ];

    /**
     * Get the stock that owns this quote
     */
    public function stock()
    {
        return $this->belongsTo(Stock::class, 'symbol', 'symbol');
    }

    /**
     * Get the latest quote for a symbol
     */
    public static function getLatestQuote(string $symbol): ?self
    {
        return static::where('symbol', $symbol)
            ->orderBy('fetched_at', 'desc')
            ->first();
    }

    /**
     * Get quotes for a symbol within a date range
     */
    public static function getQuotesForPeriod(string $symbol, Carbon $from, Carbon $to): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('symbol', $symbol)
            ->whereBetween('fetched_at', [$from, $to])
            ->orderBy('fetched_at', 'asc')
            ->get();
    }

    /**
     * Get total recommendation count
     */
    public function getTotalRecommendationsAttribute(): int
    {
        return $this->strong_buy + $this->buy + $this->hold + $this->sell + $this->strong_sell;
    }

    /**
     * Get recommendation percentage
     */
    public function getRecommendationPercentageAttribute(): float
    {
        $total = $this->total_recommendations;
        if ($total === 0) return 0;
        
        $positive = $this->strong_buy + $this->buy;
        return round(($positive / $total) * 100, 2);
    }

    /**
     * Get recommendation label
     */
    public function getRecommendationLabelAttribute(): string
    {
        $percentage = $this->recommendation_percentage;
        
        if ($percentage >= 70) return 'Strong Buy';
        if ($percentage >= 50) return 'Buy';
        if ($percentage >= 30) return 'Hold';
        if ($percentage >= 10) return 'Sell';
        return 'Strong Sell';
    }

    /**
     * Get recommendation color
     */
    public function getRecommendationColorAttribute(): string
    {
        $percentage = $this->recommendation_percentage;
        
        if ($percentage >= 70) return 'text-green-600';
        if ($percentage >= 50) return 'text-blue-600';
        if ($percentage >= 30) return 'text-yellow-600';
        if ($percentage >= 10) return 'text-orange-600';
        return 'text-red-600';
    }
}
