<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomaticInvestmentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'investment_plan_id',
        'amount',
        'frequency',
        'day_of_month',
        'is_active',
        'next_investment_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'next_investment_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function investmentPlan()
    {
        return $this->belongsTo(InvestmentPlan::class);
    }

    public function getFormattedAmountAttribute()
    {
        return '$' . number_format($this->amount, 2);
    }

    public function getFrequencyLabelAttribute()
    {
        $labels = [
            'weekly' => 'Weekly',
            'biweekly' => 'Bi-weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
        ];

        return $labels[$this->frequency] ?? ucfirst($this->frequency);
    }

    public function getFormattedNextInvestmentDateAttribute()
    {
        return $this->next_investment_date ? $this->next_investment_date->format('M j, Y') : 'Not scheduled';
    }

    public function calculateNextInvestmentDate()
    {
        $date = $this->next_investment_date ?? now();

        switch ($this->frequency) {
            case 'weekly':
                return $date->addWeek();
            case 'biweekly':
                return $date->addWeeks(2);
            case 'monthly':
                return $date->addMonth();
            case 'quarterly':
                return $date->addMonths(3);
            default:
                return $date->addMonth();
        }
    }

    public function isDueForInvestment()
    {
        if (!$this->is_active || !$this->next_investment_date) {
            return false;
        }

        return $this->next_investment_date->isPast();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDueForInvestment($query)
    {
        return $query->where('is_active', true)
            ->where('next_investment_date', '<=', now());
    }
}
