<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LanguageTranslation extends Model
{
    protected $fillable = [
        'language_id', 'group', 'key', 'source_text', 'translated_text', 'status', 'is_locked',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
