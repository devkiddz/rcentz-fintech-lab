<?php

namespace App\Models;

use App\Services\MarketPriceRouter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'company_name',
        'sector',
        'industry',
        'logo_url',
        'current_price',
        'previous_close',
        'change_amount',
        'change_percentage',
        'volume',
        'high',
        'low',
        'open',
        'market_cap',
        'pe_ratio',
        'dividend_yield',
        'fifty_two_week_high',
        'fifty_two_week_low',
        'is_active',
        'is_featured',
        'external_feed_enabled',
        'last_updated',
        'volume_updated_at',
    ];

    protected $casts = [
        'current_price' => 'decimal:2',
        'previous_close' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'change_percentage' => 'decimal:2',
        'volume' => 'integer',
        'market_cap' => 'decimal:2',
        'pe_ratio' => 'decimal:2',
        'dividend_yield' => 'decimal:2',
        'fifty_two_week_high' => 'decimal:2',
        'fifty_two_week_low' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'external_feed_enabled' => 'boolean',
        'last_updated' => 'datetime',
        'volume_updated_at' => 'datetime',
    ];

    public function holdings()
    {
        return $this->hasMany(StockHolding::class);
    }

    public function transactions()
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function watchlists()
    {
        return $this->hasMany(StockWatchlist::class);
    }

    public function priceHistory()
    {
        return $this->hasMany(StockPriceHistory::class, 'symbol', 'symbol');
    }

    /**
     * Public market price follows the active marketplace authority.
     * The raw database column remains the latest Live feed price.
     */
    public function getCurrentPriceAttribute($value)
    {
        try {
            if (app()->bound(MarketPriceRouter::class)) {
                return app(MarketPriceRouter::class)->displayPrice($this, (float) $value);
            }
        } catch (\Throwable) {
            // During boot/migrations, fall back to the persisted Live value.
        }

        return $value;
    }

    public function getLiveCurrentPriceAttribute(): float
    {
        return (float) ($this->getRawOriginal('current_price') ?? 0);
    }

    // Accessors
    public function getFormattedCurrentPriceAttribute()
    {
        return '$' . number_format($this->current_price, 2);
    }

    public function getFormattedChangeAmountAttribute()
    {
        if ($this->change_amount > 0) {
            return '+' . '$' . number_format($this->change_amount, 2);
        }
        return '$' . number_format($this->change_amount, 2);
    }

    public function getFormattedChangePercentageAttribute()
    {
        if ($this->change_percentage > 0) {
            return '+' . number_format($this->change_percentage, 2) . '%';
        }
        return number_format($this->change_percentage, 2) . '%';
    }

    public function getChangeColorAttribute()
    {
        if ($this->change_percentage > 0) {
            return 'text-green-600';
        } elseif ($this->change_percentage < 0) {
            return 'text-red-600';
        }
        return 'text-gray-600';
    }

    public function getFormattedVolumeAttribute()
    {
        if ($this->volume >= 1000000) {
            return number_format($this->volume / 1000000, 1) . 'M';
        } elseif ($this->volume >= 1000) {
            return number_format($this->volume / 1000, 1) . 'K';
        }
        return number_format($this->volume);
    }

    public function getFormattedMarketCapAttribute()
    {
        if ($this->market_cap >= 1000000000000) {
            return '$' . number_format($this->market_cap / 1000000000000, 1) . 'T';
        } elseif ($this->market_cap >= 1000000000) {
            return '$' . number_format($this->market_cap / 1000000000, 1) . 'B';
        } elseif ($this->market_cap >= 1000000) {
            return '$' . number_format($this->market_cap / 1000000, 1) . 'M';
        }
        return '$' . number_format($this->market_cap);
    }

    public function getFormattedPeRatioAttribute()
    {
        return $this->pe_ratio ? number_format($this->pe_ratio, 2) : 'N/A';
    }

    public function getFormattedDividendYieldAttribute()
    {
        return $this->dividend_yield ? number_format($this->dividend_yield, 2) . '%' : 'N/A';
    }

    public function getFormattedFiftyTwoWeekHighAttribute()
    {
        return $this->fifty_two_week_high ? '$' . number_format($this->fifty_two_week_high, 2) : 'N/A';
    }

    public function getFormattedFiftyTwoWeekLowAttribute()
    {
        return $this->fifty_two_week_low ? '$' . number_format($this->fifty_two_week_low, 2) : 'N/A';
    }

    public function getFormattedLastUpdatedAttribute()
    {
        return $this->last_updated ? $this->last_updated->format('M j, Y g:i A') : 'N/A';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeBySector($query, $sector)
    {
        return $query->where('sector', $sector);
    }

    public function scopeByIndustry($query, $industry)
    {
        return $query->where('industry', $industry);
    }

    public function scopeGainers($query)
    {
        return $query->where('change_percentage', '>', 0);
    }

    public function scopeLosers($query)
    {
        return $query->where('change_percentage', '<', 0);
    }

    public function scopeMostActive($query)
    {
        return $query->orderBy('volume', 'desc');
    }

    public function scopeBySymbol($query, $symbol)
    {
        return $query->where('symbol', 'LIKE', "%{$symbol}%");
    }

    public function scopeByCompanyName($query, $name)
    {
        return $query->where('company_name', 'LIKE', "%{$name}%");
    }
}
