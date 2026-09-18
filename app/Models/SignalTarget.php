<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SignalTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'signal_id',
        'sequence',
        'price',
        'status',
        'hit_at',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'price' => 'decimal:8',
        'hit_at' => 'datetime',
    ];

    public function signal()
    {
        return $this->belongsTo(Signal::class);
    }
}
