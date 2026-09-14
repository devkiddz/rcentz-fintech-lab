<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentWatchlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'investment_plan_id',
        'alert_nav',
        'alert_type',
    ];

    protected $casts = [
        'alert_nav' => 'decimal:4',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function investmentPlan()
    {
        return $this->belongsTo(InvestmentPlan::class);
    }

    public function getFormattedAlertNavAttribute()
    {
        return $this->alert_nav ? '$' . number_format($this->alert_nav, 4) : 'N/A';
    }

    public function getAlertTypeLabelAttribute()
    {
        return ucfirst($this->alert_type);
    }

    public function isAlertTriggered()
    {
        if (!$this->alert_nav || !$this->alert_type) {
            return false;
        }

        $currentNav = $this->investmentPlan->nav;

        if ($this->alert_type === 'above') {
            return $currentNav >= $this->alert_nav;
        } elseif ($this->alert_type === 'below') {
            return $currentNav <= $this->alert_nav;
        }

        return false;
    }
}
