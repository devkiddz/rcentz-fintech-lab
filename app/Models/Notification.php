<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    protected $appends = [
        'icon',
        'color',
        'formatted_time',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedTimeAttribute()
    {
        $now = now();
        $diff = abs($now->diffInSeconds($this->created_at));

        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            return round(abs($now->diffInMinutes($this->created_at))) . ' minutes ago';
        } elseif ($diff < 86400) {
            return round(abs($now->diffInHours($this->created_at))) . ' hours ago';
        } else {
            return $this->created_at->format('M j, Y');
        }
    }

    public function getIconAttribute()
    {
        $icons = [
            'stock_update' => 'trending-up',
            'investment_success' => 'check-circle',
            'investment_update' => 'trending-up-down',
            'wallet_update' => 'wallet',
            'kyc_status' => 'shield-check',
            'automatic_investment' => 'repeat',
            'price_alert' => 'bell',
            'signal' => 'radio-tower',
            'signal_admin' => 'circle-check-big',
            'system' => 'info',
        ];

        return $icons[$this->type] ?? 'info';
    }

    public function getColorAttribute()
    {
        $colors = [
            'stock_update' => 'blue',
            'investment_success' => 'green',
            'investment_update' => 'blue',
            'wallet_update' => 'purple',
            'kyc_status' => 'yellow',
            'automatic_investment' => 'green',
            'price_alert' => 'orange',
            'signal' => 'red',
            'signal_admin' => 'green',
            'system' => 'gray',
        ];

        return $colors[$this->type] ?? 'gray';
    }

    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }
}
