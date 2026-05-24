<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'group', 'key', 'value', 'label', 'type', 'options', 'description', 'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    /**
     * قراءة إعداد بالـ dot-notation مع كاش 60 دقيقة
     * Usage: SystemSetting::get('invoice.terms', 'الدفع خلال 30 يوم')
     */
    public static function get(string $dotKey, mixed $default = null): mixed
    {
        return Cache::remember("setting:{$dotKey}", 3600, function () use ($dotKey, $default) {
            [$group, $key] = array_pad(explode('.', $dotKey, 2), 2, null);
            if (! $key) {
                return $default;
            }

            $setting = self::where('group', $group)->where('key', $key)->first();
            if (! $setting) {
                return $default;
            }

            return match ($setting->type) {
                'toggle' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                'number' => is_numeric($setting->value) ? (float) $setting->value : $default,
                'json' => json_decode($setting->value, true) ?? $default,
                default => $setting->value ?? $default,
            };
        });
    }

    /**
     * كتابة إعداد + مسح الكاش
     */
    public static function set(string $dotKey, mixed $value): void
    {
        [$group, $key] = array_pad(explode('.', $dotKey, 2), 2, null);
        if (! $key) {
            return;
        }

        $stored = is_bool($value) ? ($value ? '1' : '0') : (string) $value;

        self::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $stored]
        );

        Cache::forget("setting:{$dotKey}");
    }

    /**
     * قراءة كل إعدادات مجموعة (key => value)
     */
    public static function getGroup(string $group): array
    {
        return self::where('group', $group)
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn ($s) => [$s->key => $s->value])
            ->toArray();
    }

    /**
     * مسح كاش كل الإعدادات
     */
    public static function clearCache(): void
    {
        self::all()->each(fn ($s) => Cache::forget("setting:{$s->group}.{$s->key}"));
    }
}
