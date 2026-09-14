<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AutomaticNavUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'investment_plan_id',
        'name',
        'description',
        'update_type', // 'increase' or 'decrease'
        'update_amount',
        'update_interval_value', // e.g., 2
        'update_interval_unit', // 'minutes', 'hours', 'days'
        'start_date',
        'end_date',
        'last_executed_at',
        'next_execution_at',
        'is_active',
        'total_executions',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'last_executed_at' => 'datetime',
        'next_execution_at' => 'datetime',
        'is_active' => 'boolean',
        'update_amount' => 'decimal:4',
        'total_executions' => 'integer',
        'update_interval_value' => 'integer',
    ];

    /**
     * Get the investment plan this automatic update belongs to
     */
    public function investmentPlan()
    {
        return $this->belongsTo(InvestmentPlan::class);
    }

    /**
     * Get the user who created this automatic update
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if the automatic update is due for execution
     */
    public function isDue(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();

        // Check if we're within the date range
        if ($now->lt($this->start_date) || $now->gt($this->end_date)) {
            return false;
        }

        // Check if it's time for next execution
        return $now->gte($this->next_execution_at);
    }

    /**
     * Calculate the next execution time based on interval
     */
    public function calculateNextExecution(): Carbon
    {
        $base = $this->last_executed_at ?? $this->start_date;
        
        // Ensure we're working with UTC for consistent calculations
        $base = $base->utc();
        
        switch ($this->update_interval_unit) {
            case 'minutes':
                return $base->copy()->addMinutes($this->update_interval_value);
            case 'hours':
                return $base->copy()->addHours($this->update_interval_value);
            case 'days':
                return $base->copy()->addDays($this->update_interval_value);
            default:
                return $base->copy()->addHours(1); // Default to 1 hour
        }
    }

    /**
     * Get the NAV change amount (positive for increase, negative for decrease)
     */
    public function getNavChangeAmount(): float
    {
        return $this->update_type === 'increase' 
            ? $this->update_amount 
            : -$this->update_amount;
    }

    /**
     * Get formatted interval description
     */
    public function getIntervalDescription(): string
    {
        $value = $this->update_interval_value;
        $unit = $this->update_interval_unit;
        
        if ($value == 1) {
            $unit = rtrim($unit, 's'); // Remove 's' for singular
        }
        
        return "{$value} {$unit}";
    }

    /**
     * Get status badge color
     */
    public function getStatusBadge(): string
    {
        if (!$this->is_active) {
            return 'bg-gray-100 text-gray-800';
        }

        $now = Carbon::now('UTC');
        
        if ($now->lt($this->start_date)) {
            return 'bg-yellow-100 text-yellow-800'; // Pending
        }
        
        if ($now->gt($this->end_date)) {
            return 'bg-red-100 text-red-800'; // Expired
        }
        
        return 'bg-green-100 text-green-800'; // Active
    }

    /**
     * Get status text
     */
    public function getStatusText(): string
    {
        if (!$this->is_active) {
            return 'Inactive';
        }

        $now = Carbon::now('UTC');
        
        if ($now->lt($this->start_date)) {
            return 'Pending';
        }
        
        if ($now->gt($this->end_date)) {
            return 'Expired';
        }
        
        return 'Active';
    }

    /**
     * Scope for active automatic updates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for due automatic updates
     */
    public function scopeDue($query)
    {
        $now = Carbon::now('UTC');
        
        return $query->where('is_active', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->where('next_execution_at', '<=', $now);
    }

    /**
     * Scope for specific investment plan
     */
    public function scopeForPlan($query, $planId)
    {
        return $query->where('investment_plan_id', $planId);
    }
}
