<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CommunicationConversation extends Model
{
    protected $fillable = [
        'public_id',
        'type',
        'ticket_number',
        'subject',
        'category',
        'status',
        'priority',
        'created_by_user_id',
        'assigned_to_user_id',
        'idempotency_key',
        'metadata',
        'last_message_at',
        'closed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_message_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(CommunicationParticipant::class, 'conversation_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CommunicationMessage::class, 'conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(CommunicationMessage::class, 'conversation_id')->latestOfMany();
    }

    public function events(): HasMany
    {
        return $this->hasMany(CommunicationEvent::class, 'conversation_id');
    }

    public function scopeSupportTickets($query)
    {
        return $query->where('type', 'support_ticket');
    }
}
