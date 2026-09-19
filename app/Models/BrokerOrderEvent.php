<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrokerOrderEvent extends Model
{
    protected $fillable = [
        'broker_order_id',
        'actor_id',
        'actor_type',
        'event_type',
        'from_status',
        'to_status',
        'note',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function order(){ return $this->belongsTo(BrokerOrder::class, 'broker_order_id'); }
    public function actor(){ return $this->belongsTo(User::class, 'actor_id'); }
}
