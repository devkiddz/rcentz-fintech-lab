<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SignalDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'distribution_id',
        'signal_id',
        'user_id',
        'reason',
        'delivered_at',
        'read_at',
        'metadata',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function distribution()
    {
        return $this->belongsTo(SignalDistribution::class, 'distribution_id');
    }

    public function signal()
    {
        return $this->belongsTo(Signal::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
