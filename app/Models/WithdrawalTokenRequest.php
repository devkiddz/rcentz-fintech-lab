<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalTokenRequest extends Model
{
    protected $fillable = [
        'user_id','amount','note','status','token_hash','token_last_four',
        'token_generated_at','token_expires_at','token_verified_at','used_at',
        'generated_by_user_id','account_alert_id','wallet_transaction_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'token_generated_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'token_verified_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function generator(): BelongsTo { return $this->belongsTo(User::class, 'generated_by_user_id'); }
    public function alert(): BelongsTo { return $this->belongsTo(AccountAlert::class, 'account_alert_id'); }
    public function walletTransaction(): BelongsTo { return $this->belongsTo(WalletTransaction::class); }
}
