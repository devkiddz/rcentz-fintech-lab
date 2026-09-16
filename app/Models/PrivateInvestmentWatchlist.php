<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateInvestmentWatchlist extends Model
{
    protected $fillable = [
        'user_id','instrument_id','target_price','priority','note','last_reviewed_at',
    ];

    protected $casts = [
        'target_price'=>'decimal:6',
        'last_reviewed_at'=>'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(PrivateInvestmentInstrument::class,'instrument_id');
    }
}
