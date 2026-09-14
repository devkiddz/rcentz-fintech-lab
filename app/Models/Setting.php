<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Get a setting value by key
     */
    public static function get($key, $default = null)
    {
        $cacheKey = "setting_{$key}";

        try {
            return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
                $setting = static::where('key', $key)->first();

                return $setting ? $setting->value : $default;
            });
        } catch (\Illuminate\Database\QueryException|\PDOException $exception) {
            // During first deployment the database/settings table may not exist yet.
            // Public/auth layouts should still render with application defaults so
            // the hosting problem can be diagnosed instead of becoming a theme 500.
            return $default;
        }
    }

    /**
     * Set a setting value by key
     */
    public static function set($key, $value)
    {
        $setting = static::where('key', $key)->first();
        
        if ($setting) {
            $setting->update(['value' => $value]);
        } else {
            $setting = static::create([
                'key' => $key,
                'value' => $value,
                'type' => 'text',
                'group' => 'general',
                'label' => ucwords(str_replace('_', ' ', $key)),
            ]);
        }

        // Clear cache
        Cache::forget("setting_{$key}");
        
        return $setting;
    }

    /**
     * Get all settings grouped by their group
     */
    public static function getGrouped()
    {
        return static::orderBy('group')
            ->orderBy('label')
            ->get()
            ->groupBy('group');
    }

    /**
     * Get all public settings
     */
    public static function getPublic()
    {
        return static::where('is_public', true)
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Check if a setting is enabled (for checkboxes)
     */
    public static function isEnabled($key)
    {
        return static::get($key) == '1';
    }

    /**
     * Get settings by group
     */
    public static function getByGroup($group)
    {
        return static::where('group', $group)
            ->orderBy('label')
            ->get();
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache()
    {
        $settings = static::all();
        foreach ($settings as $setting) {
            Cache::forget("setting_{$setting->key}");
        }
    }

    /**
     * Get the formatted value based on type
     */
    public function getFormattedValueAttribute()
    {
        switch ($this->type) {
            case 'checkbox':
                return $this->value == '1';
            case 'image':
                return $this->value ? asset('storage/' . $this->value) : null;
            default:
                return $this->value;
        }
    }

    /**
     * Check if the setting is a checkbox type
     */
    public function isCheckbox()
    {
        return $this->type === 'checkbox';
    }

    /**
     * Check if the setting is an image type
     */
    public function isImage()
    {
        return $this->type === 'image';
    }

    /**
     * Check if the setting is a textarea type
     */
    public function isTextarea()
    {
        return $this->type === 'textarea';
    }
}
