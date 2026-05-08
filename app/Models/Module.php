<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Module extends Model
{
    protected $fillable = [
        'code', 'name', 'icon', 'is_active', 'sort_order', 'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * هل الوحدة نشطة؟ (مع كاش 60 دقيقة)
     */
    public static function isActive(string $code): bool
    {
        return Cache::remember("module:active:{$code}", 3600, function () use ($code) {
            return self::where('code', $code)->value('is_active') ?? true;
        });
    }

    /**
     * قائمة الوحدات النشطة
     */
    public static function activeCodes(): array
    {
        return Cache::remember('modules:active_codes', 3600, function () {
            return self::where('is_active', true)->pluck('code')->toArray();
        });
    }

    /**
     * مسح كاش الوحدات
     */
    public static function clearCache(): void
    {
        self::all()->each(fn ($m) => Cache::forget("module:active:{$m->code}"));
        Cache::forget('modules:active_codes');
    }
}
