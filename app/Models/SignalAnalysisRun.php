<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SignalAnalysisRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'signal_id',
        'stock_id',
        'marketplace',
        'trigger',
        'source',
        'timeframe',
        'result',
        'confluence_score',
        'market_price',
        'context',
        'conclusion',
        'analyzed_at',
        'actor_user_id',
    ];

    protected $casts = [
        'confluence_score' => 'decimal:2',
        'market_price' => 'decimal:8',
        'context' => 'array',
        'conclusion' => 'array',
        'analyzed_at' => 'datetime',
    ];

    public function signal()
    {
        return $this->belongsTo(Signal::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
