<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Impersonate;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'profile_image',
        'is_admin',
        'country',
        'currency',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function investmentHoldings()
    {
        return $this->hasMany(InvestmentHolding::class);
    }

    public function investmentTransactions()
    {
        return $this->hasMany(InvestmentTransaction::class);
    }

    public function stockHoldings()
    {
        return $this->hasMany(StockHolding::class);
    }

    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function investmentWatchlist()
    {
        return $this->hasMany(InvestmentWatchlist::class);
    }

    public function stockWatchlist()
    {
        return $this->hasMany(StockWatchlist::class);
    }

    public function automaticInvestmentPlans()
    {
        return $this->hasMany(AutomaticInvestmentPlan::class);
    }

    public function kyc()
    {
        return $this->hasOne(KYC::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function sentTransfers()
    {
        return $this->hasMany(InternalTransfer::class, 'sender_id');
    }

    public function receivedTransfers()
    {
        return $this->hasMany(InternalTransfer::class, 'recipient_id');
    }

    public function linkedWallets()
    {
        return $this->hasMany(LinkedWallet::class);
    }

    public function isAdmin()
    {
        return $this->is_admin;
    }

    /**
     * Check if the user can impersonate another user.
     * Only admins can impersonate users.
     */
    public function canImpersonate()
    {
        return $this->isAdmin();
    }

    /**
     * Check if the user can be impersonated.
     * Admins cannot be impersonated.
     */
    public function canBeImpersonated()
    {
        return !$this->isAdmin();
    }
}
