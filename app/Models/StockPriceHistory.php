<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StockPriceHistory extends Model
{
    use HasFactory;

    protected $table = 'stock_price_history';

    protected $fillable = [
        'symbol',
        'open',
        'high',
        'low',
        'close',
        'volume',
        'timestamp',
        'interval',
    ];

    protected $casts = [
        'open' => 'decimal:2',
        'high' => 'decimal:2',
        'low' => 'decimal:2',
        'close' => 'decimal:2',
        'volume' => 'integer',
        'timestamp' => 'datetime',
    ];

    /**
     * Get historical data for a symbol
     */
    public static function getHistoryForSymbol(string $symbol, string $interval = '1D', int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        $from = Carbon::now()->subDays($days);
        
        return static::where('symbol', $symbol)
            ->where('interval', $interval)
            ->where('timestamp', '>=', $from)
            ->orderBy('timestamp', 'asc')
            ->get();
    }

    /**
     * Get the latest price for a symbol
     */
    public static function getLatestPrice(string $symbol, string $interval = '1D'): ?self
    {
        return static::where('symbol', $symbol)
            ->where('interval', $interval)
            ->orderBy('timestamp', 'desc')
            ->first();
    }

    /**
     * Calculate price change percentage
     */
    public function getPriceChangePercentageAttribute(): float
    {
        if ($this->open == 0) return 0;
        return round((($this->close - $this->open) / $this->open) * 100, 2);
    }

    /**
     * Get formatted volume
     */
    public function getFormattedVolumeAttribute(): string
    {
        if (!$this->volume) return 'N/A';
        
        if ($this->volume >= 1000000000) {
            return round($this->volume / 1000000000, 1) . 'B';
        } elseif ($this->volume >= 1000000) {
            return round($this->volume / 1000000, 1) . 'M';
        } elseif ($this->volume >= 1000) {
            return round($this->volume / 1000, 1) . 'K';
        }
        
        return number_format($this->volume);
    }
}
