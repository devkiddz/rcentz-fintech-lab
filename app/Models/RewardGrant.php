<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardGrant extends Model
{
    protected $fillable = [
        'reward_campaign_id', 'user_id', 'wallet_transaction_id', 'financial_activity_id',
        'granted_by_user_id', 'source_type', 'source_reference', 'reward_kind', 'amount',
        'currency', 'non_cash_payload', 'status', 'reference', 'idempotency_key', 'metadata', 'granted_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'non_cash_payload' => 'array',
        'metadata' => 'array',
        'granted_at' => 'datetime',
    ];

    public function campaign(){ return $this->belongsTo(RewardCampaign::class, 'reward_campaign_id'); }
    public function user(){ return $this->belongsTo(User::class); }
    public function walletTransaction(){ return $this->belongsTo(WalletTransaction::class); }
    public function financialActivity(){ return $this->belongsTo(FinancialActivity::class); }
    public function grantedBy(){ return $this->belongsTo(User::class, 'granted_by_user_id'); }
}
