<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAccountOperation extends Model
{
    protected $fillable = [
        'user_id',
        'admin_id',
        'operation_type',
        'direction',
        'amount',
        'effective_at',
        'reference',
        'label',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'effective_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
