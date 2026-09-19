<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketExecutionTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'market_instrument_id',
        'wallet_transaction_id',
        'trade_position_id',
        'native_type',
        'native_id',
        'idempotency_key',
        'side',
        'execution_source',
        'marketplace',
        'quantity',
        'price',
        'gross_value',
        'settlement_currency',
        'settlement_amount',
        'fee',
        'realized_profit_loss',
        'status',
        'executed_at',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:8',
        'price' => 'decimal:8',
        'gross_value' => 'decimal:8',
        'settlement_amount' => 'decimal:8',
        'fee' => 'decimal:8',
        'realized_profit_loss' => 'decimal:8',
        'executed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(){ return $this->belongsTo(User::class); }
    public function marketInstrument(){ return $this->belongsTo(MarketInstrument::class); }
    public function walletTransaction(){ return $this->belongsTo(WalletTransaction::class); }
    public function tradePosition(){ return $this->belongsTo(TradePosition::class); }
}
