<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockHolding extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'market_instrument_id',
        'stock_id',
        'marketplace',
        'quantity',
        'average_buy_price',
        'total_invested',
        'current_value',
        'unrealized_gain_loss',
        'unrealized_gain_loss_percentage',
        'last_notified_change_percentage',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'average_buy_price' => 'decimal:2',
        'total_invested' => 'decimal:2',
        'current_value' => 'decimal:2',
        'unrealized_gain_loss' => 'decimal:2',
        'unrealized_gain_loss_percentage' => 'decimal:2',
        'last_notified_change_percentage' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function marketInstrument()
    {
        return $this->belongsTo(MarketInstrument::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function getFormattedCurrentValueAttribute()
    {
        return '$' . number_format($this->current_value, 2);
    }

    public function getFormattedUnrealizedGainLossAttribute()
    {
        $value = $this->unrealized_gain_loss;
        $formatted = '$' . number_format(abs($value), 2);

        if ($value > 0) {
            return '+' . $formatted;
        } elseif ($value < 0) {
            return '-' . $formatted;
        }
        return $formatted;
    }

    public function getFormattedUnrealizedGainLossPercentageAttribute()
    {
        $percentage = $this->unrealized_gain_loss_percentage;
        $formatted = number_format($percentage, 2) . '%';

        if ($percentage > 0) {
            return '+' . $formatted;
        } elseif ($percentage < 0) {
            return '-' . $formatted;
        }
        return $formatted;
    }

    public function getGainLossColorAttribute()
    {
        if ($this->unrealized_gain_loss > 0) {
            return 'text-green-600';
        } elseif ($this->unrealized_gain_loss < 0) {
            return 'text-red-600';
        }
        return 'text-gray-600';
    }

    public function getFormattedQuantityAttribute()
    {
        return number_format($this->quantity, 6);
    }

    public function getFormattedAverageBuyPriceAttribute()
    {
        return '$' . number_format($this->average_buy_price, 2);
    }

    public function getFormattedTotalInvestedAttribute()
    {
        return '$' . number_format($this->total_invested, 2);
    }
}
