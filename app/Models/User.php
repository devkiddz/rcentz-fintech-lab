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
        'date_of_birth',
        'employment_class',
        'education_level',
        'account_status',
        'status_reason',
        'status_until',
        'status_changed_at',
        'status_changed_by_user_id',
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
            'date_of_birth' => 'date',
            'status_until' => 'datetime',
            'status_changed_at' => 'datetime',
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

    public function accountAlerts()
    {
        return $this->hasMany(AccountAlert::class);
    }

    public function withdrawalTokenRequests()
    {
        return $this->hasMany(WithdrawalTokenRequest::class);
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

    public function financialActivities()
    {
        return $this->hasMany(FinancialActivity::class);
    }

    public function copyTraderProfile()
    {
        return $this->hasOne(CopyTraderProfile::class);
    }

    public function copyRelationships()
    {
        return $this->hasMany(CopyRelationship::class, 'follower_id');
    }

    public function copyFollowers()
    {
        return $this->hasMany(CopyRelationship::class, 'provider_id');
    }

    public function tradingBots()
    {
        return $this->hasMany(TradingBot::class);
    }

    public function strategyProviderApplications()
    {
        return $this->hasMany(StrategyProviderApplication::class);
    }

    public function botSubscriptions()
    {
        return $this->hasMany(BotSubscription::class);
    }


    public function isAdmin()
    {
        return $this->is_admin;
    }

    public function isAccountActive(): bool
    {
        if ($this->is_admin) return true;
        if ($this->account_status === 'suspended' && $this->status_until && now()->gte($this->status_until)) return true;
        return ($this->account_status ?? 'active') === 'active';
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
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
