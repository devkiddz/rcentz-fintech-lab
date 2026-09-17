<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountAlert extends Model
{
    protected $fillable = [
        'user_id','created_by_user_id','type','priority','title','message',
        'action_url','action_label','metadata','read_at','dismissed_at','expires_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
        'dismissed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }

    public function scopeActive($query)
    {
        return $query->whereNull('dismissed_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
}
