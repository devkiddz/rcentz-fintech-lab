<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForexCandle extends Model
{
    use HasFactory;

    protected $fillable = [
        'forex_pair_id',
        'interval',
        'timestamp',
        'open',
        'high',
        'low',
        'close',
        'volume',
        'source',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'open' => 'decimal:8',
        'high' => 'decimal:8',
        'low' => 'decimal:8',
        'close' => 'decimal:8',
        'volume' => 'decimal:4',
    ];

    public function pair()
    {
        return $this->belongsTo(ForexPair::class, 'forex_pair_id');
    }
}
