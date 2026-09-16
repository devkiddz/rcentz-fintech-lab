<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateInvestmentPrice extends Model
{
    protected $fillable = [
        'instrument_id','event_id','timeframe','open','high','low','close',
        'change_amount','change_percent','source','recorded_at',
    ];

    protected $casts = [
        'open'=>'decimal:6','high'=>'decimal:6','low'=>'decimal:6','close'=>'decimal:6',
        'change_amount'=>'decimal:6','change_percent'=>'decimal:6','recorded_at'=>'datetime',
    ];

    public function instrument(): BelongsTo { return $this->belongsTo(PrivateInvestmentInstrument::class, 'instrument_id'); }
    public function event(): BelongsTo { return $this->belongsTo(PrivateInvestmentEvent::class, 'event_id'); }
}
