<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    protected $fillable = [
        'code', 'name', 'native_name', 'direction', 'is_enabled', 'is_default',
        'is_major', 'sort_order', 'metadata',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_default' => 'boolean',
        'is_major' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(LanguageTranslation::class);
    }
}
