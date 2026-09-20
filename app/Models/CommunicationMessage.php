<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunicationMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_user_id',
        'body',
        'kind',
        'reply_to_message_id',
        'client_message_key',
        'sent_at',
        'edited_at',
        'deleted_for_everyone_at',
        'deleted_by_user_id',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'edited_at' => 'datetime',
        'deleted_for_everyone_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(CommunicationConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_message_id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by_user_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CommunicationAttachment::class, 'message_id');
    }

    public function userStates(): HasMany
    {
        return $this->hasMany(CommunicationMessageUserState::class, 'message_id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query
            ->whereNull('deleted_for_everyone_at')
            ->whereDoesntHave('userStates', function ($state) use ($user) {
                $state->where('user_id', $user->id)->whereNotNull('hidden_at');
            });
    }

    public function getIsDeletedForEveryoneAttribute(): bool
    {
        return $this->deleted_for_everyone_at !== null;
    }
}
