<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoCandle extends Model
{
    use HasFactory;

    protected $fillable = [
        'crypto_pair_id',
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
        'open' => 'decimal:12',
        'high' => 'decimal:12',
        'low' => 'decimal:12',
        'close' => 'decimal:12',
        'volume' => 'decimal:8',
    ];

    public function pair()
    {
        return $this->belongsTo(CryptoPair::class, 'crypto_pair_id');
    }
}
