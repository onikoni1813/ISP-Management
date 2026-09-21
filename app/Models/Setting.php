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
        'group',
    ];

    /**
     * Get a setting value with caching and default fallback.
     */
    public static function get(string $key, $default = null): ?string
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value and invalidate cache.
     */
    public static function set(string $key, $value, string $group = 'general'): self
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        Cache::forget("setting_{$key}");
        Cache::forget("all_settings");

        return $setting;
    }

    /**
     * Get all settings as key-value pairs.
     */
    public static function getAll(): array
    {
        return Cache::remember("all_settings", 3600, function () {
            return static::pluck('value', 'key')->toArray();
        });
    }
}
