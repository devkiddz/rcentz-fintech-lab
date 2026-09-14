<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LinkedWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'network',
        'address',
        'label',
        'is_primary',
        'status',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getMaskedAddressAttribute(): string
    {
        $address = (string) $this->address;
        if (strlen($address) <= 14) {
            return $address;
        }

        return substr($address, 0, 7) . '…' . substr($address, -5);
    }
}
