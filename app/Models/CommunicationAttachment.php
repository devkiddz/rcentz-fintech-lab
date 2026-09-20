<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationAttachment extends Model
{
    protected $fillable = [
        'message_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'checksum',
        'revoked_at',
        'revoked_by_user_id',
    ];

    protected $casts = [
        'revoked_at' => 'datetime',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(CommunicationMessage::class, 'message_id');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_user_id');
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with(strtolower((string) $this->mime_type), 'image/');
    }

    public function getIsPdfAttribute(): bool
    {
        return strtolower((string) $this->mime_type) === 'application/pdf';
    }
}
