<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentHolding extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'investment_plan_id',
        'units',
        'average_cost',
        'total_invested',
        'current_value',
        'unrealized_gain_loss',
        'unrealized_gain_loss_percentage',
        'last_notified_change_percentage',
        'nav_at_purchase',
    ];

    protected $casts = [
        'units' => 'decimal:6',
        'average_cost' => 'decimal:4',
        'total_invested' => 'decimal:2',
        'current_value' => 'decimal:2',
        'unrealized_gain_loss' => 'decimal:2',
        'unrealized_gain_loss_percentage' => 'decimal:2',
        'last_notified_change_percentage' => 'decimal:4',
        'nav_at_purchase' => 'decimal:4',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function investmentPlan()
    {
        return $this->belongsTo(InvestmentPlan::class);
    }

    public function getFormattedCurrentValueAttribute()
    {
        return '$' . number_format($this->current_value, 2);
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

    public function getFormattedUnitsAttribute()
    {
        return number_format($this->units, 6);
    }

    public function getFormattedAverageCostAttribute()
    {
        return '$' . number_format($this->average_cost, 4);
    }

    public function getFormattedTotalInvestedAttribute()
    {
        return '$' . number_format($this->total_invested, 2);
    }
}
