<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockCandle extends Model
{
    protected $fillable = [
        'symbol',
        'interval',
        'started_at',
        'open',
        'high',
        'low',
        'close',
        'sample_count',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'open' => 'decimal:6',
        'high' => 'decimal:6',
        'low' => 'decimal:6',
        'close' => 'decimal:6',
        'sample_count' => 'integer',
    ];
}
