<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateInvestmentAsset extends Model
{
    protected $fillable = [
        'instrument_id','market_instrument_id','asset_type','name','description','acquisition_value','current_valuation',
        'ownership_percentage','status','acquired_at','effective_at','notes',
    ];

    protected $casts = [
        'acquisition_value'=>'decimal:2','current_valuation'=>'decimal:2',
        'ownership_percentage'=>'decimal:4','acquired_at'=>'date','effective_at'=>'datetime',
    ];

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(PrivateInvestmentInstrument::class, 'instrument_id');
    }

    public function marketInstrument(): BelongsTo
    {
        return $this->belongsTo(MarketInstrument::class, 'market_instrument_id');
    }
}
