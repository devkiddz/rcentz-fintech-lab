<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateInvestmentTransaction extends Model
{
    protected $fillable = [
        'user_id','instrument_id','holding_id','type','units','price_per_unit','gross_amount',
        'fee','net_amount','status','reference','metadata','executed_at',
    ];

    protected $casts = [
        'units'=>'decimal:6','price_per_unit'=>'decimal:6','gross_amount'=>'decimal:2',
        'fee'=>'decimal:2','net_amount'=>'decimal:2','metadata'=>'array','executed_at'=>'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function instrument(): BelongsTo { return $this->belongsTo(PrivateInvestmentInstrument::class, 'instrument_id'); }
    public function holding(): BelongsTo { return $this->belongsTo(PrivateInvestmentHolding::class, 'holding_id'); }
}
