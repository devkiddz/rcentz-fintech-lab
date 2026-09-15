<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTradePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'stock_id',
        'source_transaction_id',
        'executed_transaction_id',
        'source_type',
        'planned_action',
        'mode',
        'duration_minutes',
        'quantity',
        'due_at',
        'status',
        'notified_at',
        'completed_at',
        'failure_reason',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'due_at' => 'datetime',
        'notified_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(){ return $this->belongsTo(User::class); }
    public function stock(){ return $this->belongsTo(Stock::class); }
    public function sourceTransaction(){ return $this->belongsTo(StockTransaction::class, 'source_transaction_id'); }
    public function executedTransaction(){ return $this->belongsTo(StockTransaction::class, 'executed_transaction_id'); }

    public function getIsDueAttribute(): bool
    {
        return in_array($this->status, ['due','completed','failed'], true)
            || ($this->due_at && $this->due_at->isPast());
    }

    public function getRemainingSecondsAttribute(): int
    {
        if (! $this->due_at || $this->due_at->isPast()) return 0;
        return max(0, now()->diffInSeconds($this->due_at, false));
    }
}
