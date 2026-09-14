<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockWatchlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'stock_id',
        'alert_price',
        'alert_type',
    ];

    protected $casts = [
        'alert_price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function getFormattedAlertPriceAttribute()
    {
        return $this->alert_price ? '$' . number_format($this->alert_price, 2) : 'N/A';
    }

    public function getAlertTypeLabelAttribute()
    {
        return ucfirst($this->alert_type);
    }

    public function isAlertTriggered()
    {
        if (!$this->alert_price || !$this->alert_type) {
            return false;
        }

        $currentPrice = $this->stock->current_price;

        if ($this->alert_type === 'above') {
            return $currentPrice >= $this->alert_price;
        } elseif ($this->alert_type === 'below') {
            return $currentPrice <= $this->alert_price;
        }

        return false;
    }
}
