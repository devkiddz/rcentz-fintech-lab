<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketEnvironment extends Model
{
    protected $fillable = [
        'active_marketplace',
        'controlled_drive_mode',
        'controlled_drive_strength',
        'controlled_tick_seconds',
        'updated_by_user_id',
    ];

    protected $casts = [
        'controlled_drive_strength' => 'decimal:4',
        'controlled_tick_seconds' => 'integer',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'active_marketplace' => 'live',
                'controlled_drive_mode' => 'range',
                'controlled_drive_strength' => 1,
                'controlled_tick_seconds' => 60,
            ]
        );
    }
}
