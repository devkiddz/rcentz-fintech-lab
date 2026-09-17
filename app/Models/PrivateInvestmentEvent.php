<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateInvestmentEvent extends Model
{
    protected $fillable = [
        'instrument_id','asset_id','event_type','direction','adjustment_type','adjustment_value',
        'previous_price','new_price','reason','approval_state','created_by_user_id','metadata','effective_at',
    ];

    protected $casts = [
        'adjustment_value'=>'decimal:6','previous_price'=>'decimal:6','new_price'=>'decimal:6',
        'metadata'=>'array','effective_at'=>'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (PrivateInvestmentEvent $event) {
            PrivateInvestmentAuditLog::query()->create([
                'actor_user_id' => $event->created_by_user_id,
                'target_user_id' => null,
                'instrument_id' => $event->instrument_id,
                'action' => 'valuation.'.$event->event_type,
                'reference' => 'PINV-EVT-'.$event->id,
                'reason' => $event->reason,
                'metadata' => [
                    'event_id' => $event->id,
                    'asset_id' => $event->asset_id,
                    'direction' => $event->direction,
                    'adjustment_type' => $event->adjustment_type,
                    'adjustment_value' => (string) $event->adjustment_value,
                    'previous_price' => (string) $event->previous_price,
                    'new_price' => (string) $event->new_price,
                    'approval_state' => $event->approval_state,
                ],
                'occurred_at' => $event->effective_at ?? now(),
            ]);
        });
    }

    public function instrument(): BelongsTo { return $this->belongsTo(PrivateInvestmentInstrument::class, 'instrument_id'); }
    public function asset(): BelongsTo { return $this->belongsTo(PrivateInvestmentAsset::class, 'asset_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
}
