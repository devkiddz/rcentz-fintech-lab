<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrokerOrder extends Model
{
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_EXECUTING = 'executing';
    public const STATUS_FILLED = 'filled';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'public_id',
        'user_id',
        'market_instrument_id',
        'market_execution_transaction_id',
        'marketplace',
        'side',
        'order_type',
        'quantity',
        'quantity_mode',
        'filled_quantity',
        'average_fill_price',
        'gross_value',
        'fee',
        'settlement_currency',
        'settlement_amount',
        'status',
        'time_in_force',
        'idempotency_key',
        'execution_source',
        'risk_controls',
        'failure_code',
        'failure_message',
        'accepted_at',
        'submitted_at',
        'filled_at',
        'failed_at',
        'cancelled_at',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:10',
        'filled_quantity' => 'decimal:10',
        'average_fill_price' => 'decimal:12',
        'gross_value' => 'decimal:8',
        'fee' => 'decimal:8',
        'settlement_amount' => 'decimal:8',
        'risk_controls' => 'array',
        'accepted_at' => 'datetime',
        'submitted_at' => 'datetime',
        'filled_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(){ return $this->belongsTo(User::class); }
    public function marketInstrument(){ return $this->belongsTo(MarketInstrument::class); }
    public function execution(){ return $this->belongsTo(MarketExecutionTransaction::class, 'market_execution_transaction_id'); }
    public function events(){ return $this->hasMany(BrokerOrderEvent::class); }

    public function getIsTerminalAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_FILLED,
            self::STATUS_REJECTED,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
        ], true);
    }
}
