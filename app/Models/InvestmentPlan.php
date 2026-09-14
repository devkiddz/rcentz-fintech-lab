<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'category',
        'risk_level',
        'minimum_investment',
        'management_fee',
        'expense_ratio',
        'nav',
        'nav_history',
        'last_nav_update',
        'nav_change_percentage',
        'previous_nav',
        'nav_change',
        'total_assets',
        'inception_date',
        'dividend_yield',
        'is_active',
        'is_featured',
        'image',
        'prospectus_url',
        'fact_sheet_url',
        'last_updated',
    ];

    protected $casts = [
        'nav' => 'decimal:4',
        'nav_history' => 'array',
        'last_nav_update' => 'datetime',
        'nav_change_percentage' => 'decimal:4',
        'previous_nav' => 'decimal:4',
        'nav_change' => 'decimal:4',
        'management_fee' => 'decimal:4',
        'expense_ratio' => 'decimal:4',
        'minimum_investment' => 'decimal:2',
        'total_assets' => 'decimal:2',
        'dividend_yield' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'inception_date' => 'date',
        'last_updated' => 'datetime',
    ];

    public function holdings()
    {
        return $this->hasMany(InvestmentHolding::class);
    }

    public function transactions()
    {
        return $this->hasMany(InvestmentTransaction::class);
    }

    public function watchlists()
    {
        return $this->hasMany(InvestmentWatchlist::class);
    }

    public function automaticInvestmentPlans()
    {
        return $this->hasMany(AutomaticInvestmentPlan::class);
    }

    public function category()
    {
        return $this->belongsTo(InvestmentCategory::class, 'category', 'name');
    }

    // Accessors
    public function getFormattedNavAttribute()
    {
        return '$' . number_format($this->nav, 4);
    }

    public function getFormattedManagementFeeAttribute()
    {
        return number_format($this->management_fee * 100, 2) . '%';
    }

    public function getFormattedExpenseRatioAttribute()
    {
        return number_format($this->expense_ratio * 100, 2) . '%';
    }

    public function getFormattedDividendYieldAttribute()
    {
        return $this->dividend_yield ? number_format($this->dividend_yield, 2) . '%' : 'N/A';
    }

    public function getNavChangeColorAttribute()
    {
        if ($this->nav_change_percentage > 0) {
            return 'text-green-600';
        } elseif ($this->nav_change_percentage < 0) {
            return 'text-red-600';
        }
        return 'text-gray-600';
    }

    public function getFormattedNavChangeAttribute()
    {
        if ($this->nav_change_percentage > 0) {
            return '+' . number_format($this->nav_change_percentage, 2) . '%';
        }
        return number_format($this->nav_change_percentage, 2) . '%';
    }

    public function getFormattedMinimumInvestmentAttribute()
    {
        return '$' . number_format($this->minimum_investment, 2);
    }

    public function getFormattedTotalAssetsAttribute()
    {
        if ($this->total_assets) {
            return '$' . number_format($this->total_assets, 0);
        }
        return 'N/A';
    }

    public function getRiskLevelBadgeAttribute()
    {
        switch ($this->risk_level) {
            case 'low':
                return 'bg-green-100 text-green-800';
            case 'medium':
                return 'bg-yellow-100 text-yellow-800';
            case 'high':
                return 'bg-red-100 text-red-800';
            case 'very_high':
                return 'bg-purple-100 text-purple-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
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

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByRiskLevel($query, $riskLevel)
    {
        return $query->where('risk_level', $riskLevel);
    }

    public function scopeTeslaFocused($query)
    {
        return $query->where('type', 'tesla_focused');
    }

    public function scopeESG($query)
    {
        return $query->where('type', 'esg');
    }

    public function scopeMutualFunds($query)
    {
        return $query->where('type', 'mutual_fund');
    }

    public function scopeETFs($query)
    {
        return $query->where('type', 'etf');
    }

    public function scopeRetirement($query)
    {
        return $query->where('type', 'retirement');
    }
}
