<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    /** @use HasFactory<\Database\Factories\SettingFactory> */
    use HasFactory;

    protected $fillable = ['key', 'value', 'group', 'type'];

    protected $casts = [
        'value' => 'string',
    ];

    /**
     * Retrieve a setting value by key.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $settings = Cache::remember('app_settings', 3600, fn () => self::all()->keyBy('key'));

        $setting = $settings->get($key);

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => in_array($setting->value, [true, 1, '1', 'true', 'yes', 'on'], true),
            'integer' => (int) $setting->value,
            'decimal', 'float' => (float) $setting->value,
            'array', 'json' => json_decode($setting->value, true) ?? [],
            default => $setting->value,
        };
    }

    /**
     * Set a setting value.
     */
    public static function setValue(string $key, mixed $value, string $group = 'general', string $type = 'string'): self
    {
        Cache::forget('app_settings');

        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'group' => $group,
                'type' => $type,
            ]
        );
    }
}
