<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'details',
        'logo',
        'barcode',
        'wallet_address',
        'crypto_symbol',
        'network_fee',
        'is_active',
        'allow_deposit',
        'allow_withdraw',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'network_fee' => 'decimal:8',
        'allow_deposit' => 'boolean',
        'allow_withdraw' => 'boolean',
    ];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTraditional($query)
    {
        return $query->where('type', 'traditional');
    }

    public function scopeCryptocurrency($query)
    {
        return $query->where('type', 'cryptocurrency');
    }

    public function scopeAllowDeposit($query)
    {
        return $query->where('allow_deposit', true);
    }

    public function scopeAllowWithdraw($query)
    {
        return $query->where('allow_withdraw', true);
    }

    public function isCryptocurrency()
    {
        return $this->type === 'cryptocurrency';
    }

    public function isTraditional()
    {
        return $this->type === 'traditional';
    }

    public function canDeposit()
    {
        return $this->allow_deposit;
    }

    public function canWithdraw()
    {
        return $this->allow_withdraw;
    }

    public function getLogoUrlAttribute()
    {
        if (!$this->logo) {
            return null;
        }

        // If it's a full URL, return as is
        if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
            return $this->logo;
        }

        // Otherwise, assume it's a storage path
        return Storage::url($this->logo);
    }

    public function getBarcodeDataAttribute()
    {
        if (!$this->barcode) {
            return null;
        }

        // If it's already a data URL, return as is
        if (strpos($this->barcode, 'data:') === 0) {
            return $this->barcode;
        }

        // If it's a file path, convert to URL
        if (Storage::exists($this->barcode)) {
            return Storage::url($this->barcode);
        }

        // Otherwise, assume it's raw QR code data
        return $this->barcode;
    }

    public function getFormattedNetworkFeeAttribute()
    {
        if (!$this->network_fee || !$this->crypto_symbol) {
            return null;
        }

        return number_format($this->network_fee, 8) . ' ' . strtoupper($this->crypto_symbol);
    }
}
