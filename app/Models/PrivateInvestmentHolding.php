<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateInvestmentHolding extends Model
{
    protected $fillable = [
        'user_id','instrument_id','units','average_entry_price','cost_basis','current_value',
        'unrealized_profit_loss','unrealized_return_percent','realized_profit_loss','status',
        'started_at','locked_until','closed_at',
    ];

    protected $casts = [
        'units'=>'decimal:6','average_entry_price'=>'decimal:6','cost_basis'=>'decimal:2',
        'current_value'=>'decimal:2','unrealized_profit_loss'=>'decimal:2',
        'unrealized_return_percent'=>'decimal:6','realized_profit_loss'=>'decimal:2',
        'started_at'=>'datetime','locked_until'=>'datetime','closed_at'=>'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function instrument(): BelongsTo { return $this->belongsTo(PrivateInvestmentInstrument::class, 'instrument_id'); }
}
