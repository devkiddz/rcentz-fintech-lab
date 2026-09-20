<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardAuditLog extends Model
{
    protected $fillable = [
        'reward_campaign_id', 'reward_grant_id', 'actor_user_id', 'target_user_id',
        'action', 'reference', 'reason', 'metadata', 'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function campaign(){ return $this->belongsTo(RewardCampaign::class, 'reward_campaign_id'); }
    public function grant(){ return $this->belongsTo(RewardGrant::class, 'reward_grant_id'); }
    public function actor(){ return $this->belongsTo(User::class, 'actor_user_id'); }
    public function targetUser(){ return $this->belongsTo(User::class, 'target_user_id'); }
}
