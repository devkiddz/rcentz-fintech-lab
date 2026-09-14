<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'car_id',
        'payment_method_id',
        'amount',
        'billing_name',
        'billing_email',
        'billing_phone',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'company_name',
        'tax_id',
        'transaction_hash',
        'crypto_amount',
        'crypto_currency',
        'exchange_rate',
        'status',
        'purchased_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'crypto_amount' => 'decimal:8',
        'exchange_rate' => 'decimal:8',
        'purchased_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function getFormattedAmountAttribute()
    {
        return '$' . number_format($this->amount, 2);
    }

    public function getFormattedCryptoAmountAttribute()
    {
        if (!$this->crypto_amount || !$this->crypto_currency) {
            return null;
        }

        return number_format($this->crypto_amount, 8) . ' ' . strtoupper($this->crypto_currency);
    }

    public function getFullBillingAddressAttribute()
    {
        $address = $this->billing_address;
        
        if ($this->billing_city) {
            $address .= ', ' . $this->billing_city;
        }
        
        if ($this->billing_state) {
            $address .= ', ' . $this->billing_state;
        }
        
        if ($this->billing_postal_code) {
            $address .= ' ' . $this->billing_postal_code;
        }
        
        if ($this->billing_country) {
            $address .= ', ' . $this->billing_country;
        }
        
        return $address;
    }

    public function isCryptoPurchase()
    {
        return !empty($this->crypto_currency) && !empty($this->crypto_amount);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'processing' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            'refunded' => 'bg-gray-100 text-gray-800',
        ];

        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }
}
