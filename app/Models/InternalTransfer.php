<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternalTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'sender_id',
        'recipient_id',
        'amount',
        'currency',
        'status',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
