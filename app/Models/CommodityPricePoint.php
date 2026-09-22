<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommodityPricePoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'commodity_instrument_id',
        'interval',
        'timestamp',
        'price',
        'source',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'price' => 'decimal:12',
    ];

    public function commodityInstrument()
    {
        return $this->belongsTo(CommodityInstrument::class);
    }
}
